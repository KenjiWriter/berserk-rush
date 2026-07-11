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
                    $monsters = $map->monsters;

                    if ($monsters->isEmpty()) {
                        return Result::error('NO_MONSTERS', 'Brak potworów na tej mapie');
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
                    $hasEncounter = Encounter::where('character_id', $character->id)
                        ->where('monster_id', $monster->id)
                        ->where('map_id', $map->id)
                        ->where('created_at', '>=', $activeBoss->created_at)
                        ->exists();

                    if ($hasEncounter) {
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
                $encounter->load(['character', 'monster']);

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
                $isWorldBoss = ($monster->rank === 'worldboss');
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

                // Update combat_data
                $combatData = array_merge($encounter->combat_data ?? [], [
                    'player_max_hp' => $playerMaxHp,
                    'monster_max_hp' => $monsterMaxHp,
                    'completed_at' => now()->toISOString(),
                    'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    'rewards' => [
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                    ]
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
                    'result' => $isWorldBoss ? 'finished' : ($winner === 'player' ? 'win' : 'loss'),
                    'rewards' => [
                        'gold' => $goldReward,
                        'xp' => $xpReward,
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                        'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    ]
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
        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $monster->stats['agi'] ?? $monster->level;
        $playerFirst = $playerAgi >= $monsterAgi;

        $turns = [];
        $turnCount = 0;
        $maxTurns = $isWorldBoss ? 20 : 50;

        while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
            $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            if ($isPlayerTurn) {
                $turn = $this->playerAttack($character, $monster, $playerHp, $monsterHp);
                $monsterHp = $turn['enemyHp'];
            } else {
                $turn = $this->monsterAttack($monster, $character, $playerHp, $monsterHp);
                $playerHp = $turn['playerHp'];
            }

            $turns[] = $turn;
            $turnCount++;
        }

        return $turns;
    }

    private function playerAttack(Character $character, Monster $monster, int $playerHp, int $monsterHp): array
    {
        $damage = $this->calculateDamage($character, $monster);
        $isCrit = $this->rollCritical($character);
        $isMiss = $this->rollMiss();

        if ($isMiss) {
            return [
                'actor' => 'player',
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $monsterHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
        }

        $newMonsterHp = max(0, $monsterHp - $damage);

        return [
            'actor' => 'player',
            'type' => 'hit',
            'value' => $damage,
            'crit' => $isCrit,
            'playerHp' => $playerHp,
            'enemyHp' => $newMonsterHp,
        ];
    }

    private function monsterAttack(Monster $monster, Character $character, int $playerHp, int $monsterHp): array
    {
        $damage = $this->calculateMonsterDamage($monster, $character);
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
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        return [
            'actor' => 'enemy',
            'type' => 'hit',
            'value' => $damage,
            'crit' => $isCrit,
            'playerHp' => $newPlayerHp,
            'enemyHp' => $monsterHp,
        ];
    }



    private function calculateDamage(Character $character, Monster $monster): int
    {
        $strength = $character->getTotalAttributes()['str'] ?? 1;
        $eq = $character->getEquipmentStats();
        
        $baseDamageMin = 10 + ($strength * 2) + ($character->level * 1) + $eq['attack_min'];
        $baseDamageMax = 10 + ($strength * 2) + ($character->level * 1) + $eq['attack_max'];
        
        // Ensure max is at least min
        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }
        
        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $defense = $monster->stats['def'] ?? $monster->level;

        return max(1, $damage - ($defense / 2));
    }

    private function calculateMonsterDamage(Monster $monster, Character $character): int
    {
        $baseDamage = $monster->stats['atk'] ?? $monster->level * 2;
        $vitality = $character->getTotalAttributes()['vit'] ?? 1;
        $eq = $character->getEquipmentStats();
        $defense = $vitality + ($character->level / 2) + $eq['defense'];

        return max(1, $baseDamage - ($defense / 2));
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
