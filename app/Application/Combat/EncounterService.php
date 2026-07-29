<?php

namespace App\Application\Combat;

use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Application\Loot\DropService;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;

class EncounterService
{
    private array $activeCooldowns = [];
    private array $activeDots = [];
    private $equippedSkills = null;
    private array $activeBuffs = [];

    // Rozszerzenie systemu skilli (2026-07-28): pasywne umiejętności (aura obrażeń,
    // szansa na dodatkowy atak) oraz licznik unieruchomienia potwora (freeze/stun)
    // w walkach 1 na 1. W walkach grupowych (over-level) unieruchomienie liczone jest
    // per-potwór wewnątrz tablicy $monsters (klucz 'cc_turns'), patrz simulateMultiCombat().
    private array $activePassives = [];
    private int $monsterCcTurns = 0;

    public function start(Character $character, Map $map, ?Monster $forcedMonster = null, string $targetStrategy = 'random'): Result
    {
        Log::info('EncounterService::start called', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'map_id' => $map->id,
            'map_name' => $map->name,
            'forced_monster_id' => $forcedMonster?->id,
            'target_strategy' => $targetStrategy,
        ]);

        try {
            return DB::transaction(function () use ($character, $map, $forcedMonster, $targetStrategy) {
                // Lock character row to serialize concurrent battle requests
                $char = Character::where('id', $character->id)->lockForUpdate()->first();
                if (!$char) {
                    return Result::error('CHARACTER_NOT_FOUND', 'Nie znaleziono postaci.');
                }

                // Auto-claim rewards for any completed encounters that haven't been applied yet
                $unappliedEncounters = Encounter::where('character_id', $char->id)
                    ->where('rewards_applied', false)
                    ->whereIn('state', ['win', 'finished', 'lose'])
                    ->get();

                foreach ($unappliedEncounters as $unappliedEnc) {
                    $this->applyRewards($unappliedEnc);
                }

                // Clean up stale 'ongoing' encounters older than 10 seconds
                Encounter::where('character_id', $char->id)
                    ->where('state', 'ongoing')
                    ->where('started_at', '<', now()->subSeconds(10))
                    ->update(['state' => 'cancelled']);

                // Check for active ongoing encounter (state == 'ongoing' within last 10s)
                $activeOngoing = Encounter::where('character_id', $char->id)
                    ->where('state', 'ongoing')
                    ->where('started_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($activeOngoing) {
                    return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater jest już w trakcie innej walki!');
                }

                // Check for active/recent PvP encounter (< 5s ago or pending/calculating)
                $recentPvP = PvpEncounter::where('attacker_character_id', $char->id)
                    ->where(function ($query) {
                        $query->whereIn('state', ['pending', 'calculating'])
                              ->orWhere('created_at', '>=', now()->subSeconds(5));
                    })
                    ->exists();

                if ($recentPvP) {
                    return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater bierze obecnie udział w pojedynku PvP.');
                }

                // Check map level requirement
                if (!$map->isAccessibleBy($char)) {
                    return Result::error('LEVEL_OUT_OF_RANGE', "Twój poziom ({$char->level}) wykracza poza przedział tej mapy (wymagany poziom: {$map->level_range}).");
                }

                $isTutorial = ($char->user && $char->user->game_stage <= 12);
                $isOverLevel = $map->isOverLevel($char);

                // Tier scaling: player's natural tier vs map's tier
                $playerTier = $isTutorial ? 1 : $map->getPlayerTier($char);
                $tierDiff = $isTutorial ? 0 : $map->getTierDiff($char);
                $tierMultiplier = $isTutorial ? 1.0 : $map->getMonsterTierMultiplier($char);
                $tierLabel = $tierDiff > 0 ? "[T{$playerTier}]" : null;

                $monster = $forcedMonster;
                $monstersList = [];

                if (!$monster) {
                    if ($isTutorial) {
                        // W samouczku zawsze losuj Wilka Leśnego
                        $monster = $map->monsters->first(function ($m) {
                            return str_contains(mb_strtolower($m->name), 'wilk');
                        }) ?? $map->monsters->sortBy('level')->first();
                    } else {
                        // Get monsters for this map. Rank 'worldboss' ma osobny, dedykowany
                        // mechanizm spotkania (WorldBossInstance + ?world_boss= w MapStub) i
                        // NIE może wypaść z normalnej puli eksploracji. Rank 'boss' to zwykły,
                        // zabijalny boss mapy (patrz MonsterLootSeeder) - nie ma dla niego żadnej
                        // innej ścieżki spotkania, więc musi zostać w puli losowej tak jak 'elite'.
                        $monstersPool = $map->monsters->whereNotIn('rank', [
                            \App\Domain\Combat\Enums\MonsterRank::WORLDBOSS,
                        ]);

                        if ($monstersPool->isEmpty()) {
                            return Result::error('NO_MONSTERS', 'Brak zwykłych potworów na tej mapie');
                        }

                        if ($isOverLevel) {
                            $monsterCount = mt_rand(3, 4);
                            $selectedCounts = [];
                            for ($i = 0; $i < $monsterCount; $i++) {
                                // Filter pool to monsters picked fewer than 2 times
                                $availablePool = $monstersPool->filter(function($m) use ($selectedCounts) {
                                    return ($selectedCounts[$m->id] ?? 0) < 2;
                                });

                                if ($availablePool->isEmpty()) {
                                    $availablePool = $monstersPool;
                                }

                                $mObj = $availablePool->random();
                                $selectedCounts[$mObj->id] = ($selectedCounts[$mObj->id] ?? 0) + 1;

                                $scaled = $mObj->getScaledStats($char->level, false);

                                // Apply tier multiplier to all stats
                                if ($tierMultiplier > 1.0) {
                                    foreach (['hp', 'atk', 'def', 'agi', 'int'] as $statKey) {
                                        if (isset($scaled[$statKey])) {
                                            $scaled[$statKey] = (int)ceil($scaled[$statKey] * $tierMultiplier);
                                        }
                                    }
                                }

                                $monstersList[] = [
                                    'id' => $mObj->id,
                                    'name' => $mObj->name,
                                    'level' => $mObj->level,
                                    'maxHp' => $scaled['hp'],
                                    'hp' => $scaled['hp'],
                                    'stats' => $scaled,
                                    'avatar' => $mObj->avatar,
                                    'rank' => is_object($mObj->rank) ? $mObj->rank->value : (string)$mObj->rank,
                                    'tier_label' => $tierLabel,
                                ];
                            }
                            $firstMonsterId = $monstersList[0]['id'];
                            $monster = $map->monsters->find($firstMonsterId) ?? $monstersPool->random();
                        } else {
                            $monster = $monstersPool->random();
                        }
                    }
                }

                Log::info('Selected monster for encounter', [
                    'monster_id' => $monster->id,
                    'monster_name' => $monster->name,
                    'monster_level' => $monster->level,
                    'is_overlevel' => $isOverLevel,
                    'player_tier' => $playerTier,
                    'tier_diff' => $tierDiff,
                    'tier_multiplier' => $tierMultiplier,
                ]);

                // Check if this is an active world boss
                $activeBoss = WorldBossInstance::where('map_id', $map->id)
                    ->where('monster_id', $monster->id)
                    ->where('is_defeated', false)
                    ->first();

                if ($activeBoss) {
                    $hasParticipated = WorldBossDamageLog::where('world_boss_instance_id', $activeBoss->id)
                        ->where('character_id', $character->id)
                        ->exists();

                    if ($hasParticipated) {
                        return Result::error('ALREADY_PARTICIPATED', 'Już walczyłeś z tym World Bossem!');
                    }
                }

                // Determine turn order using scaled stats (apply tier multiplier to single monster too)
                $totalAttributes = $character->getTotalAttributes();
                $playerAgi = $totalAttributes['agi'] ?? 0;
                $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);

                // Apply tier multiplier to single-monster stats
                if ($tierMultiplier > 1.0 && !$isOverLevel) {
                    foreach (['hp', 'atk', 'def', 'agi', 'int'] as $statKey) {
                        if (isset($scaledMonsterStats[$statKey])) {
                            $scaledMonsterStats[$statKey] = (int)ceil($scaledMonsterStats[$statKey] * $tierMultiplier);
                        }
                    }
                }

                $monsterAgi = $scaledMonsterStats['agi'];
                $playerFirst = $playerAgi >= $monsterAgi;

                // Create encounter
                $encounter = Encounter::create([
                    'character_id' => $character->id,
                    'map_id' => $map->id,
                    'monster_id' => $monster->id,
                    'state' => 'ongoing',
                    'gold_reward' => 0,
                    'xp_reward' => 0,
                    'player_first' => $playerFirst,
                    'turns' => [],
                    'combat_data' => [
                        'player_agi' => $playerAgi,
                        'monster_agi' => $monsterAgi,
                        'created_at' => now()->toISOString(),
                        'is_overlevel' => ($isOverLevel && !$forcedMonster && !$isTutorial),
                        'target_strategy' => $targetStrategy,
                        'monsters' => $monstersList,
                        'player_tier' => $playerTier,
                        'tier_diff' => $tierDiff,
                        'tier_multiplier' => $tierMultiplier,
                        'tier_label' => $tierLabel,
                        // Pre-scaled stats for single-monster fights
                        'scaled_monster_stats' => (!$isOverLevel && $tierMultiplier > 1.0) ? $scaledMonsterStats : null,
                    ],
                    'rewards_applied' => false,
                    'started_at' => now(),
                ]);

                Log::info('Encounter created successfully', [
                    'encounter_id' => $encounter->id,
                    'character_id' => $character->id,
                    'map_id' => $map->id,
                    'monster_id' => $monster->id
                ]);

                return Result::ok($encounter);
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::start failed', [
                'character_id' => $character->id,
                'map_id' => $map->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Result::error('ENCOUNTER_START_FAILED', 'Nie udało się rozpocząć walki');
        }
    }

    public function simulate(Encounter $encounter): Result
    {
        Log::info('EncounterService::simulate called', [
            'encounter_id' => $encounter->id,
        ]);

        try {
            return DB::transaction(function () use ($encounter) {
                // Load relations
                $encounter->load(['character.user', 'character.guild', 'monster']);

                $character = $encounter->character;
                $monster = $encounter->monster;

                if (!$character || !$monster) {
                    return Result::error('MISSING_DATA', 'Brak danych do symulacji walki');
                }

                Log::info('Starting combat simulation', [
                    'character_name' => $character->name,
                    'character_level' => $character->level,
                    'monster_name' => $monster->name,
                    'monster_level' => $monster->level,
                ]);

                $isTutorial = ($character->user && $character->user->game_stage <= 12);

                // Use pre-scaled stats if tier multiplier was applied in start()
                $scaledStatOverride = $encounter->combat_data['scaled_monster_stats'] ?? null;
                $scaledStats = $scaledStatOverride ?? $monster->getScaledStats($character->level, $isTutorial);

                // Initialize HP
                $playerHp = $character->getMaxHp();
                $monsterHp = $scaledStats['hp'];
                $playerMaxHp = $playerHp;
                $monsterMaxHp = $monsterHp;

                Log::info('Combat stats initialized', [
                    'player_hp' => $playerHp,
                    'monster_hp' => $monsterHp,
                ]);

                // Simulate combat
                $isOverLevel = $encounter->combat_data['is_overlevel'] ?? false;
                $rankVal = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
                $isWorldBoss = ($rankVal === 'worldboss');
                if ($isWorldBoss) {
                    $monsterMaxHp = 999999999;
                    $monsterHp = $monsterMaxHp;
                }

                $goldRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];
                $xpRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];

                if ($isOverLevel && !empty($encounter->combat_data['monsters'])) {
                    $monstersData = $encounter->combat_data['monsters'];
                    $tactic = $encounter->combat_data['target_strategy'] ?? 'random';
                    $turns = $this->simulateMultiCombat($character, $monstersData, $playerHp, $tactic, $playerMaxHp);

                    $lastTurn = end($turns);
                    $finalPlayerHp = $lastTurn ? ($lastTurn['playerHp'] ?? 0) : $playerHp;
                    $allDead = true;
                    if ($lastTurn && !empty($lastTurn['monsters_state'])) {
                        foreach ($lastTurn['monsters_state'] as $ms) {
                            if (($ms['hp'] ?? 0) > 0) {
                                $allDead = false;
                                break;
                            }
                        }
                    }

                    $winner = ($finalPlayerHp > 0 && $allDead) ? 'player' : 'enemy';

                    if ($winner === 'player') {
                        $goldRewardData = $this->calculateGoldReward($monster, $character);
                        $xpRewardData = $this->calculateXpReward($monster, $character);

                        // Apply Over-Level Penalty (-66% rewards, so 0.33x multiplier)
                        $goldRewardData['base'] = (int)ceil($goldRewardData['base'] * 0.33);
                        $goldRewardData['total'] = (int)ceil($goldRewardData['total'] * 0.33);
                        $goldRewardData['bonus'] = $goldRewardData['total'] - $goldRewardData['base'];

                        $xpRewardData['base'] = (int)ceil($xpRewardData['base'] * 0.33);
                        $xpRewardData['total'] = (int)ceil($xpRewardData['total'] * 0.33);
                        $xpRewardData['bonus'] = $xpRewardData['total'] - $xpRewardData['base'];
                    }
                } else {
                    $turns = $this->simulateCombat($character, $monster, $playerHp, $monsterHp, $isWorldBoss, $playerMaxHp);
                    $lastTurn = end($turns);
                    $finalMonsterHp = $lastTurn ? $lastTurn['enemyHp'] : $monsterHp;

                    if ($isWorldBoss) {
                        $winner = 'enemy'; // Worldboss always wins/survives
                        $damageDealt = max(0, $monsterMaxHp - $finalMonsterHp);
                        
                        // Skalowanie nagród używając spłaszczonej krzywej (np. pierwiastek) by zapobiec nieskończonemu wzrostowi
                        $baseGold = max(10, (int)ceil(pow($damageDealt, 0.7)));
                        $baseXp = max(80, (int)ceil(pow($damageDealt, 0.75) * 4));

                        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
                        $goldMult = $multiplierService->getGoldMultiplier($character);
                        $xpMult = $multiplierService->getExpMultiplier($character);

                        $goldRewardData = ['base' => $baseGold, 'bonus' => (int)($baseGold * $goldMult) - $baseGold, 'total' => (int)($baseGold * $goldMult), 'multiplier' => $goldMult];
                        $xpRewardData = ['base' => $baseXp, 'bonus' => (int)($baseXp * $xpMult) - $baseXp, 'total' => (int)($baseXp * $xpMult), 'multiplier' => $xpMult];

                        // Zapisz log damage
                        $activeBoss = WorldBossInstance::where('map_id', $encounter->map_id)
                            ->where('monster_id', $monster->id)
                            ->where('is_defeated', false)
                            ->first();
                            
                        if (!$activeBoss) {
                            $activeBoss = WorldBossInstance::create([
                                'map_id' => $encounter->map_id,
                                'monster_id' => $monster->id,
                                'total_hp' => $monster->stats['hp'] ?? 1000000,
                                'current_hp' => $monster->stats['hp'] ?? 1000000,
                                'is_defeated' => false
                            ]);
                        }

                        WorldBossDamageLog::create([
                            'world_boss_instance_id' => $activeBoss->id,
                            'character_id' => $character->id,
                            'damage' => $damageDealt
                        ]);
                        
                        $activeBoss->decrement('current_hp', $damageDealt);
                        if ($activeBoss->current_hp <= 0) {
                            $activeBoss->update(['is_defeated' => true]);
                        }
                    } else {
                        $winner = $finalMonsterHp <= 0 ? 'player' : 'enemy';

                        if ($winner === 'player') {
                            $goldRewardData = $this->calculateGoldReward($monster, $character);
                            $xpRewardData = $this->calculateXpReward($monster, $character);
                        }
                    }
                }
                
                $goldReward = $goldRewardData['total'];
                $xpReward = $xpRewardData['total'];

                Log::info('Combat simulation completed', [
                    'turns_count' => count($turns),
                    'winner' => $winner,
                    'gold_reward' => $goldReward,
                    'xp_reward' => $xpReward,
                ]);

                // UŻYJ METOD Z MODELU zamiast bezpośredniego update()
                if ($winner === 'player') {
                    $encounter->markAsWon();
                    $dropService = app(DropService::class);
                    $dropResult = $dropService->rollLoot($encounter);

                    // Update hunting quests
                    $questService = app(\App\Application\Quests\QuestService::class);
                    $questService->progressQuest($character, 'hunting', [(string)$monster->id, (string)$encounter->map_id]);

                    // Fire MonsterDefeated event for Bestiary and Achievements
                    event(new \App\Domain\Collections\Events\MonsterDefeated($character, $monster, $encounter->map_id));

                    if ($dropResult->isError()) {
                        Log::warning('Failed to apply loot drops', [
                            'encounter_id' => $encounter->id,
                            'error' => $dropResult->getErrorMessage()
                        ]);
                    }
                } else {
                    if ($isWorldBoss) {
                        $encounter->update(['state' => 'finished']); // don't mark as lost for world boss since we give rewards
                    } else {
                        $encounter->markAsLost();
                    }
                }

                // Ustaw nagrody i inne dane
                $encounter->setRewards($goldReward, $xpReward);
                $encounter->setTurns($turns);

                // Zbieranie powiadomień wygenerowanych podczas walki (np. awanse misji, osiągnięcia)
                $notifications = app(\App\Application\Shared\NotificationTracker::class)->flush();

                // Update combat_data
                $combatData = array_merge($encounter->combat_data ?? [], [
                    'player_max_hp' => $playerMaxHp,
                    'monster_max_hp' => $monsterMaxHp,
                    'completed_at' => now()->toISOString(),
                    'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    'rewards' => [
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                    ],
                    'notifications' => $notifications
                ]);
                $encounter->combat_data = $combatData;
                $encounter->save();

                // Create result for UI
                $combatResultData = [
                    'player' => [
                        'name' => $character->name,
                        'level' => $character->level,
                        'hp' => $playerMaxHp,
                        'maxHp' => $playerMaxHp,
                        'stats' => [
                            'str' => $character->getTotalAttributes()['str'] ?? 0,
                            'int' => $character->getTotalAttributes()['int'] ?? 0,
                            'vit' => $character->getTotalAttributes()['vit'] ?? 0,
                            'agi' => $character->getTotalAttributes()['agi'] ?? 0,
                        ]
                    ],
                    'enemy' => [
                        'name' => $monster->name,
                        'level' => $monster->level,
                        'hp' => $monsterMaxHp,
                        'maxHp' => $monsterMaxHp,
                        'stats' => [
                            'atk' => $scaledStats['atk'],
                            'def' => $scaledStats['def'],
                            'agi' => $scaledStats['agi'],
                            'hp' => $scaledStats['hp'],
                        ]
                    ],
                    'turns' => $turns,
                    'result' => $isWorldBoss ? 'finished' : ($winner === 'player' ? 'win' : 'lose'),
                    'rewards' => [
                        'gold' => $goldReward,
                        'xp' => $xpReward,
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                        'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    ],
                    'notifications' => $notifications
                ];

                return Result::ok($combatResultData);
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::simulate failed', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Result::error('SIMULATION_FAILED', 'Symulacja walki nie powiodła się');
        }
    }

    public function applyRewards(Encounter $encounter): Result
    {
        try {
            return DB::transaction(function () use ($encounter) {
                $enc = Encounter::where('id', $encounter->id)->lockForUpdate()->first();
                if (!$enc) {
                    return Result::error('NOT_FOUND', 'Spotkanie nie istnieje');
                }

                if ($enc->rewards_applied) {
                    Log::info('Rewards already applied for encounter', ['encounter_id' => $enc->id]);
                    return Result::ok($enc);
                }

                if (in_array($enc->state, ['win', 'finished'])) {
                    $character = $enc->character;
                    if ($character) {
                        if ($enc->gold_reward > 0) {
                            $character->increment('gold', $enc->gold_reward);
                        }
                        if ($enc->xp_reward > 0) {
                            $character->increment('xp', $enc->xp_reward);
                        }

                        app(\App\Application\Characters\LevelUpService::class)->checkAndApply($character);

                        $dropService = app(DropService::class);
                        $dropService->applyLoot($enc);
                    }
                }

                $enc->markRewardsApplied();
                return Result::ok($enc->fresh());
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::applyRewards failed', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('APPLY_REWARDS_FAILED', 'Nie udało się przyznać nagród z walki.');
        }
    }

    private function simulateCombat(Character $character, Monster $monster, int $playerHp, int $monsterHp, bool $isWorldBoss = false, int $playerMaxHp = 0): array
    {
        // Reset state for new combat
        $this->activeCooldowns = [];
        $this->activeDots = [];
        $this->activeBuffs = [];
        $this->activePassives = [];
        $this->monsterCcTurns = 0;
        $this->equippedSkills = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $this->activeCooldowns[$cs->id] = max(0, $cs->skill->base_cooldown - 1); // ready slightly earlier
            }
        }

        $this->initPassives($character);

        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'];
        $playerFirst = $playerAgi >= $monsterAgi;

        $turns = [];
        $turnCount = 0;
        $maxTurns = $isWorldBoss ? 20 : 50;

        $monsterMaxHp = $monsterHp;

        while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
            $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            if ($isPlayerTurn) {
                // Player's turn: decrease skill cooldowns
                foreach ($this->activeCooldowns as $id => $cd) {
                    if ($cd > 0) $this->activeCooldowns[$id]--;
                }
                // Decrease active buffs duration
                foreach ($this->activeBuffs as $k => $b) {
                    $this->activeBuffs[$k]['duration']--;
                    if ($this->activeBuffs[$k]['duration'] <= 0) unset($this->activeBuffs[$k]);
                }

                $turn = $this->playerAttack($character, $monster, $playerHp, $monsterHp, $monsterMaxHp, $isWorldBoss, $playerMaxHp);
                $playerHp = $turn['playerHp'];
                $monsterHp = $turn['enemyHp'];

                if (!empty($turn['cc_applied'])) {
                    $this->monsterCcTurns = max($this->monsterCcTurns, (int) $turn['cc_applied']['duration']);
                }

                $turn['state'] = [
                    'dots' => $this->activeDots,
                    'buffs' => $this->activeBuffs,
                    'cooldowns' => $this->activeCooldowns,
                ];
                $turns[] = $turn;
                $turnCount++;

                // Pasywna szansa na natychmiastowy dodatkowy atak (np. "Furia Berserkera" - topór)
                if ($monsterHp > 0 && $this->rollExtraAttack()) {
                    $bonusTurn = $this->playerAttack($character, $monster, $playerHp, $monsterHp, $monsterMaxHp, $isWorldBoss, $playerMaxHp);
                    $bonusTurn['extra_attack'] = true;
                    $playerHp = $bonusTurn['playerHp'];
                    $monsterHp = $bonusTurn['enemyHp'];

                    if (!empty($bonusTurn['cc_applied'])) {
                        $this->monsterCcTurns = max($this->monsterCcTurns, (int) $bonusTurn['cc_applied']['duration']);
                    }

                    $bonusTurn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                    ];
                    $turns[] = $bonusTurn;
                    $turnCount++;
                }

                continue;
            }

            if ($this->monsterCcTurns > 0) {
                // Potwór jest zamrożony/ogłuszony - traci turę ataku
                $this->monsterCcTurns--;
                $turn = [
                    'actor' => 'enemy',
                    'type' => 'crowd_controlled',
                    'value' => 0,
                    'crit' => false,
                    'playerHp' => $playerHp,
                    'enemyHp' => $monsterHp,
                ];
            } else {
                $turn = $this->monsterAttack($monster, $character, $playerHp, $monsterHp);
                $playerHp = $turn['playerHp'];
            }

            $turn['state'] = [
                'dots' => $this->activeDots,
                'buffs' => $this->activeBuffs,
                'cooldowns' => $this->activeCooldowns,
            ];

            $turns[] = $turn;
            $turnCount++;
        }

        return $turns;
    }

    /**
     * Wylicza aktywne efekty pasywnych umiejętności (equip_slot 1-3, type = 'passive')
     * z wyposażonego decku. W przeciwieństwie do skilli aktywnych, pasywy nie mają
     * cooldownu ani nie "zastępują" ataku - działają cały czas, o ile wymagana broń
     * (required_weapon_type) jest założona w ręce głównej.
     *
     * Obsługiwane effect_type:
     * - passive_aura_dmg: stały % bonusu do obrażeń fizycznych (np. "Aura Miecza").
     * - passive_extra_attack: szansa (0..1) na natychmiastowy dodatkowy atak po trafieniu
     *   (np. "Furia Berserkera" dla topora).
     */
    private function initPassives(Character $character): void
    {
        $this->activePassives = [];
        if (!$this->equippedSkills) {
            return;
        }

        $equippedWeaponType = $character->getEquippedWeaponType();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type !== 'passive') {
                continue;
            }

            $reqWep = $cs->skill->required_weapon_type;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                continue;
            }

            $effVal = $cs->skill->base_value + ($cs->skill->scaling_value * ($cs->level - 1));

            if ($cs->skill->effect_type === 'passive_aura_dmg') {
                $this->activePassives['aura_dmg'] = ($this->activePassives['aura_dmg'] ?? 0) + $effVal;
            } elseif ($cs->skill->effect_type === 'passive_extra_attack') {
                // Cap zabezpieczający przed absurdalnymi wartościami z panelu admina
                $chance = min(0.75, max(0, $effVal));
                $this->activePassives['extra_attack_chance'] = max($this->activePassives['extra_attack_chance'] ?? 0, $chance);
            }
        }
    }

    /**
     * Rzuca kością na pasywną szansę "dodatkowego ataku" (np. Furia Berserkera).
     * Wspólne dla walk 1 na 1 oraz walk grupowych.
     */
    private function rollExtraAttack(): bool
    {
        $chance = $this->activePassives['extra_attack_chance'] ?? 0;
        if ($chance <= 0) {
            return false;
        }

        return mt_rand(1, 10000) <= (int) round($chance * 10000);
    }

    private function playerAttack(Character $character, Monster $monster, int $playerHp, int $monsterHp, int $monsterMaxHp, bool $isWorldBoss = false, int $playerMaxHp = 0): array
    {
        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        $equippedWeaponType = $character->getEquippedWeaponType();

        $usedSkill = null;

        // Try to use skill
        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active' && ($this->activeCooldowns[$cs->id] ?? 0) <= 0) {
                // Check weapon requirement
                $reqWep = $cs->skill->required_weapon_type;
                if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                    continue;
                }

                // Use skill
                $this->activeCooldowns[$cs->id] = $cs->skill->base_cooldown;
                
                $effVal = $cs->skill->base_value + ($cs->skill->scaling_value * ($cs->level - 1));
                
                if ($cs->skill->effect_type === 'poison' || $cs->skill->effect_type === 'fire') {
                    $this->activeDots[] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'description' => $cs->skill->description ?? ($cs->skill->effect_type === 'poison' ? 'Zadaje obrażenia od otrucia co turę.' : 'Zadaje obrażenia od ognia co turę.'),
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                } else if ($cs->skill->effect_type === 'buff_phys_dmg') {
                    $this->activeBuffs['phys_dmg'] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'description' => $cs->skill->description ?? ('Zwiększa obrażenia fizyczne o ' . round($effVal * 100) . '%.'),
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                }

                $usedSkill = [
                    'skill' => $cs->skill,
                    'effVal' => $effVal,
                ];
                break;
            }
        }

        // Process active DoTs on monster during this exchange
        $dotDamage = 0;
        $dotType = null;
        $baseHpForDot = $monster->stats['hp'] ?? 10000;

        foreach ($this->activeDots as $k => $dot) {
            if ($dot['type'] === 'poison') {
                $targetHp = $isWorldBoss ? $baseHpForDot : $monsterHp;
                $dmg = (int)($targetHp * $dot['value']);
            } else if ($dot['type'] === 'fire') {
                $targetHp = $isWorldBoss ? $baseHpForDot : $monsterMaxHp;
                $dmg = (int)($targetHp * $dot['value']);
            } else {
                $dmg = 0;
            }

            if ($isWorldBoss) {
                // Cap DoT damage per tick on World Boss to prevent scaling exploit
                $playerDmgData = $this->calculateDamage($character, $monster);
                $maxDotCap = max(100, (int)($playerDmgData['total'] * 5));
                $dmg = min($dmg, $maxDotCap);
            }

            $dmg = max(1, $dmg);
            $dotDamage += $dmg;
            $dotType = $dot['type'];

            $this->activeDots[$k]['duration']--;
            if ($this->activeDots[$k]['duration'] <= 0) unset($this->activeDots[$k]);
        }

        if ($usedSkill) {
            $csSkill = $usedSkill['skill'];
            $effVal = $usedSkill['effVal'];

            // --- HEAL: umiejętność lecząca zastępuje atak tej tury - nie zadaje
            // obrażeń przeciwnikowi (poza już aktywnymi DoT-ami), leczy postać gracza
            // o % maksymalnego HP (base_value, skalowane scaling_value/poziom). ---
            if ($csSkill->effect_type === 'heal') {
                $healAmount = max(1, (int) round($playerMaxHp * $effVal));
                $newPlayerHp = min($playerMaxHp, $playerHp + $healAmount);
                $newMonsterHp = max(0, $monsterHp - $dotDamage);

                return [
                    'actor' => 'player',
                    'type' => 'skill_heal',
                    'skill_name' => $csSkill->name,
                    'effect_type' => $csSkill->effect_type,
                    'is_magic' => (bool) $csSkill->is_magic,
                    'value' => $healAmount,
                    'healAmount' => $healAmount,
                    'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                    'dotType' => $dotDamage > 0 ? $dotType : null,
                    'crit' => false,
                    'playerHp' => $newPlayerHp,
                    'enemyHp' => $newMonsterHp,
                ];
            }

            $skillMultiplier = 1.0;
            if (in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                $skillMultiplier = $effVal;
            }

            $damageData = $this->calculateDamage($character, $monster);
            $damage = (int)($damageData['total'] * $skillMultiplier);
            $baseDamage = (int)($damageData['base'] * $skillMultiplier);
            $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
            $magicDamage = (int)(($damageData['magic'] ?? 0) * $skillMultiplier);

            // Active Buffs Application (bonus z aktywnego buffa + stała pasywna aura, np. Aura Miecza)
            $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
            if ($physBuffValue > 0) {
                $damage = (int)($damage * (1 + $physBuffValue));
                $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
            }

            $isCrit = $this->rollCritical($character, $monster);
            if ($isCrit) {
                $damage = (int)($damage * 1.5);
                $baseDamage = (int)($baseDamage * 1.5);
                $bonusDamage = (int)($bonusDamage * 1.5);
                $magicDamage = (int)($magicDamage * 1.5);
            }

            // Przełącznik obrażeń magicznych: skille oznaczone is_magic (Różdżka/Dzwon)
            // pokazują CAŁOŚĆ wyliczonych obrażeń jako magicDamage zamiast
            // baseDamage/bonusDamage. To wyłącznie reklasyfikacja do UI/logu walki -
            // mitygacja (obrona przeciwnika) liczona jest identycznie jak dla obrażeń
            // fizycznych, zgodnie z celowym uproszczeniem opisanym w combat.md (brak
            // osobnej "obrony magicznej").
            if ($csSkill->is_magic) {
                $magicDamage += $baseDamage + $bonusDamage;
                $baseDamage = 0;
                $bonusDamage = 0;
            }

            $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

            $result = [
                'actor' => 'player',
                'type' => 'skill',
                'skill_name' => $csSkill->name,
                'effect_type' => $csSkill->effect_type,
                'is_magic' => (bool) $csSkill->is_magic,
                'value' => $damage,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => $isCrit,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
                'baseDamage' => $baseDamage,
                'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
                'magicDamage' => $magicDamage > 0 ? $magicDamage : null,
            ];

            if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
                // Unieruchamia cel na base_duration tur (traci turę/tury ataku).
                // Duration nie skaluje się z poziomem skilla - podobnie jak inne
                // pola base_duration w systemie (dot/buff) - balans ustawiany wprost
                // per-skill w seederze/panelu admina.
                $result['cc_applied'] = [
                    'type' => $csSkill->effect_type,
                    'duration' => max(1, (int) $csSkill->base_duration),
                ];
            }

            return $result;
        }

        // Standard attack
        $damageData = $this->calculateDamage($character, $monster);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $bonusDamage = $damageData['bonus'];
        $magicDamage = $damageData['magic'] ?? 0;

        // Apply global buffs + passive aura (np. Aura Miecza) to normal attacks
        $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
            $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
        }

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $isCrit = $this->rollCritical($character, $monster);
        $isMiss = $this->rollDodge($monsterAgi, $playerAgi);

        if ($isMiss) {
            $newMonsterHp = max(0, $monsterHp - $dotDamage);
            return [
                'actor' => 'player',
                'type' => 'miss',
                'value' => 0,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $bonusDamage = (int)($bonusDamage * 1.5);
            $magicDamage = (int)($magicDamage * 1.5);
        }

        $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

        $turn = [
            'actor' => 'player',
            'type' => 'hit',
            'value' => $damage,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
            'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => $isCrit,
            'playerHp' => $playerHp,
            'enemyHp' => $newMonsterHp,
        ];

        if ($bonusDamage > 0 || $magicDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['bonusDamage'] = $bonusDamage > 0 ? $bonusDamage : null;
            $turn['magicDamage'] = $magicDamage > 0 ? $magicDamage : null;
        }

        return $turn;
    }

    private function monsterAttack(Monster $monster, Character $character, int $playerHp, int $monsterHp): array
    {
        $damageData = $this->calculateMonsterDamage($monster, $character);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $resistDamage = $damageData['resist'];

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $isCrit = $this->rollMonsterCritical($monster, $character);
        $isMiss = $this->rollDodge($playerAgi, $monsterAgi);

        if ($isMiss) {
            return [
                'actor' => 'enemy',
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $monsterHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $resistDamage = (int)($resistDamage * 1.5);
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        $turn = [
            'actor' => 'enemy',
            'type' => 'hit',
            'value' => $damage,
            'crit' => $isCrit,
            'playerHp' => $newPlayerHp,
            'enemyHp' => $monsterHp,
        ];

        if ($resistDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['resistDamage'] = $resistDamage;
        }

        return $turn;
    }



    private function calculateDamage(Character $character, Monster $monster): array
    {
        $weaponType = $character->getEquippedWeaponType();
        $statBonus = $character->getAttributeAttackBonus($weaponType);
        $eq = $character->getEquipmentStats();

        $weaponAtkMin = ($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0);
        $weaponAtkMax = ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0);

        $baseDamageMin = 10 + $statBonus + ($character->level * 1) + $weaponAtkMin;
        $baseDamageMax = 10 + $statBonus + ($character->level * 1) + $weaponAtkMax;
        
        // Ensure max is at least min
        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }
        
        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $defense = $scaledMonsterStats['def'];

        $baseDamage = max(1, $damage - ($defense / 2));
        $bonusDamage = 0;

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $bonusKey = 'strong_vs_' . $typeStr;
            $altBonusKey = 'bonus_vs_' . $typeStr;
            $pluralBonusKey = 'strong_vs_' . $typeStr . 's';

            $bonusPercentage = ($eq[$bonusKey] ?? 0) + ($eq[$altBonusKey] ?? 0) + ($eq[$pluralBonusKey] ?? 0);
            if ($bonusPercentage > 0) {
                $bonusDamage = (int)($baseDamage * ($bonusPercentage / 100));
            }
        }

        // "Magic burst": bronie hybrydowe (np. Dzwon) mają szansę zadać dodatkowe,
        // OSOBNE obrażenia magiczne przy trafieniu, poza normalnym atakiem fizycznym.
        // Mitygowane tą samą obroną przeciwnika co reszta obrażeń (uproszczony model,
        // bez osobnej "obrony magicznej").
        $magicBurstDamage = 0;
        $magicBurstChance = $eq['magic_burst_chance'] ?? 0;
        if ($magicBurstChance > 0 && mt_rand(1, 100) <= $magicBurstChance) {
            $burstMin = $eq['magic_burst_min'] ?? 0;
            $burstMax = max($burstMin, $eq['magic_burst_max'] ?? 0);
            if ($burstMax > 0) {
                $rawBurst = mt_rand((int) $burstMin, (int) $burstMax);
                $magicBurstDamage = max(1, (int) round($rawBurst - ($defense / 2)));
            }
        }

        return [
            'base' => $baseDamage,
            'bonus' => $bonusDamage,
            'magic' => $magicBurstDamage,
            'total' => $baseDamage + $bonusDamage + $magicBurstDamage,
        ];
    }

    private function calculateMonsterDamage(Monster $monster, Character $character): array
    {
        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $baseDamage = $scaledMonsterStats['atk'];
        $vitality = $character->getTotalAttributes()['vit'] ?? 1;
        $eq = $character->getEquipmentStats();
        $defense = $vitality + ($character->level / 2) + ($eq['defense'] ?? 0);

        $damage = max(1, $baseDamage - ($defense / 2));
        $resistDamage = 0;

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $resistKey = 'resist_' . $typeStr;
            $pluralResistKey = 'resist_' . $typeStr . 's';
            
            $resistPercentage = ($eq[$resistKey] ?? 0) + ($eq[$pluralResistKey] ?? 0);
            if ($resistPercentage > 0) {
                $resistDamage = (int)($damage * ($resistPercentage / 100));
            }
        }

        return [
            'base' => (int)$damage,
            'resist' => $resistDamage,
            'total' => max(1, (int)$damage - $resistDamage)
        ];
    }

    private function rollCritical(Character $character, Monster $monster): bool
    {
        $agility = $character->getTotalAttributes()['agi'] ?? 1;
        $eq = $character->getEquipmentStats();
        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $baseCrit = 0.05 + ($agility * 0.004) + (($eq['crit_chance'] ?? 0) / 100);
        $agiCritPenalty = max(0, ($monsterAgi - $agility) * 0.0008);
        // UWAGA (fix 2026-07-28): usunięto górny sufit 50% na życzenie - szansa gracza na
        // krytyka rośnie teraz bez ograniczenia wraz z AGI oraz crit_chance z ekwipunku
        // (nowy system itemów opiera się mocno na tej staty, więc stary sztywny cap
        // psuł skalowanie). Dolny próg 0.03 zostaje, żeby krytyk nigdy nie spadł do zera
        // przy bardzo dużej przewadze AGI potwora. Krytyk POTWORA (rollMonsterCritical
        // poniżej) zostaje bez zmian, wciąż capowany na 30%.
        $critChance = max(0.03, $baseCrit - $agiCritPenalty);

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollMonsterCritical(Monster $monster, ?Character $character = null): bool
    {
        $isTutorial = ($character && $character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character ? $character->level : $monster->level, $isTutorial);
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;
        $playerAgi = $character ? ($character->getTotalAttributes()['agi'] ?? 0) : 0;

        $baseCrit = 0.03 + ($monsterAgi * 0.003);
        $agiCritPenalty = max(0, ($playerAgi - $monsterAgi) * 0.0008);
        $critChance = max(0.02, min(0.30, $baseCrit - $agiCritPenalty));

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollDodge(int $defenderAgi, int $attackerAgi): bool
    {
        // UWAGA (fix 2026-07-28): usunięto górny sufit 18% na życzenie. Ta funkcja jest
        // współdzielona - liczy szansę na unik zarówno gdy broni się gracz, jak i gdy
        // broni się potwór (patrz wywołania w playerAttack()/monsterAttack() poniżej),
        // więc zdjęcie capa dotyczy obu stron symetrycznie, tak jak to już wcześniej
        // działało (nie było tu podziału gracz/potwór - w przeciwieństwie do rollCritical
        // vs rollMonsterCritical, które to zawsze były rozdzielone).
        $agiDodgeAdvantage = max(0, $defenderAgi - $attackerAgi);
        $dodgeChance = 0.03 + ($agiDodgeAdvantage * 0.0015);

        return mt_rand(1, 1000) <= (int)round($dodgeChance * 1000);
    }

    private function calculateGoldReward(Monster $monster, Character $character): array
    {
        $baseGold = 10 + ($monster->level * 2);
        $variation = mt_rand(80, 120) / 100;
        $gold = (int)($baseGold * $variation);

        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
        $multiplier = $multiplierService->getGoldMultiplier($character);
        
        $total = (int)round($gold * $multiplier);
        $bonus = $total - $gold;

        return [
            'base' => $gold,
            'bonus' => $bonus,
            'total' => $total,
            'multiplier' => $multiplier
        ];
    }

    private function calculateXpReward(Monster $monster, Character $character): array
    {
        $levelDiff = $monster->level - $character->level;
        $baseXp = 25 * pow($monster->level, 1.2) + 30;

        if ($levelDiff > 0) {
            $baseXp *= (1 + ($levelDiff * 0.1));
        } elseif ($levelDiff < -5) {
            $baseXp *= max(0.1, 1 + ($levelDiff * 0.05));
        }
        
        $baseXp = (int) round($baseXp);

        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
        $multiplier = $multiplierService->getExpMultiplier($character);
        
        $total = (int)round($baseXp * $multiplier);
        $bonus = $total - $baseXp;

        return [
            'base' => $baseXp,
            'bonus' => $bonus,
            'total' => $total,
            'multiplier' => $multiplier
        ];
    }

    /**
     * Znajduje pierwszy gotowy (cooldown <= 0), pasujący do broni skill obszarowy
     * (is_aoe = true) z wyposażonego decku. Używane wyłącznie w starciach grupowych
     * (simulateMultiCombat) - np. "Rozproszenie Strzał" (łuk), "Kule Ognia" (różdżka).
     */
    private function resolveAoeSkill(Character $character): ?\App\Infrastructure\Persistence\CharacterCombatSkill
    {
        $equippedWeaponType = $character->getEquippedWeaponType();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type !== 'active' || ($this->activeCooldowns[$cs->id] ?? 0) > 0) {
                continue;
            }
            if (!$cs->skill->is_aoe) {
                continue;
            }
            if (!in_array($cs->skill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                continue;
            }

            $reqWep = $cs->skill->required_weapon_type;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                continue;
            }

            return $cs;
        }

        return null;
    }

    /**
     * Wylicza obrażenia skilla obszarowego wobec JEDNEGO z celów w grupie. Analogiczne
     * do gałęzi obrażeń w playerAttack(), ale bez ponownego wyboru/konsumpcji cooldownu
     * (skill jest konsumowany raz przez wywołującego dla całej salwy AOE) i bez DoT
     * (efekty obszarowe działają na obrażeniach bezpośrednich/CC, nie na DoT).
     */
    private function playerAttackAoeTarget(Character $character, CombatSkill $csSkill, float $effVal, Monster $monsterModel, int $monsterHp): array
    {
        $skillMultiplier = in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true) ? $effVal : 1.0;

        $damageData = $this->calculateDamage($character, $monsterModel);
        $damage = (int)($damageData['total'] * $skillMultiplier);
        $baseDamage = (int)($damageData['base'] * $skillMultiplier);
        $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
        $magicDamage = (int)(($damageData['magic'] ?? 0) * $skillMultiplier);

        $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
            $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
        }

        $isCrit = $this->rollCritical($character, $monsterModel);
        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $bonusDamage = (int)($bonusDamage * 1.5);
            $magicDamage = (int)($magicDamage * 1.5);
        }

        if ($csSkill->is_magic) {
            $magicDamage += $baseDamage + $bonusDamage;
            $baseDamage = 0;
            $bonusDamage = 0;
        }

        $newMonsterHp = max(0, $monsterHp - $damage);

        $turn = [
            'actor' => 'player',
            'type' => 'skill',
            'aoe' => true,
            'skill_name' => $csSkill->name,
            'effect_type' => $csSkill->effect_type,
            'is_magic' => (bool) $csSkill->is_magic,
            'value' => $damage,
            'crit' => $isCrit,
            'enemyHp' => $newMonsterHp,
            'baseDamage' => $baseDamage,
            'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
            'magicDamage' => $magicDamage > 0 ? $magicDamage : null,
        ];

        if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
            $turn['cc_applied'] = [
                'type' => $csSkill->effect_type,
                'duration' => max(1, (int) $csSkill->base_duration),
            ];
        }

        return $turn;
    }

    private function simulateMultiCombat(Character $character, array $monsters, int $playerHp, string $tactic = 'random', int $playerMaxHp = 0): array
    {
        $this->activeCooldowns = [];
        $this->activeDots = [];
        $this->activeBuffs = [];
        $this->activePassives = [];
        $this->equippedSkills = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $this->activeCooldowns[$cs->id] = max(0, $cs->skill->base_cooldown - 1);
            }
        }

        $this->initPassives($character);

        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        // Znaczniki unieruchomienia (freeze/stun) per-potwór
        foreach ($monsters as &$initM) {
            $initM['cc_turns'] = $initM['cc_turns'] ?? 0;
        }
        unset($initM);

        $turns = [];
        $turnCount = 0;
        $maxTurns = 80;

        $extraMonsters = count($monsters) - 1;
        // Monster damage reduction: 10% per extra monster
        $monsterDmgMult = max(0.1, 1.0 - ($extraMonsters * 0.10));

        while ($playerHp > 0 && $this->anyMonsterAlive($monsters) && $turnCount < $maxTurns) {
            // 1. Living monsters attack sequentially (chyba że są unieruchomione)
            foreach ($monsters as $mIdx => &$m) {
                if ($m['hp'] <= 0 || $playerHp <= 0) {
                    continue;
                }

                if (($m['cc_turns'] ?? 0) > 0) {
                    $m['cc_turns']--;
                    $turn = [
                        'actor' => 'enemy',
                        'type' => 'crowd_controlled',
                        'value' => 0,
                        'crit' => false,
                        'playerHp' => $playerHp,
                        'enemyHp' => $m['hp'],
                        'enemy_index' => $mIdx,
                        'enemy_name' => $m['name'],
                    ];
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                    ];
                    $turns[] = $turn;
                    $turnCount++;
                    continue;
                }

                $monsterModel = Monster::find($m['id']);
                if (!$monsterModel) {
                    continue;
                }

                $turn = $this->monsterAttack($monsterModel, $character, $playerHp, $m['hp']);

                if ($turn['type'] === 'hit' && ($turn['value'] ?? 0) > 0) {
                    $turn['value'] = max(1, (int)round($turn['value'] * $monsterDmgMult));
                    if (isset($turn['baseDamage'])) {
                        $turn['baseDamage'] = max(1, (int)round($turn['baseDamage'] * $monsterDmgMult));
                    }
                }

                $playerHp = max(0, $playerHp - $turn['value']);
                $turn['playerHp'] = $playerHp;
                $turn['enemy_index'] = $mIdx;
                $turn['enemy_name'] = $m['name'];
                $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                $turn['state'] = [
                    'dots' => $this->activeDots,
                    'buffs' => $this->activeBuffs,
                    'cooldowns' => $this->activeCooldowns,
                ];

                $turns[] = $turn;
                $turnCount++;
            }
            unset($m);

            if ($playerHp <= 0 || !$this->anyMonsterAlive($monsters)) {
                break;
            }

            // 2. Player attacks: skill obszarowy bije wszystkich, w innym wypadku 1 cel wg taktyki
            foreach ($this->activeCooldowns as $id => $cd) {
                if ($cd > 0) $this->activeCooldowns[$id]--;
            }
            foreach ($this->activeBuffs as $k => $b) {
                $this->activeBuffs[$k]['duration']--;
                if ($this->activeBuffs[$k]['duration'] <= 0) unset($this->activeBuffs[$k]);
            }

            $aoeSkillCs = $this->resolveAoeSkill($character);

            if ($aoeSkillCs) {
                $this->activeCooldowns[$aoeSkillCs->id] = $aoeSkillCs->skill->base_cooldown;
                $effVal = $aoeSkillCs->skill->base_value + ($aoeSkillCs->skill->scaling_value * ($aoeSkillCs->level - 1));

                foreach ($monsters as $mIdx => &$m) {
                    if (($m['hp'] ?? 0) <= 0) {
                        continue;
                    }
                    $monsterModel = Monster::find($m['id']);
                    if (!$monsterModel) {
                        continue;
                    }

                    $turn = $this->playerAttackAoeTarget($character, $aoeSkillCs->skill, $effVal, $monsterModel, $m['hp']);
                    $m['hp'] = $turn['enemyHp'];

                    if (!empty($turn['cc_applied'])) {
                        $m['cc_turns'] = max($m['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
                    }

                    $turn['target_index'] = $mIdx;
                    $turn['target_name'] = $m['name'];
                    $turn['playerHp'] = $playerHp;
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                    ];

                    $turns[] = $turn;
                    $turnCount++;
                }
                unset($m);
            } else {
                $targetIdx = $this->selectTargetMonster($monsters, $tactic);
                if ($targetIdx === null) {
                    break;
                }

                $targetMonster = &$monsters[$targetIdx];
                $monsterModel = Monster::find($targetMonster['id']);
                if ($monsterModel) {
                    $turn = $this->playerAttack($character, $monsterModel, $playerHp, $targetMonster['hp'], $targetMonster['maxHp'], false, $playerMaxHp);
                    $playerHp = $turn['playerHp'];
                    $targetMonster['hp'] = $turn['enemyHp'];

                    if (!empty($turn['cc_applied'])) {
                        $targetMonster['cc_turns'] = max($targetMonster['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
                    }

                    $turn['target_index'] = $targetIdx;
                    $turn['target_name'] = $targetMonster['name'];
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                    ];

                    $turns[] = $turn;
                    $turnCount++;
                }
                unset($targetMonster);
            }

            // 3. Pasywna szansa na natychmiastowy dodatkowy atak (np. Furia Berserkera)
            if ($playerHp > 0 && $this->anyMonsterAlive($monsters) && $this->rollExtraAttack()) {
                $bonusIdx = $this->selectTargetMonster($monsters, $tactic);
                if ($bonusIdx !== null) {
                    $bonusTarget = &$monsters[$bonusIdx];
                    $bonusMonsterModel = Monster::find($bonusTarget['id']);
                    if ($bonusMonsterModel) {
                        $bonusTurn = $this->playerAttack($character, $bonusMonsterModel, $playerHp, $bonusTarget['hp'], $bonusTarget['maxHp'], false, $playerMaxHp);
                        $bonusTurn['extra_attack'] = true;
                        $playerHp = $bonusTurn['playerHp'];
                        $bonusTarget['hp'] = $bonusTurn['enemyHp'];

                        if (!empty($bonusTurn['cc_applied'])) {
                            $bonusTarget['cc_turns'] = max($bonusTarget['cc_turns'] ?? 0, (int) $bonusTurn['cc_applied']['duration']);
                        }

                        $bonusTurn['target_index'] = $bonusIdx;
                        $bonusTurn['target_name'] = $bonusTarget['name'];
                        $bonusTurn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                        $bonusTurn['state'] = [
                            'dots' => $this->activeDots,
                            'buffs' => $this->activeBuffs,
                            'cooldowns' => $this->activeCooldowns,
                        ];

                        $turns[] = $bonusTurn;
                        $turnCount++;
                    }
                    unset($bonusTarget);
                }
            }
        }

        return $turns;
    }

    private function anyMonsterAlive(array $monsters): bool
    {
        foreach ($monsters as $m) {
            if (($m['hp'] ?? 0) > 0) return true;
        }
        return false;
    }

    private function getMonstersStateSummary(array $monsters): array
    {
        return array_map(function($m) {
            return [
                'id' => $m['id'],
                'name' => $m['name'],
                'hp' => $m['hp'],
                'maxHp' => $m['maxHp'],
                'level' => $m['level'],
                'stats' => $m['stats'] ?? [],
                'avatar' => $m['avatar'] ?? null,
                'rank' => $m['rank'] ?? 'regular',
                'tier_label' => $m['tier_label'] ?? null,
                'cc_turns' => $m['cc_turns'] ?? 0,
            ];
        }, $monsters);
    }

    private function selectTargetMonster(array $monsters, string $tactic): ?int
    {
        $aliveIndices = [];
        foreach ($monsters as $idx => $m) {
            if (($m['hp'] ?? 0) > 0) {
                $aliveIndices[] = $idx;
            }
        }

        if (empty($aliveIndices)) {
            return null;
        }

        if ($tactic === 'highest_hp') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'lowest_hp') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                return $monsters[$a]['hp'] <=> $monsters[$b]['hp'];
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'highest_att') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                $atkA = $monsters[$a]['stats']['atk'] ?? 0;
                $atkB = $monsters[$b]['stats']['atk'] ?? 0;
                if ($atkA === $atkB) {
                    return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
                }
                return $atkB <=> $atkA;
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'highest_def') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                $defA = $monsters[$a]['stats']['def'] ?? 0;
                $defB = $monsters[$b]['stats']['def'] ?? 0;
                if ($defA === $defB) {
                    return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
                }
                return $defB <=> $defA;
            });
            return $aliveIndices[0];
        }

        // Default 'random'
        return $aliveIndices[array_rand($aliveIndices)];
    }
}
