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

        while ($attackerHp > 0 && $defenderHp > 0 && $turnCount < $maxTurns) {
            $isAttackerTurn = $attackerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            $turn = $this->performAttack($attacker, $defender, $attackerHp, $defenderHp, $isAttackerTurn ? 'attacker' : 'defender');
            
            $attackerHp = $turn['attackerHp'];
            $defenderHp = $turn['defenderHp'];

            $turns[] = $turn;
            $turnCount++;
        }

        return $turns;
    }

    private function performAttack(array $attackerSnapshot, array $defenderSnapshot, int $attackerHp, int $defenderHp, string $actor): array
    {
        $actingSnapshot = $actor === 'attacker' ? $attackerSnapshot : $defenderSnapshot;
        $targetSnapshot = $actor === 'attacker' ? $defenderSnapshot : $attackerSnapshot;

        // Calculate damage using snapshot equipment stats
        $strength = $actingSnapshot['attributes']['str'] ?? 1;
        $eqStats = $actingSnapshot['equipment_stats'] ?? [];
        
        $baseDmgMin = 10 + ($strength * 2) + ($actingSnapshot['level'] * 1) + ($eqStats['attack_min'] ?? 0);
        $baseDmgMax = 10 + ($strength * 2) + ($actingSnapshot['level'] * 1) + ($eqStats['attack_max'] ?? 0);
        if ($baseDmgMax < $baseDmgMin) $baseDmgMax = $baseDmgMin;
        
        $damage = mt_rand($baseDmgMin, $baseDmgMax);
        
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
            return [
                'actor' => $actor,
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'attackerHp' => $attackerHp,
                'defenderHp' => $defenderHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
        }

        if ($actor === 'attacker') {
            $newDefenderHp = max(0, $defenderHp - (int)$damage);
            return [
                'actor' => 'attacker',
                'type' => 'hit',
                'value' => (int)$damage,
                'crit' => $isCrit,
                'attackerHp' => $attackerHp,
                'defenderHp' => $newDefenderHp,
            ];
        } else {
            // Actor is 'defender' attacking, so attacker takes damage
            $newAttackerHp = max(0, $attackerHp - (int)$damage);
            return [
                'actor' => 'defender',
                'type' => 'hit',
                'value' => (int)$damage,
                'crit' => $isCrit,
                'attackerHp' => $newAttackerHp,
                'defenderHp' => $defenderHp,
            ];
        }
    }
}
