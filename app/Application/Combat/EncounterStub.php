<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

class EncounterStub
{
    public function generateFakeEncounter(Character $character, Map $map): array
    {
        // Pick random monster from map
        $monster = Monster::where('map_id', $map->id)
            ->inRandomOrder()
            ->first();

        if (!$monster) {
            // Fallback monster if none exists
            $monster = (object) [
                'name' => 'Dziki Przeciwnik',
                'level' => $character->level,
                'max_hp' => 100,
                'attack' => 15,
                'defense' => 5,
                'agility' => 10,
                'intelligence' => 5,
                'crit_chance' => 0.1,
                'dodge_chance' => 0.05,
            ];
        }

        // Character stats from attributes
        $characterStats = $character->attributes ?? ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5];
        $characterHp = ($characterStats['vit'] ?? 5) * 20 + 80; // VIT * 20 + base 80
        $characterAtk = ($characterStats['str'] ?? 5) * 3 + 10; // STR * 3 + base 10

        // Determine who goes first (higher AGI)
        $characterAgi = $characterStats['agi'] ?? 5;
        $monsterAgi = $monster->agility ?? 10;
        $first = $characterAgi >= $monsterAgi ? 'player' : 'enemy';

        // Generate fake turn sequence
        $turns = $this->generateFakeTurns($characterHp, $monster->max_hp ?? 100, $characterAtk, $monster->attack ?? 15);

        // Determine result based on who dies first
        $result = $this->determineFakeResult($turns);

        return [
            'player' => [
                'name' => $character->name,
                'level' => $character->level,
                'hp' => $characterHp,
                'maxHp' => $characterHp,
                'avatar' => $character->avatar ? asset("img/avatars/{$character->avatar}.png") : null,
                'stats' => [
                    'str' => $characterStats['str'] ?? 5,
                    'int' => $characterStats['int'] ?? 5,
                    'vit' => $characterStats['vit'] ?? 5,
                    'agi' => $characterStats['agi'] ?? 5,
                ],
            ],
            'enemy' => [
                'name' => $monster->name,
                'level' => $monster->level ?? 1,
                'hp' => $monster->max_hp ?? 100,
                'maxHp' => $monster->max_hp ?? 100,
                'image' => asset('img/monsters/placeholder.png'), // Placeholder for now
                'stats' => [
                    'atk' => $monster->attack ?? 15,
                    'def' => $monster->defense ?? 5,
                    'agi' => $monster->agility ?? 10,
                    'int' => $monster->intelligence ?? 5,
                ],
            ],
            'first' => $first,
            'turns' => $turns,
            'result' => $result,
        ];
    }

    private function generateFakeTurns(int $playerHp, int $enemyHp, int $playerAtk, int $enemyAtk): array
    {
        $turns = [];
        $currentPlayerHp = $playerHp;
        $currentEnemyHp = $enemyHp;
        $turnNo = 1;
        $maxTurns = 20; // Prevent infinite loops

        while ($currentPlayerHp > 0 && $currentEnemyHp > 0 && $turnNo <= $maxTurns) {
            // Player turn
            if ($currentPlayerHp > 0) {
                $damage = rand(max(1, $playerAtk - 5), $playerAtk + 3);
                $isCrit = rand(1, 100) <= 10; // 10% crit chance
                $isMiss = rand(1, 100) <= 5; // 5% miss chance

                if ($isMiss) {
                    $turns[] = [
                        'actor' => 'player',
                        'type' => 'miss',
                        'value' => 0,
                        'crit' => false,
                        'enemyHp' => $currentEnemyHp,
                    ];
                } else {
                    if ($isCrit) {
                        $damage = (int)($damage * 1.5);
                    }
                    $currentEnemyHp = max(0, $currentEnemyHp - $damage);

                    $turns[] = [
                        'actor' => 'player',
                        'type' => 'hit',
                        'value' => $damage,
                        'crit' => $isCrit,
                        'enemyHp' => $currentEnemyHp,
                    ];
                }
            }

            if ($currentEnemyHp <= 0) break;

            // Enemy turn
            if ($currentEnemyHp > 0) {
                $damage = rand(max(1, $enemyAtk - 5), $enemyAtk + 3);
                $isCrit = rand(1, 100) <= 8; // 8% crit chance
                $isMiss = rand(1, 100) <= 7; // 7% miss chance

                if ($isMiss) {
                    $turns[] = [
                        'actor' => 'enemy',
                        'type' => 'miss',
                        'value' => 0,
                        'crit' => false,
                        'playerHp' => $currentPlayerHp,
                    ];
                } else {
                    if ($isCrit) {
                        $damage = (int)($damage * 1.5);
                    }
                    $currentPlayerHp = max(0, $currentPlayerHp - $damage);

                    $turns[] = [
                        'actor' => 'enemy',
                        'type' => 'hit',
                        'value' => $damage,
                        'crit' => $isCrit,
                        'playerHp' => $currentPlayerHp,
                    ];
                }
            }

            $turnNo++;
        }

        return $turns;
    }

    private function determineFakeResult(array $turns): string
    {
        $lastTurn = end($turns);

        if (!$lastTurn) {
            return 'win'; // Default if no turns
        }

        // Check if enemy died (enemyHp reached 0)
        if (isset($lastTurn['enemyHp']) && $lastTurn['enemyHp'] <= 0) {
            return 'win';
        }

        // Check if player died (playerHp reached 0)
        if (isset($lastTurn['playerHp']) && $lastTurn['playerHp'] <= 0) {
            return 'lose';
        }

        // Default to win if unclear
        return 'win';
    }
}
