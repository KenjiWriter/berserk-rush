<?php

namespace App\Application\Combat;

use App\Application\Shared\Result;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Application\Loot\DropService;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;

class EncounterService
{
    private array $activeCooldowns = [];
    private array $activeDots = [];
    private $equippedSkills = null;
    private array $activeBuffs = [];

    public function start(Character $character, Map $map, ?Monster $forcedMonster = null): Result
    {
        Log::info('EncounterService::start called', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'map_id' => $map->id,
            'map_name' => $map->name,
            'forced_monster_id' => $forcedMonster?->id,
        ]);

        try {
            return DB::transaction(function () use ($character, $map, $forcedMonster) {
                Log::info('Starting transaction for encounter');

                $monster = $forcedMonster;

                if (!$monster) {
                    // Get monsters for this map
                    $monsters = $map->monsters->whereNotIn('rank', [
                        \App\Domain\Combat\Enums\MonsterRank::WORLDBOSS,
                        \App\Domain\Combat\Enums\MonsterRank::BOSS
                    ]);

                    if ($monsters->isEmpty()) {
                        return Result::error('NO_MONSTERS', 'Brak zwykłych potworów na tej mapie');
                    }

                    // Get random monster
                    $monster = $monsters->random();
                }

                Log::info('Selected monster for encounter', [
                    'monster_id' => $monster->id,
                    'monster_name' => $monster->name,
                    'monster_level' => $monster->level
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

                // Determine turn order
                $totalAttributes = $character->getTotalAttributes();
                $playerAgi = $totalAttributes['agi'] ?? 0;
                $monsterAgi = $monster->stats['agi'] ?? $monster->level;
                $playerFirst = $playerAgi >= $monsterAgi;

                // Create encounter - używając ended_at zamiast finished_at
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
                        'created_at' => now()->toISOString()
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

                // Initialize HP
                $playerHp = $character->getMaxHp();
                $monsterHp = $monster->stats['hp'] ?? $monster->level * 20;
                $playerMaxHp = $playerHp;
                $monsterMaxHp = $monsterHp;

                Log::info('Combat stats initialized', [
                    'player_hp' => $playerHp,
                    'monster_hp' => $monsterHp,
                ]);

                // Simulate combat
                $isWorldBoss = ($monster->rank?->value === 'worldboss');
                if ($isWorldBoss) {
                    $monsterMaxHp = 999999999;
                    $monsterHp = $monsterMaxHp;
                }

                $turns = $this->simulateCombat($character, $monster, $playerHp, $monsterHp, $isWorldBoss);

                $lastTurn = end($turns);
                $finalMonsterHp = $lastTurn ? $lastTurn['enemyHp'] : $monsterHp;
                
                // Determine winner and rewards
                $goldRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];
                $xpRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];

                if ($isWorldBoss) {
                    $winner = 'enemy'; // Worldboss always wins/survives
                    $damageDealt = max(0, $monsterMaxHp - $finalMonsterHp);
                    
                    // Skalowanie nagród używając spłaszczonej krzywej (np. pierwiastek) by zapobiec nieskończonemu wzrostowi
                    $baseGold = max(10, (int)ceil(pow($damageDealt, 0.7)));
                    $baseXp = max(20, (int)ceil(pow($damageDealt, 0.75)));

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
                    $dropResult = $dropService->rollAndApplyRewards($encounter);

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
                            'atk' => $monster->stats['atk'] ?? $monster->level * 2,
                            'def' => $monster->stats['def'] ?? $monster->level,
                            'agi' => $monster->stats['agi'] ?? $monster->level,
                            'hp' => $monster->stats['hp'] ?? $monster->level * 20,
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

    private function simulateCombat(Character $character, Monster $monster, int $playerHp, int $monsterHp, bool $isWorldBoss = false): array
    {
        // Reset state for new combat
        $this->activeCooldowns = [];
        $this->activeDots = [];
        $this->activeBuffs = [];
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

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $monster->stats['agi'] ?? $monster->level;
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

                $turn = $this->playerAttack($character, $monster, $playerHp, $monsterHp, $monsterMaxHp);
                $monsterHp = $turn['enemyHp'];
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

    private function playerAttack(Character $character, Monster $monster, int $playerHp, int $monsterHp, int $monsterMaxHp): array
    {
        $weaponName = '';
        foreach ($character->equippedItems as $item) {
            if ($item->template->type === 'weapon') {
                $weaponName = mb_strtolower($item->template->name, 'UTF-8');
                break;
            }
        }

        $usedSkill = null;

        // Try to use skill
        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active' && ($this->activeCooldowns[$cs->id] ?? 0) <= 0) {
                // Check weapon requirement
                $reqWep = $cs->skill->required_weapon_type;
                if (!empty($reqWep)) {
                    if ($reqWep === 'bow' && strpos($weaponName, 'łuk') === false && strpos($weaponName, 'bow') === false) continue;
                    if ($reqWep === 'sword' && strpos($weaponName, 'miecz') === false && strpos($weaponName, 'ostrze') === false) continue;
                    if ($reqWep === 'axe' && strpos($weaponName, 'topór') === false && strpos($weaponName, 'rozłupywacz') === false && strpos($weaponName, 'maczuga') === false) continue;
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
        foreach ($this->activeDots as $k => $dot) {
            if ($dot['type'] === 'poison') {
                $dmg = (int)($monsterHp * $dot['value']);
            } else if ($dot['type'] === 'fire') {
                $dmg = (int)($monsterMaxHp * $dot['value']);
            } else {
                $dmg = 0;
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

            $skillMultiplier = 1.0;
            if ($csSkill->effect_type === 'direct_dmg') {
                $skillMultiplier = $effVal;
            }
            
            $damageData = $this->calculateDamage($character, $monster);
            $damage = (int)($damageData['total'] * $skillMultiplier);
            $baseDamage = (int)($damageData['base'] * $skillMultiplier);
            $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
            
            // Active Buffs Application
            if (isset($this->activeBuffs['phys_dmg'])) {
                $damage = (int)($damage * (1 + $this->activeBuffs['phys_dmg']['value']));
                $baseDamage = (int)($baseDamage * (1 + $this->activeBuffs['phys_dmg']['value']));
            }

            $isCrit = $this->rollCritical($character);
            if ($isCrit) {
                $damage = (int)($damage * 1.5);
                $baseDamage = (int)($baseDamage * 1.5);
                $bonusDamage = (int)($bonusDamage * 1.5);
            }

            $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

            return [
                'actor' => 'player',
                'type' => 'skill',
                'skill_name' => $csSkill->name,
                'effect_type' => $csSkill->effect_type,
                'value' => $damage,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => $isCrit,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
                'baseDamage' => $baseDamage,
                'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
            ];
        }

        // Standard attack
        $damageData = $this->calculateDamage($character, $monster);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $bonusDamage = $damageData['bonus'];

        // Apply global buffs to normal attacks
        if (isset($this->activeBuffs['phys_dmg'])) {
            $damage = (int)($damage * (1 + $this->activeBuffs['phys_dmg']['value']));
            $baseDamage = (int)($baseDamage * (1 + $this->activeBuffs['phys_dmg']['value']));
        }

        $isCrit = $this->rollCritical($character);
        $isMiss = $this->rollMiss();

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

        if ($bonusDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['bonusDamage'] = $bonusDamage;
        }

        return $turn;
    }

    private function monsterAttack(Monster $monster, Character $character, int $playerHp, int $monsterHp): array
    {
        $damageData = $this->calculateMonsterDamage($monster, $character);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $resistDamage = $damageData['resist'];

        $isCrit = $this->rollMonsterCritical($monster);
        $isMiss = $this->rollMiss();

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
        $strength = $character->getTotalAttributes()['str'] ?? 1;
        $eq = $character->getEquipmentStats();
        
        $baseDamageMin = 10 + ($strength * 2) + ($character->level * 1) + ($eq['attack_min'] ?? 0);
        $baseDamageMax = 10 + ($strength * 2) + ($character->level * 1) + ($eq['attack_max'] ?? 0);
        
        // Ensure max is at least min
        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }
        
        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $defense = $monster->stats['def'] ?? $monster->level;

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

        return [
            'base' => $baseDamage,
            'bonus' => $bonusDamage,
            'total' => $baseDamage + $bonusDamage
        ];
    }

    private function calculateMonsterDamage(Monster $monster, Character $character): array
    {
        $baseDamage = $monster->stats['atk'] ?? $monster->level * 2;
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

    private function rollCritical(Character $character): bool
    {
        $agility = $character->getTotalAttributes()['agi'] ?? 1;
        $eq = $character->getEquipmentStats();
        $critChance = min(0.3, 0.05 + ($agility * 0.01) + ($eq['crit_chance'] / 100));
        return mt_rand(1, 100) <= ($critChance * 100);
    }

    private function rollMonsterCritical(Monster $monster): bool
    {
        $agility = $monster->stats['agi'] ?? $monster->level;
        $critChance = min(0.2, 0.03 + ($agility * 0.008));
        return mt_rand(1, 100) <= ($critChance * 100);
    }

    private function rollMiss(): bool
    {
        return mt_rand(1, 100) <= 5; // 5% miss chance
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
        $baseXp = 20 + ($monster->level * 3);

        if ($levelDiff > 0) {
            $baseXp *= (1 + ($levelDiff * 0.1));
        } elseif ($levelDiff < -5) {
            $baseXp *= max(0.1, 1 + ($levelDiff * 0.05));
        }
        
        $baseXp = (int)$baseXp;

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
}
