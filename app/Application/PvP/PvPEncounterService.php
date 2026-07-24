<?php

namespace App\Application\PvP;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PvPEncounterService
{
    /**
     * Start a new PvP encounter between two characters.
     */
    public function startEncounter(Character $attacker, Character $defender): Result
    {
        try {
            return DB::transaction(function () use ($attacker, $defender) {
                $pvpEncounter = PvpEncounter::create([
                    'attacker_character_id' => $attacker->id,
                    'defender_character_id' => $defender->id,
                    'state' => 'pending',
                    'attacker_snapshot' => $attacker->createSnapshot(),
                    'defender_snapshot' => $defender->createSnapshot(),
                ]);

                // Simulate immediately
                $simResult = $this->simulate($pvpEncounter);
                
                if ($simResult->isError()) {
                    return $simResult;
                }

                return Result::ok($pvpEncounter->fresh());
            });
        } catch (\Exception $e) {
            Log::error('Start encounter failed', ['error' => $e->getMessage()]);
            return Result::error('START_FAILED', 'Nie udało się rozpocząć pojedynku.');
        }
    }

    /**
     * Simulate a PvP fight between two character snapshots.
     * Both sides use snapshot data (frozen stats) not live character data.
     */
    public function simulate(PvpEncounter $pvpEncounter): Result
    {
        // Load snapshots
        $attacker = $pvpEncounter->attacker_snapshot;
        $defender = $pvpEncounter->defender_snapshot;
        
        if (!$attacker || !$defender) {
            return Result::error('MISSING_SNAPSHOT', 'Brak snapshotów postaci do symulacji PvP');
        }

        try {
            return DB::transaction(function () use ($pvpEncounter, $attacker, $defender) {
                // Initialize HP from snapshots
                $attackerHp = $attacker['max_hp'];
                $defenderHp = $defender['max_hp'];
                $attackerMaxHp = $attackerHp;
                $defenderMaxHp = $defenderHp;

                // Determine initiative from AGI
                $attackerAgi = $attacker['attributes']['agi'] ?? 0;
                $defenderAgi = $defender['attributes']['agi'] ?? 0;
                $attackerFirst = $attackerAgi >= $defenderAgi;

                // Simulate combat
                $turns = $this->simulateCombat($attacker, $defender, $attackerHp, $defenderHp, $attackerFirst);

                $lastTurn = end($turns);
                $finalDefenderHp = $lastTurn ? $lastTurn['defenderHp'] : $defenderHp;
                $finalAttackerHp = $lastTurn ? $lastTurn['attackerHp'] : $attackerHp;

                // Determine winner
                $winnerId = null;
                if ($finalDefenderHp <= 0) {
                    $winnerId = $attacker['character_id'];
                } elseif ($finalAttackerHp <= 0) {
                    $winnerId = $defender['character_id'];
                } else {
                    // Timeout - whoever has more HP% wins
                    $attackerHpPct = $finalAttackerHp / $attackerMaxHp;
                    $defenderHpPct = $finalDefenderHp / $defenderMaxHp;
                    $winnerId = $attackerHpPct >= $defenderHpPct 
                        ? $attacker['character_id'] 
                        : $defender['character_id'];
                }

                $isAttackerWinner = ($winnerId === $attacker['character_id']);

                // Calculate ELO changes
                $eloAction = new CalculateEloAction();
                $eloResult = $eloAction->execute(
                    $pvpEncounter->attacker->elo ?? 1000,
                    $pvpEncounter->defender->elo ?? 1000,
                    $isAttackerWinner
                );

                // Arena tokens reward (only winner gets tokens)
                $tokensReward = $isAttackerWinner ? 10 : 3; // Winner gets 10, loser gets 3 consolation

                $combatData = [
                    'attacker_max_hp' => $attackerMaxHp,
                    'defender_max_hp' => $defenderMaxHp,
                    'attacker_first' => $attackerFirst,
                    'completed_at' => now()->toISOString(),
                ];

                // Update PvP encounter
                $pvpEncounter->markAsFinished(
                    $winnerId,
                    $turns,
                    $combatData,
                    $eloResult['attacker_change'],
                    $eloResult['defender_change'],
                    $tokensReward
                );

                // Apply ELO changes to characters
                $pvpEncounter->attacker->increment('elo', $eloResult['attacker_change']);
                $pvpEncounter->defender->increment('elo', $eloResult['defender_change']);
                
                // Ensure ELO doesn't go below 0
                if ($pvpEncounter->attacker->elo < 0) $pvpEncounter->attacker->update(['elo' => 0]);
                if ($pvpEncounter->defender->elo < 0) $pvpEncounter->defender->update(['elo' => 0]);

                // Update leagues
                $pvpEncounter->attacker->update(['league' => $pvpEncounter->attacker->getLeagueForElo()]);
                $pvpEncounter->defender->update(['league' => $pvpEncounter->defender->getLeagueForElo()]);

                // Give arena tokens to winner
                $winner = $isAttackerWinner ? $pvpEncounter->attacker : $pvpEncounter->defender;
                $winner->increment('arena_tokens', $tokensReward);
                // Give consolation tokens to loser too
                $loser = $isAttackerWinner ? $pvpEncounter->defender : $pvpEncounter->attacker;
                $loser->increment('arena_tokens', 3);

                Log::info('PvP encounter completed', [
                    'pvp_encounter_id' => $pvpEncounter->id,
                    'winner_id' => $winnerId,
                    'attacker_elo_change' => $eloResult['attacker_change'],
                    'defender_elo_change' => $eloResult['defender_change'],
                ]);

                // Build result for UI
                return Result::ok([
                    'attacker' => $attacker,
                    'defender' => $defender,
                    'turns' => $turns,
                    'winner_id' => $winnerId,
                    'attacker_elo_change' => $eloResult['attacker_change'],
                    'defender_elo_change' => $eloResult['defender_change'],
                    'tokens_reward' => $tokensReward,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('PvP simulation failed', [
                'pvp_encounter_id' => $pvpEncounter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return Result::error('PVP_SIMULATION_FAILED', 'Symulacja walki PvP nie powiodła się');
        }
    }

    private function simulateCombat(array $attacker, array $defender, int $attackerHp, int $defenderHp, bool $attackerFirst): array
    {
        $turns = [];
        $turnCount = 0;
        $maxTurns = 50;

        $state = [
            'attacker' => ['cooldowns' => [], 'effects' => []],
            'defender' => ['cooldowns' => [], 'effects' => []],
        ];

        while ($attackerHp > 0 && $defenderHp > 0 && $turnCount < $maxTurns) {
            $isAttackerTurn = $attackerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);
            $actorKey = $isAttackerTurn ? 'attacker' : 'defender';
            $targetKey = $isAttackerTurn ? 'defender' : 'attacker';

            $turn = $this->performAttack($attacker, $defender, $attackerHp, $defenderHp, $actorKey, $state[$actorKey], $state[$targetKey]);
            
            $attackerHp = $turn['attackerHp'];
            $defenderHp = $turn['defenderHp'];
            $turns[] = $turn;

            // Decrement cooldowns
            foreach ($state[$actorKey]['cooldowns'] as &$cd) {
                if ($cd > 0) $cd--;
            }

            $turnCount++;
        }

        return $turns;
    }

    private function performAttack(array $attackerSnapshot, array $defenderSnapshot, int $attackerHp, int $defenderHp, string $actor, array &$actorState, array &$targetState): array
    {
        $actingSnapshot = $actor === 'attacker' ? $attackerSnapshot : $defenderSnapshot;
        $targetSnapshot = $actor === 'attacker' ? $defenderSnapshot : $attackerSnapshot;

        // Determine if a skill can be used
        $skills = $actingSnapshot['skills'] ?? [];
        $weaponType = $actingSnapshot['weapon_type'] ?? 'barehands';
        
        $skillToUse = null;
        foreach ($skills as $skill) {
            if ($skill['required_weapon_type'] === 'all' || $skill['required_weapon_type'] === $weaponType) {
                $cd = $actorState['cooldowns'][$skill['id']] ?? 0;
                if ($cd <= 0) {
                    $skillToUse = $skill;
                    break;
                }
            }
        }

        // Calculate damage using snapshot equipment stats
        $strength = $actingSnapshot['attributes']['str'] ?? 1;
        $eqStats = $actingSnapshot['equipment_stats'] ?? [];
        
        $baseDmgMin = 10 + ($strength * 2) + ($actingSnapshot['level'] * 1) + ($eqStats['attack_min'] ?? 0);
        $baseDmgMax = 10 + ($strength * 2) + ($actingSnapshot['level'] * 1) + ($eqStats['attack_max'] ?? 0);
        if ($baseDmgMax < $baseDmgMin) $baseDmgMax = $baseDmgMin;
        
        $damage = mt_rand($baseDmgMin, $baseDmgMax);
        
        if ($skillToUse) {
            $actorState['cooldowns'][$skillToUse['id']] = $skillToUse['base_cooldown'];
            $bonus = $skillToUse['base_value'] + ($skillToUse['level'] * $skillToUse['scaling_value']);
            if ($skillToUse['effect_type'] === 'damage') {
                $damage = (int)($damage * (1 + ($bonus / 100)));
            } elseif ($skillToUse['effect_type'] === 'poison') {
                $targetState['effects'][$skillToUse['id']] = [
                    'type' => 'poison',
                    'name' => $skillToUse['name'] ?? 'Otrucie',
                    'icon' => $skillToUse['icon'] ?? null,
                    'description' => $skillToUse['description'] ?? 'Zadaje obrażenia od otrucia co turę.',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            }
        }

        // Process DoT for target during this exchange
        $dotDamage = 0;
        $dotType = null;
        if (!empty($targetState['effects'])) {
            $targetMaxHp = $actor === 'attacker' ? $defenderSnapshot['max_hp'] : $attackerSnapshot['max_hp'];
            foreach ($targetState['effects'] as $id => &$eff) {
                if ($eff['duration'] > 0 && ($eff['type'] === 'poison' || $eff['type'] === 'fire')) {
                    $dmg = max(1, (int)($targetMaxHp * ($eff['value'] / 100)));
                    $dotDamage += $dmg;
                    $dotType = $eff['type'];

                    $eff['duration']--;
                }
            }
            $targetState['effects'] = array_filter($targetState['effects'], fn($e) => $e['duration'] > 0);
        }

        // Defender's defense
        $defVit = $targetSnapshot['attributes']['vit'] ?? 1;
        $defEq = $targetSnapshot['equipment_stats'] ?? [];
        $defense = $defVit + ($targetSnapshot['level'] / 2) + ($defEq['defense'] ?? 0);
        $damage = max(1, $damage - ($defense / 2));

        // Crit check
        $agi = $actingSnapshot['attributes']['agi'] ?? 1;
        $critChance = min(0.3, 0.05 + ($agi * 0.01) + (($eqStats['crit_chance'] ?? 0) / 100));
        $isCrit = mt_rand(1, 100) <= ($critChance * 100);

        // Miss check
        $isMiss = mt_rand(1, 100) <= 5;

        if ($isMiss) {
            $turn = [
                'actor' => $actor,
                'type' => 'miss',
                'value' => 0,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => false,
            ];
            if ($actor === 'attacker') {
                $turn['attackerHp'] = $attackerHp;
                $turn['defenderHp'] = max(0, $defenderHp - $dotDamage);
            } else {
                $turn['attackerHp'] = max(0, $attackerHp - $dotDamage);
                $turn['defenderHp'] = $defenderHp;
            }
            return $turn;
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
        }

        $turn = [
            'actor' => $actor,
            'type' => $skillToUse ? 'skill' : 'hit',
            'value' => (int)$damage,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
            'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => $isCrit,
        ];
        
        if ($skillToUse) {
            $turn['skill_id'] = $skillToUse['id'];
            $turn['skill_name'] = $skillToUse['name'];
        }

        if ($actor === 'attacker') {
            $newDefenderHp = max(0, $defenderHp - (int)$damage - $dotDamage);
            $turn['attackerHp'] = $attackerHp;
            $turn['defenderHp'] = $newDefenderHp;
        } else {
            $newAttackerHp = max(0, $attackerHp - (int)$damage - $dotDamage);
            $turn['attackerHp'] = $newAttackerHp;
            $turn['defenderHp'] = $defenderHp;
        }

        return $turn;
    }
}
