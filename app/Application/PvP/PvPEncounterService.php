<?php

namespace App\Application\PvP;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
use App\Application\Rankings\WeeklyRankingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PvPEncounterService
{
    // Procki z ekwipunku (2026-07-29): zduplikowane z EncounterService::rollEquipmentProcs()
    // (patrz komentarz tam) - tu obie strony mają ekwipunek, więc odporność
    // (resist_poison/resist_stun) przeciwnika faktycznie redukuje szansę na proc.
    private const EQUIPMENT_POISON_DURATION = 3;
    private const EQUIPMENT_POISON_VALUE = 0.03;
    private const EQUIPMENT_STUN_DURATION = 1;

    private function buildChampionBonuses(Character $character): array
    {
        return [
            'phys_dmg_pct' => $character->getChampionBonusPercent('phys_dmg_pct'),
            'magic_dmg_pct' => $character->getChampionBonusPercent('magic_dmg_pct'),
            'mana_regen_pct' => $character->getChampionBonusPercent('mana_regen_pct'),
            'dodge_pct' => $character->getChampionBonusPercent('dodge_pct'),
            'accuracy_pct' => $character->getChampionBonusPercent('accuracy_pct'),
            'dmg_reduction_pct' => $character->getChampionBonusPercent('dmg_reduction_pct'),
        ];
    }

    private function rollEquipmentProcs(array $eq, array $resistEq): array
    {
        $poisonChance = max(0, ($eq['poison_chance'] ?? 0) - ($resistEq['resist_poison'] ?? 0));
        $bleedChance = max(0, $eq['bleed_chance'] ?? 0);
        $stunChance = max(0, ($eq['stun_chance'] ?? 0) - ($resistEq['resist_stun'] ?? 0));
        $doubleStrikeChance = max(0, $eq['double_strike_chance'] ?? 0);
        $magicInfusionChance = max(0, $eq['magic_infusion_chance'] ?? 0);

        $dot = null;
        $cc = null;
        $doubleStrike = false;
        $infusionArmorPen = 0;

        if ($poisonChance > 0 && mt_rand(1, 100) <= $poisonChance) {
            $dot = [
                'type' => 'poison',
                'name' => 'Zatrucie (Ekwipunek)',
                'duration' => self::EQUIPMENT_POISON_DURATION,
                'value' => self::EQUIPMENT_POISON_VALUE,
            ];
        } elseif ($bleedChance > 0 && mt_rand(1, 100) <= $bleedChance) {
            $dot = [
                'type' => 'bleed',
                'name' => 'Krwawienie (Ekwipunek)',
                'duration' => self::EQUIPMENT_POISON_DURATION,
                'value' => self::EQUIPMENT_POISON_VALUE,
            ];
        }

        if ($stunChance > 0 && mt_rand(1, 100) <= $stunChance) {
            $cc = ['type' => 'stun', 'duration' => self::EQUIPMENT_STUN_DURATION];
        }

        if ($doubleStrikeChance > 0 && mt_rand(1, 100) <= $doubleStrikeChance) {
            $doubleStrike = true;
        }

        if ($magicInfusionChance > 0 && mt_rand(1, 100) <= $magicInfusionChance) {
            $infusionRoll = mt_rand(1, 4);
            if ($infusionRoll === 1 && !$dot) {
                $dot = [
                    'type' => 'bleed',
                    'name' => 'Infuzja Krwawienia',
                    'duration' => self::EQUIPMENT_POISON_DURATION,
                    'value' => self::EQUIPMENT_POISON_VALUE,
                ];
            } elseif ($infusionRoll === 2 && !$dot) {
                $dot = [
                    'type' => 'poison',
                    'name' => 'Infuzja Otrucia',
                    'duration' => self::EQUIPMENT_POISON_DURATION,
                    'value' => self::EQUIPMENT_POISON_VALUE,
                ];
            } elseif ($infusionRoll === 3) {
                $doubleStrike = true;
            } elseif ($infusionRoll === 4) {
                $infusionArmorPen = 50;
            }
        }

        return [
            'dot' => $dot,
            'cc' => $cc,
            'double_strike' => $doubleStrike,
            'infusion_armor_pen' => $infusionArmorPen,
        ];
    }

    /**
     * Start a new PvP encounter between two characters.
     */
    public function startEncounter(Character $attacker, Character $defender): Result
    {
        if ($attacker->level < 15) {
            return Result::error('LEVEL_TOO_LOW', 'Musisz posiadać co najmniej 15 poziom, aby walczyć na Arenie.');
        }

        if ($defender->level < 15) {
            return Result::error('LEVEL_TOO_LOW', 'Przeciwnik musi posiadać co najmniej 15 poziom, aby walczyć na Arenie.');
        }

        // Clean up stale pending/calculating PvP encounters older than 2 minutes
        PvpEncounter::where('attacker_character_id', $attacker->id)
            ->whereIn('state', ['pending', 'calculating'])
            ->where('created_at', '<', now()->subMinutes(2))
            ->update(['state' => 'error']);

        // Check active or very recent PvP encounter (cooldown 5s to prevent multi-challenge spamming)
        $recentPvP = PvpEncounter::where('attacker_character_id', $attacker->id)
            ->where(function ($query) {
                $query->whereIn('state', ['pending', 'calculating'])
                      ->orWhere('created_at', '>=', now()->subSeconds(5));
            })
            ->exists();

        if ($recentPvP) {
            return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater jest już w trakcie walki lub musisz odczekać chwilę przed kolejnym wyzwaniem.');
        }

        // Check active PvE encounter
        $activePvE = Encounter::where('character_id', $attacker->id)
            ->where('state', 'ongoing')
            ->where('started_at', '>=', now()->subMinutes(2))
            ->exists();

        if ($activePvE) {
            return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater bierze obecnie udział w walce PvE.');
        }

        // Check limit of Arena fights (max 3, +1 per 1h)
        if ($attacker->getRemainingDailyPvpFights() <= 0) {
            $mins = $attacker->getMinutesToNextPvpFight();
            $regenText = $mins ? "Kolejna próba odnowi się za {$mins} min." : "Użyj Zwoju Areny Walki, aby odnowić próbę.";
            return Result::error('DAILY_LIMIT_REACHED', "Wykorzystałeś wszystkie próby na Arenie Walk (0/3). {$regenText}");
        }

        try {
            return DB::transaction(function () use ($attacker, $defender) {
                if (($attacker->daily_pvp_fights_used ?? 0) <= 0 || !$attacker->daily_pvp_fights_last_reset_at) {
                    $attacker->daily_pvp_fights_last_reset_at = now();
                }
                $attacker->increment('daily_pvp_fights_used');
                $attacker->save();

                $pvpEncounter = PvpEncounter::create([
                    'attacker_character_id' => $attacker->id,
                    'defender_character_id' => $defender->id,
                    'state' => 'pending',
                    // Atakujący zawsze walczy tym, co ma aktualnie założone - to on
                    // wybiera moment ataku. Obrońca broni się dedykowanym setem "Arena
                    // PvP" (z fallbackiem per-slot na aktualny ekwipunek - patrz
                    // Character::resolveEffectiveEquipment()), bo może być offline.
                    'attacker_snapshot' => $attacker->createSnapshot(),
                    'defender_snapshot' => $defender->createSnapshot(CharacterEquipmentSetItem::SET_PVP),
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

        // Bonusy z drzewka Mistrzostwa (patrz docs/modules/mastery.md) - snapshoty PvP
        // to płaskie tablice bez dostępu do modelu Character, więc doliczamy je raz
        // przed symulacją zamiast odpytywać bazę na każdą turę.
        $attacker['champion_bonuses'] = $this->buildChampionBonuses($pvpEncounter->attacker);
        $defender['champion_bonuses'] = $this->buildChampionBonuses($pvpEncounter->defender);

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

                // Ranking tygodniowy: zwycięstwa na arenie
                app(WeeklyRankingService::class)->incrementScore($winner->id, 'arena_wins');

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
            'attacker' => [
                'cooldowns' => [],
                'effects' => [],
                'cc_turns' => 0,
                'passives' => ['aura_dmg' => 0, 'extra_attack_chance' => 0],
                'mana' => $attacker['max_mana'] ?? 50,
                'max_mana' => $attacker['max_mana'] ?? 50,
            ],
            'defender' => [
                'cooldowns' => [],
                'effects' => [],
                'cc_turns' => 0,
                'passives' => ['aura_dmg' => 0, 'extra_attack_chance' => 0],
                'mana' => $defender['max_mana'] ?? 50,
                'max_mana' => $defender['max_mana'] ?? 50,
            ],
        ];

        while ($attackerHp > 0 && $defenderHp > 0 && $turnCount < $maxTurns) {
            $isAttackerTurn = $attackerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);
            $actorKey = $isAttackerTurn ? 'attacker' : 'defender';
            $targetKey = $isAttackerTurn ? 'defender' : 'attacker';

            // Regeneracja many co turę dla obu stron (5% max_mana, min 5 MP) + Koncentracja (Mistrzostwo)
            $attackerManaRegenPct = 0.05 + ($attacker['champion_bonuses']['mana_regen_pct'] ?? 0);
            $defenderManaRegenPct = 0.05 + ($defender['champion_bonuses']['mana_regen_pct'] ?? 0);
            $state['attacker']['mana'] = min($state['attacker']['max_mana'], $state['attacker']['mana'] + max(5, (int)ceil($state['attacker']['max_mana'] * $attackerManaRegenPct)));
            $state['defender']['mana'] = min($state['defender']['max_mana'], $state['defender']['mana'] + max(5, (int)ceil($state['defender']['max_mana'] * $defenderManaRegenPct)));

            // Zamrożenie/ogłuszenie: aktor traci turę ataku, ale cooldowny nadal tykają
            if (($state[$actorKey]['cc_turns'] ?? 0) > 0) {
                $state[$actorKey]['cc_turns']--;
                $turns[] = [
                    'actor' => $actorKey,
                    'type' => 'crowd_controlled',
                    'value' => 0,
                    'crit' => false,
                    'attackerHp' => $attackerHp,
                    'defenderHp' => $defenderHp,
                    'attackerMana' => $state['attacker']['mana'],
                    'defenderMana' => $state['defender']['mana'],
                ];

                foreach ($state[$actorKey]['cooldowns'] as &$cd) {
                    if ($cd > 0) $cd--;
                }
                unset($cd);

                $turnCount++;
                continue;
            }

            // Pobranie many i ocena efektów pasywnych na tę turę
            $actorSnapshot = $isAttackerTurn ? $attacker : $defender;
            $state[$actorKey]['passives'] = $this->computePassivesForTurn($actorSnapshot, $state[$actorKey]['mana']);

            $turn = $this->performAttack($attacker, $defender, $attackerHp, $defenderHp, $actorKey, $state[$actorKey], $state[$targetKey]);

            $attackerHp = $turn['attackerHp'];
            $defenderHp = $turn['defenderHp'];
            $turns[] = $turn;

            if (!empty($turn['cc_applied'])) {
                $state[$targetKey]['cc_turns'] = max($state[$targetKey]['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
            }

            // Decrement cooldowns
            foreach ($state[$actorKey]['cooldowns'] as &$cd) {
                if ($cd > 0) $cd--;
            }
            unset($cd);

            $turnCount++;

            // Pasywna szansa na natychmiastowy dodatkowy atak (np. Furia Berserkera - topór)
            if ($attackerHp > 0 && $defenderHp > 0 && $this->rollPassiveChance($state[$actorKey]['passives']['extra_attack_chance'] ?? 0)) {
                $bonusTurn = $this->performAttack($attacker, $defender, $attackerHp, $defenderHp, $actorKey, $state[$actorKey], $state[$targetKey]);
                $bonusTurn['extra_attack'] = true;

                $attackerHp = $bonusTurn['attackerHp'];
                $defenderHp = $bonusTurn['defenderHp'];
                $turns[] = $bonusTurn;

                if (!empty($bonusTurn['cc_applied'])) {
                    $state[$targetKey]['cc_turns'] = max($state[$targetKey]['cc_turns'] ?? 0, (int) $bonusTurn['cc_applied']['duration']);
                }

                $turnCount++;
            }
        }

        return $turns;
    }

    /**
     * Wylicza sumaryczne efekty pasywnych umiejętności (equip_slot 1-3, type = 'passive')
     * z wyposażonego decku danej strony pojedynku PvP (snapshot) na daną turę. Pobiera MP.
     */
    private function computePassivesForTurn(array $snapshot, int &$actorMana): array
    {
        $passives = ['aura_dmg' => 0, 'extra_attack_chance' => 0];
        $weaponType = $snapshot['weapon_type'] ?? 'barehands';

        foreach (($snapshot['skills'] ?? []) as $skill) {
            if (($skill['type'] ?? 'active') !== 'passive') {
                continue;
            }

            $reqWep = $skill['required_weapon_type'] ?? null;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $weaponType) {
                continue;
            }

            $effLevel = max(1, $skill['level'] ?? 1);
            $baseMana = $skill['base_mana_cost'] ?? 0;
            $scalingMana = $skill['scaling_mana_cost'] ?? 0;
            $manaCost = max(0, (int) round($baseMana + (($effLevel - 1) * $scalingMana)));
            $manaCost = $this->applyPetManaReduction($manaCost, $snapshot);

            if ($actorMana < $manaCost) {
                continue; // Brak wystarczającej ilości MP w tej turze
            }

            $actorMana -= $manaCost;

            $effVal = $skill['effective_value'] ?? (($skill['base_value'] ?? 0) + (($effLevel - 1) * ($skill['scaling_value'] ?? 0)));

            if (($skill['effect_type'] ?? null) === 'passive_aura_dmg') {
                $passives['aura_dmg'] += $effVal;
            } elseif (($skill['effect_type'] ?? null) === 'passive_extra_attack') {
                $passives['extra_attack_chance'] = max($passives['extra_attack_chance'], min(0.75, max(0, $effVal)));
            }
        }

        return $passives;
    }

    /**
     * Pasywka Rodzaju Wspomagającego peta (patrz Character::getEquipmentStats(),
     * docs/modules/pets.md) - obniża koszt many o `mana_cost_reduction_pct` z
     * migawki ekwipunku aktora. Ograniczone do 90% czysto zabezpieczająco.
     */
    private function applyPetManaReduction(int $manaCost, array $snapshot): int
    {
        if ($manaCost <= 0) {
            return $manaCost;
        }

        $reductionPct = min(90.0, (float) ($snapshot['equipment_stats']['mana_cost_reduction_pct'] ?? 0));
        if ($reductionPct <= 0) {
            return $manaCost;
        }

        return max(0, (int) round($manaCost * (1 - $reductionPct / 100)));
    }

    private function rollPassiveChance(float $chance): bool
    {
        if ($chance <= 0) {
            return false;
        }

        return mt_rand(1, 10000) <= (int) round($chance * 10000);
    }

    private function performAttack(array $attackerSnapshot, array $defenderSnapshot, int $attackerHp, int $defenderHp, string $actor, array &$actorState, array &$targetState): array
    {
        $actingSnapshot = $actor === 'attacker' ? $attackerSnapshot : $defenderSnapshot;
        $targetSnapshot = $actor === 'attacker' ? $defenderSnapshot : $attackerSnapshot;

        // Determine if a skill can be used
        $skills = $actingSnapshot['skills'] ?? [];
        $weaponType = $actingSnapshot['weapon_type'] ?? 'barehands';
        
        $skillToUse = null;
        $skillManaCost = 0;
        foreach ($skills as $skill) {
            // Umiejętności pasywne (Aura Miecza, Furia Berserkera) nie są "rzucane" -
            // działają cały czas poprzez computePassives(), nie mogą więc zostać
            // wybrane jako aktywny skill tej tury.
            if (($skill['type'] ?? 'active') !== 'active') {
                continue;
            }

            if ($skill['required_weapon_type'] === 'all' || $skill['required_weapon_type'] === $weaponType) {
                $cd = $actorState['cooldowns'][$skill['id']] ?? 0;
                $effLevel = max(1, $skill['level'] ?? 1);
                $cost = max(0, (int) round(($skill['base_mana_cost'] ?? 0) + (($effLevel - 1) * ($skill['scaling_mana_cost'] ?? 0))));
                $cost = $this->applyPetManaReduction($cost, $actingSnapshot);

                if ($cd <= 0 && ($actorState['mana'] ?? 0) >= $cost) {
                    if (($skill['effect_type'] ?? null) === 'heal') {
                        $actingMaxHp = $actingSnapshot['max_hp'] ?? 1;
                        $currentActingHp = $actor === 'attacker' ? $attackerHp : $defenderHp;
                        $effVal = $skill['effective_value'] ?? (($skill['base_value'] ?? 0) + (($effLevel - 1) * ($skill['scaling_value'] ?? 0)));
                        $healAmount = max(1, (int) round($actingMaxHp * $effVal));
                        $maxAllowedHp = min($actingMaxHp - 1, $actingMaxHp - $healAmount + (int) round($actingMaxHp * 0.15));
                        if ($currentActingHp > $maxAllowedHp) {
                            continue; // Nie lecz na pełnym HP ani przy braku potrzeby
                        }
                    }

                    $skillToUse = $skill;
                    $skillManaCost = $cost;
                    break;
                }
            }
        }

        // Calculate damage using snapshot equipment stats and weapon type
        $attrs = $actingSnapshot['attributes'] ?? [];
        $str = $attrs['str'] ?? 0;
        $int = $attrs['int'] ?? 0;
        $agi = $attrs['agi'] ?? 0;

        $eqStats = $actingSnapshot['equipment_stats'] ?? [];
        $isMagicSkill = (bool) ($skillToUse['is_magic'] ?? false);
        $ccEffectType = null;
        $ccDuration = 0;

        if ($isMagicSkill) {
            $rawStatBonus = match ($weaponType) {
                'bow', 'sword', 'dagger' => $str + $agi,
                'bell' => $str + $int,
                'wand' => $int * 2,
                'axe' => $str * 2,
                default => $str * 2,
            };
            if ($weaponType === 'wand') {
                $weaponAtkMin = (int) ($eqStats['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $weaponAtkMin = ($eqStats['attack_min'] ?? 0) + ($eqStats['magic_attack_min'] ?? 0);
                $weaponAtkMax = ($eqStats['attack_max'] ?? 0) + ($eqStats['magic_attack_max'] ?? 0);
            } else {
                $weaponAtkMin = ($eqStats['magic_attack_min'] ?? 0) > 0 ? ($eqStats['magic_attack_min'] ?? 0) : ($eqStats['attack_min'] ?? 0);
                $weaponAtkMax = ($eqStats['magic_attack_max'] ?? 0) > 0 ? ($eqStats['magic_attack_max'] ?? 0) : ($eqStats['attack_max'] ?? 0);
            }
        } else {
            // Standard basic auto-attack (lub atak fizyczny)
            if ($weaponType === 'wand') {
                // Naprawiony bug (2026-08-05, follow-up rebalansu) - patrz identyczna
                // notatka w EncounterService::calculateDamage().
                $rawStatBonus = $int * 2;
                $weaponAtkMin = (int) ($eqStats['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $rawStatBonus = $str + $int;
                $weaponAtkMin = (int) ($eqStats['attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['attack_max'] ?? 0);
            } else {
                $rawStatBonus = match ($weaponType) {
                    'bow', 'sword', 'dagger' => $str + $agi,
                    'axe' => $str * 2,
                    default => $str * 2,
                };
                $weaponAtkMin = ($eqStats['attack_min'] ?? 0);
                $weaponAtkMax = ($eqStats['attack_max'] ?? 0);
            }
        }
        $statBonus = (int) round($rawStatBonus * Character::ATTRIBUTE_DAMAGE_MULTIPLIER);

        $baseDmgMin = 10 + $statBonus + ($actingSnapshot['level'] * 1) + $weaponAtkMin;
        $baseDmgMax = 10 + $statBonus + ($actingSnapshot['level'] * 1) + $weaponAtkMax;
        if ($baseDmgMax < $baseDmgMin) $baseDmgMax = $baseDmgMin;
        
        $damage = mt_rand($baseDmgMin, $baseDmgMax);

        if ($skillToUse) {
            $actorState['mana'] = max(0, ($actorState['mana'] ?? 0) - $skillManaCost);
            $actorState['cooldowns'][$skillToUse['id']] = $skillToUse['base_cooldown'];
            $effLevel = max(1, $skillToUse['level'] ?? 1);
            $bonus = $skillToUse['effective_value'] ?? ($skillToUse['base_value'] + (($effLevel - 1) * $skillToUse['scaling_value']));

            $effectType = $skillToUse['effect_type'] ?? 'direct_dmg';

            // --- HEAL: leczy aktora o % jego maksymalnego HP, zastępuje atak tej tury ---
            if ($effectType === 'heal') {
                $actingMaxHp = $actingSnapshot['max_hp'] ?? 1;
                $currentActingHp = $actor === 'attacker' ? $attackerHp : $defenderHp;
                $healAmount = max(1, (int) round($actingMaxHp * $bonus));
                $newActingHp = min($actingMaxHp, $currentActingHp + $healAmount);

                $healTurn = [
                    'actor' => $actor,
                    'type' => 'skill_heal',
                    'skill_id' => $skillToUse['id'],
                    'skill_name' => $skillToUse['name'] ?? null,
                    'effect_type' => $effectType,
                    'is_magic' => $isMagicSkill,
                    'value' => $healAmount,
                    'healAmount' => $healAmount,
                    'crit' => false,
                ];

                if ($actor === 'attacker') {
                    $healTurn['attackerHp'] = $newActingHp;
                    $healTurn['defenderHp'] = $defenderHp;
                    $healTurn['attackerMana'] = $actorState['mana'];
                    $healTurn['defenderMana'] = $targetState['mana'];
                } else {
                    $healTurn['attackerHp'] = $attackerHp;
                    $healTurn['defenderHp'] = $newActingHp;
                    $healTurn['attackerMana'] = $targetState['mana'];
                    $healTurn['defenderMana'] = $actorState['mana'];
                }

                return $healTurn;
            }

            if (in_array($effectType, ['direct_dmg', 'direct', 'damage', 'aoe_dmg', 'freeze', 'stun'])) {
                $damage = (int)($damage * $bonus);

                if (in_array($effectType, ['freeze', 'stun'], true)) {
                    $ccEffectType = $effectType;
                    $ccDuration = max(1, (int) ($skillToUse['base_duration'] ?? 1));
                }
            } elseif (in_array($effectType, ['buff_phys_dmg', 'buff_damage'])) {
                $actorState['effects']['buff_phys_dmg'] = [
                    'type' => 'buff_phys_dmg',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            } elseif ($effectType === 'buff_defense') {
                $actorState['effects']['buff_defense'] = [
                    'type' => 'buff_defense',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            } elseif (in_array($effectType, ['poison', 'dot_poison'])) {
                $targetState['effects'][$skillToUse['id']] = [
                    'type' => 'poison',
                    'name' => $skillToUse['name'] ?? 'Otrucie',
                    'icon' => $skillToUse['icon'] ?? null,
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            } elseif (in_array($effectType, ['fire', 'dot_fire'])) {
                $targetState['effects'][$skillToUse['id']] = [
                    'type' => 'fire',
                    'name' => $skillToUse['name'] ?? 'Podpalenie',
                    'icon' => $skillToUse['icon'] ?? null,
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            }
        }

        // Apply active physical damage buff + pasywna aura (np. Aura Miecza) if present
        $physBuffValue = ($actorState['effects']['buff_phys_dmg']['value'] ?? 0) + ($actorState['passives']['aura_dmg'] ?? 0);

        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
        }

        // Siła/Mądrość (drzewko Mistrzostwa) - patrz identyczna logika w EncounterService::calculateDamage().
        $isMagicDamage = $isMagicSkill || $weaponType === 'wand';
        $championDmgPct = $isMagicDamage
            ? ($actingSnapshot['champion_bonuses']['magic_dmg_pct'] ?? 0)
            : ($actingSnapshot['champion_bonuses']['phys_dmg_pct'] ?? 0);
        if ($championDmgPct > 0) {
            $damage = (int) round($damage * (1 + $championDmgPct));
        }

        foreach (['buff_phys_dmg', 'buff_defense'] as $buffKey) {
            if (isset($actorState['effects'][$buffKey])) {
                $buff = &$actorState['effects'][$buffKey];
                if ($buff['duration'] > 0) {
                    $buff['duration']--;
                    if ($buff['duration'] <= 0) {
                        unset($actorState['effects'][$buffKey]);
                    }
                }
                unset($buff);
            }
        }

        // Process DoT for target during this exchange
        $dotDamage = 0;
        $dotType = null;
        if (!empty($targetState['effects'])) {
            $targetMaxHp = $actor === 'attacker' ? $defenderSnapshot['max_hp'] : $attackerSnapshot['max_hp'];
            foreach ($targetState['effects'] as $id => &$eff) {
                if (($eff['type'] ?? '') === 'buff_phys_dmg') continue;

                if (($eff['duration'] ?? 0) > 0 && in_array($eff['type'] ?? '', ['poison', 'dot_poison', 'fire', 'dot_fire', 'bleed', 'dot_bleed'])) {
                    if (in_array($eff['type'] ?? '', ['bleed', 'dot_bleed'], true)) {
                        $targetCurrentHp = $targetState['hp'] ?? $targetMaxHp;
                        $dmg = max(1, (int)($targetCurrentHp * $eff['value']));
                    } else {
                        $dmg = max(1, (int)($targetMaxHp * $eff['value']));
                    }
                    $dotDamage += $dmg;
                    $dotType = $eff['type'];

                    $eff['duration']--;
                }
            }
            $targetState['effects'] = array_filter($targetState['effects'], fn($e) => ($e['duration'] ?? 0) > 0);
        }

        // "Magic burst": bronie hybrydowe (np. Dzwon) mają szansę dołożyć dodatkowe
        // obrażenia magiczne do ataku, poza zwykłymi obrażeniami fizycznymi.
        // Mitygowane tą samą obroną przeciwnika co reszta obrażeń (uproszczony model).
        $magicBurstDamage = 0;
        $magicBurstChance = $eqStats['magic_burst_chance'] ?? 0;
        if ($magicBurstChance > 0 && mt_rand(1, 100) <= $magicBurstChance) {
            $burstMin = $eqStats['magic_burst_min'] ?? 0;
            $burstMax = max($burstMin, $eqStats['magic_burst_max'] ?? 0);
            if ($burstMax > 0) {
                $magicBurstDamage = mt_rand((int) $burstMin, (int) $burstMax);
                $damage += $magicBurstDamage;
            }
        }

        // Defender's defense & Armor Penetration (Łuk / Infuzja różdżki)
        $defVit = $targetSnapshot['attributes']['vit'] ?? 1;
        $defEq = $targetSnapshot['equipment_stats'] ?? [];
        $defense = $defVit + ($targetSnapshot['level'] / 2) + ($defEq['defense'] ?? 0);

        $armorPenPct = min(85, max(0, ($eqStats['armor_pen_pct'] ?? 0) + ($procs['infusion_armor_pen'] ?? 0)));
        if ($armorPenPct > 0) {
            $defense = (int) round($defense * (1.0 - ($armorPenPct / 100.0)));
        }

        $damage = max(1, $damage - ($defense * 0.2));

        // Podwójny Cios (Miecz / Infuzja różdżki)
        if (!empty($procs['double_strike'])) {
            $damage += max(1, (int) round($damage * 0.5));
        }

        // "Silny vs Bohaterów": w PvE ten bonus nie ma zastosowania (potwór to nie
        // bohater) - liczy się wyłącznie tutaj i w Wojnie Gildii, bezwarunkowo (obie
        // strony pojedynku PvP to zawsze gracze).
        $heroBonusPct = $eqStats['strong_vs_hero'] ?? 0;
        if ($heroBonusPct > 0) {
            $damage += $damage * ($heroBonusPct / 100);
        }

        // "Odporność na Ludzi" obrońcy: redukuje obrażenia od drugiego gracza w PvP/GvG (cap do 75%)
        $heroResistPct = min(75, max(0, $defEq['resist_hero'] ?? 0));
        if ($heroResistPct > 0) {
            $damage = max(1, (int) round($damage * (1 - ($heroResistPct / 100))));
        }

        // "Odporność na Bronie" obrońcy (odporność na miecze, sztylety, dzwony, topory, łuki, różdżki) (cap do 75%)
        $weaponResistKey = 'resist_' . $weaponType;
        $weaponResistPct = min(75, max(0, $defEq[$weaponResistKey] ?? 0));
        if ($weaponResistPct > 0) {
            $damage = max(1, (int) round($damage * (1 - ($weaponResistPct / 100))));
        }

        // Redukcja obrażeń przychodzących z aktywnego buffa buff_defense celu (np. "Postawa
        // Tarczy") + Twardziel celu (Mistrzostwo) - capowana na 75%, ten sam wzorzec
        // bezpieczeństwa co passive_extra_attack.
        $targetDefenseBuffValue = min(0.75, max(0, ($targetState['effects']['buff_defense']['value'] ?? 0) + ($targetSnapshot['champion_bonuses']['dmg_reduction_pct'] ?? 0)));
        if ($targetDefenseBuffValue > 0) {
            $damage = max(1, $damage * (1 - $targetDefenseBuffValue));
        }

        // Crit & Dodge checks with opponent AGI reduction
        $actingAgi = $actingSnapshot['attributes']['agi'] ?? 1;
        $targetAgi = $targetSnapshot['attributes']['agi'] ?? 1;

        $baseCrit = 0.05 + ($actingAgi * 0.0015) + (($eqStats['crit_chance'] ?? 0) / 100);
        $critChance = max(0.03, min(1.00, $baseCrit));
        $isCrit = mt_rand(1, 1000) <= (int)round($critChance * 1000);

        $targetItemDodge = (($defEq['dodge_chance'] ?? 0) / 100.0);
        // Zwinność celu zwiększa jego unik, Celność atakującego go kontruje (Mistrzostwo).
        $championDodgeMod = ($targetSnapshot['champion_bonuses']['dodge_pct'] ?? 0) - ($actingSnapshot['champion_bonuses']['accuracy_pct'] ?? 0);
        $dodgeChance = max(0.00, min(0.30, 0.03 + ($targetAgi * 0.0006) + $targetItemDodge + $championDodgeMod));
        $isMiss = mt_rand(1, 1000) <= (int)round($dodgeChance * 1000);

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

        // Procki otrucia/ogłuszenia z ekwipunku - patrz rollEquipmentProcs() na górze klasy.
        $procs = $this->rollEquipmentProcs($eqStats, $defEq);
        if ($procs['dot']) {
            $targetState['effects']['equipment_poison'] = $procs['dot'];
        }
        $ccApplied = $procs['cc'];

        // Przełącznik obrażeń magicznych: dla skilli oznaczonych is_magic (Różdżka/Dzwon)
        // całość obrażeń tej tury pokazywana jest jako magicDamage - tak samo jak
        // w EncounterService::playerAttack() (patrz komentarz tam - to reklasyfikacja
        // do UI, mitygacja liczona identycznie, bez osobnej "obrony magicznej").
        $magicBurstDisplay = $magicBurstDamage > 0 ? (int) ($isCrit ? $magicBurstDamage * 1.5 : $magicBurstDamage) : 0;
        $magicDamageDisplay = ($isMagicSkill ? (int) $damage : 0) + $magicBurstDisplay;

        $turn = [
            'actor' => $actor,
            'type' => $skillToUse ? 'skill' : 'hit',
            'value' => (int)$damage,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
            'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => $isCrit,
            'is_magic' => $isMagicSkill,
            'magicDamage' => $magicDamageDisplay > 0 ? $magicDamageDisplay : null,
        ];

        if ($skillToUse) {
            $turn['skill_id'] = $skillToUse['id'];
            $turn['skill_name'] = $skillToUse['name'];
            $turn['effect_type'] = $skillToUse['effect_type'] ?? null;

            if ($ccEffectType !== null) {
                $turn['cc_applied'] = [
                    'type' => $ccEffectType,
                    'duration' => $ccDuration,
                ];
            }
        }

        if ($ccApplied) {
            $turn['cc_applied'] = [
                'type' => $ccApplied['type'],
                'duration' => max($turn['cc_applied']['duration'] ?? 0, $ccApplied['duration']),
            ];
        }

        if ($actor === 'attacker') {
            $newDefenderHp = max(0, $defenderHp - (int)$damage - $dotDamage);
            $turn['attackerHp'] = $attackerHp;
            $turn['defenderHp'] = $newDefenderHp;
            $turn['attackerMana'] = $actorState['mana'];
            $turn['defenderMana'] = $targetState['mana'];
        } else {
            $newAttackerHp = max(0, $attackerHp - (int)$damage - $dotDamage);
            $turn['attackerHp'] = $newAttackerHp;
            $turn['defenderHp'] = $defenderHp;
            $turn['attackerMana'] = $targetState['mana'];
            $turn['defenderMana'] = $actorState['mana'];
        }

        return $turn;
    }
}
