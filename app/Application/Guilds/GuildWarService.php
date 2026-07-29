<?php

namespace App\Application\Guilds;

use App\Application\Shared\Result;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
use App\Infrastructure\Persistence\GuildWar;
use App\Infrastructure\Persistence\GuildWarFight;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuildWarService
{
    // Procki z ekwipunku (2026-07-29): zduplikowane z EncounterService/PvPEncounterService
    // (patrz komentarze tam) - w 5v5 obie strony mają ekwipunek, więc resist_poison/
    // resist_stun przeciwnika realnie redukuje szansę na proc.
    private const EQUIPMENT_POISON_DURATION = 3;
    private const EQUIPMENT_POISON_VALUE = 0.03;
    private const EQUIPMENT_STUN_DURATION = 1;

    private function rollEquipmentProcs(array $eq, array $resistEq): array
    {
        $poisonChance = max(0, ($eq['poison_chance'] ?? 0) - ($resistEq['resist_poison'] ?? 0));
        $stunChance = max(0, ($eq['stun_chance'] ?? 0) - ($resistEq['resist_stun'] ?? 0));

        $dot = null;
        if ($poisonChance > 0 && mt_rand(1, 100) <= $poisonChance) {
            $dot = [
                'type' => 'poison',
                'name' => 'Zatrucie (Ekwipunek)',
                'duration' => self::EQUIPMENT_POISON_DURATION,
                'value' => self::EQUIPMENT_POISON_VALUE,
            ];
        }

        $cc = null;
        if ($stunChance > 0 && mt_rand(1, 100) <= $stunChance) {
            $cc = ['type' => 'stun', 'duration' => self::EQUIPMENT_STUN_DURATION];
        }

        return ['dot' => $dot, 'cc' => $cc];
    }

    /**
     * Challenge another guild to war.
     */
    public function challengeGuild(Guild $challenger, Guild $defender): Result
    {
        if ($challenger->id === $defender->id) {
            return Result::error('SAME_GUILD', 'Nie możesz wyzwać własnej gildii.');
        }

        if (!$challenger->hasWarTeam()) {
            return Result::error('NO_WAR_TEAM', 'Ustaw drużynę wojenną (5 członków) przed wyzwaniem.');
        }

        // Check if there's already an active war between these guilds
        $existingWar = GuildWar::where(function ($q) use ($challenger, $defender) {
            $q->where('challenger_guild_id', $challenger->id)
              ->where('defender_guild_id', $defender->id);
        })->orWhere(function ($q) use ($challenger, $defender) {
            $q->where('challenger_guild_id', $defender->id)
              ->where('defender_guild_id', $challenger->id);
        })->whereNotIn('status', ['finished', 'declined', 'expired'])
          ->exists();

        if ($existingWar) {
            return Result::error('WAR_EXISTS', 'Już istnieje aktywna wojna między tymi gildiami.');
        }

        try {
            return DB::transaction(function () use ($challenger, $defender) {
                // Create the war
                $war = GuildWar::create([
                    'challenger_guild_id' => $challenger->id,
                    'defender_guild_id' => $defender->id,
                    'status' => 'pending',
                    'challenger_roster' => $challenger->war_team,
                    'defender_roster' => [],
                    'gold_prize' => 0,
                    'gems_prize' => 0,
                    'xp_prize' => 0,
                ]);

                // Lock the defender guild (prevent upgrades)
                $defender->update(['is_war_locked' => true]);

                // Send mail to defender's leader
                $defenderLeader = GuildMember::where('guild_id', $defender->id)
                    ->where('role', 'leader')
                    ->first();

                if ($defenderLeader) {
                    Mail::create([
                        'to_character_id' => $defenderLeader->character_id,
                        'subject' => '⚔️ Wyzwanie Wojenne!',
                        'body' => "Gildia \"{$challenger->name}\" wyzwała Twoją gildię na wojnę! Wejdź do panelu gildii, ustaw drużynę wojenną i zaakceptuj lub odrzuć wyzwanie. UWAGA: Ulepszenia gildii są zablokowane do czasu odpowiedzi.",
                        'attachments' => [
                            ['type' => 'guild_war_challenge', 'guild_war_id' => $war->id]
                        ],
                    ]);
                }

                Log::info('Guild war challenge sent', [
                    'war_id' => $war->id,
                    'challenger' => $challenger->name,
                    'defender' => $defender->name,
                ]);

                return Result::ok($war);
            });
        } catch (\Exception $e) {
            Log::error('Guild war challenge failed', ['error' => $e->getMessage()]);
            return Result::error('CHALLENGE_FAILED', 'Nie udało się wysłać wyzwania.');
        }
    }

    /**
     * Accept a war challenge. Requires defender to have war_team set.
     */
    public function acceptWar(GuildWar $war, Guild $defender): Result
    {
        if ($war->defender_guild_id !== $defender->id) {
            return Result::error('NOT_DEFENDER', 'Ta gildia nie jest obrońcą w tej wojnie.');
        }

        if ($war->status !== 'pending') {
            return Result::error('NOT_PENDING', 'Ta wojna nie oczekuje już na odpowiedź.');
        }

        if (!$defender->hasWarTeam()) {
            return Result::error('NO_WAR_TEAM', 'Ustaw drużynę wojenną (5 członków) przed akceptacją.');
        }

        try {
            return DB::transaction(function () use ($war, $defender) {
                // Snapshot the prize as the CURRENT treasury of the defender + challenger
                $challengerGuild = Guild::find($war->challenger_guild_id);
                
                $war->update([
                    'status' => 'in_progress',
                    'defender_roster' => $defender->war_team,
                    'gold_prize' => $defender->gold + ($challengerGuild->gold ?? 0),
                    'gems_prize' => $defender->gems + ($challengerGuild->gems ?? 0),
                    'started_at' => now(),
                ]);

                Log::info('Guild war accepted', ['war_id' => $war->id]);

                return Result::ok($war->fresh());
            });
        } catch (\Exception $e) {
            Log::error('Guild war accept failed', ['error' => $e->getMessage()]);
            return Result::error('ACCEPT_FAILED', 'Nie udało się zaakceptować wojny.');
        }
    }

    /**
     * Decline a war challenge.
     */
    public function declineWar(GuildWar $war, Guild $defender): Result
    {
        if ($war->defender_guild_id !== $defender->id) {
            return Result::error('NOT_DEFENDER', 'Ta gildia nie jest obrońcą w tej wojnie.');
        }

        if ($war->status !== 'pending') {
            return Result::error('NOT_PENDING', 'Ta wojna nie oczekuje już na odpowiedź.');
        }

        $war->update([
            'status' => 'declined',
            'ended_at' => now(),
        ]);

        // Unlock defender
        $defender->update(['is_war_locked' => false]);

        return Result::ok($war);
    }

    /**
     * Process a guild war: JEDNO starcie drużynowe 5v5 (obie 5-osobowe drużyny
     * wojenne walczą jednocześnie), analogicznie do starć grupowych PvE
     * (`EncounterService::simulateMultiCombat`) - tylko że po obu stronach
     * stoją żywi gracze zamiast potworów. Wcześniej wojna rozgrywała się jako
     * 5 osobnych pojedynków 1v1 (best-of-5); od teraz o zwycięstwie decyduje
     * WYNIK CAŁEGO starcia drużynowego (który zespół zostanie doszczętnie
     * pokonany, lub - przy przekroczeniu limitu rund - który ma wyższy
     * łączny procent pozostałego HP).
     */
    public function processWar(GuildWar $war): Result
    {
        if ($war->status !== 'in_progress') {
            return Result::error('NOT_IN_PROGRESS', 'Wojna nie jest w toku.');
        }

        try {
            return DB::transaction(function () use ($war) {
                $challengerRoster = $war->challenger_roster;
                $defenderRoster = $war->defender_roster;

                $challengerCharacters = Character::whereIn('id', $challengerRoster)->get()->keyBy('id');
                $defenderCharacters = Character::whereIn('id', $defenderRoster)->get()->keyBy('id');

                // Obie strony walczą dedykowanym setem "Wojna Gildii" (z fallbackiem
                // per-slot na aktualny ekwipunek - patrz Character::resolveEffectiveEquipment()),
                // niezależnie od tego, czy to atak czy obrona.
                $challengerSnapshots = [];
                foreach ($challengerRoster as $characterId) {
                    if (isset($challengerCharacters[$characterId])) {
                        $challengerSnapshots[] = $challengerCharacters[$characterId]->createSnapshot(CharacterEquipmentSetItem::SET_GUILD_WAR);
                    }
                }

                $defenderSnapshots = [];
                foreach ($defenderRoster as $characterId) {
                    if (isset($defenderCharacters[$characterId])) {
                        $defenderSnapshots[] = $defenderCharacters[$characterId]->createSnapshot(CharacterEquipmentSetItem::SET_GUILD_WAR);
                    }
                }

                if (empty($challengerSnapshots) || empty($defenderSnapshots)) {
                    return Result::error('EMPTY_ROSTER', 'Jedna z drużyn wojennych jest pusta - nie można rozegrać starcia.');
                }

                $battleResult = $this->simulateTeamBattle($challengerSnapshots, $defenderSnapshots);

                // Determine overall winner
                $challengerGuild = $war->challengerGuild;
                $defenderGuild = $war->defenderGuild;

                // Refresh models to get latest gold/gems
                $challengerGuild->refresh();
                $defenderGuild->refresh();

                $winnerGuild = $battleResult['winner_side'] === 'challenger' ? $challengerGuild : $defenderGuild;
                $loserGuild = $winnerGuild->id === $challengerGuild->id ? $defenderGuild : $challengerGuild;

                // Transfer ENTIRE loser's treasury to winner
                $stolenGold = $loserGuild->gold;
                $stolenGems = $loserGuild->gems;

                $winnerGuild->increment('gold', $stolenGold);
                $winnerGuild->increment('gems', $stolenGems);
                $loserGuild->update(['gold' => 0, 'gems' => 0]);

                // Award arena tokens to the winning roster
                $winnerRoster = $winnerGuild->id === $challengerGuild->id ? $challengerRoster : $defenderRoster;
                if (!empty($winnerRoster)) {
                    Character::whereIn('id', $winnerRoster)->increment('arena_tokens', 50);
                }

                GuildWarFight::create([
                    'guild_war_id' => $war->id,
                    'fight_order' => 1,
                    'challenger_character_id' => null,
                    'defender_character_id' => null,
                    'winner_character_id' => null,
                    'challenger_snapshot' => $challengerSnapshots,
                    'defender_snapshot' => $defenderSnapshots,
                    'turns' => $battleResult['turns'],
                    'combat_data' => [
                        'final_team_state' => $battleResult['final_team_state'],
                    ],
                    'challenger_survivors' => $battleResult['challenger_survivors'],
                    'defender_survivors' => $battleResult['defender_survivors'],
                    'rounds' => $battleResult['rounds'],
                ]);

                // Finish war
                $war->update([
                    'status' => 'finished',
                    'winner_guild_id' => $winnerGuild->id,
                    'gold_prize' => $stolenGold,
                    'gems_prize' => $stolenGems,
                    'ended_at' => now(),
                ]);

                // Unlock both guilds
                $challengerGuild->update(['is_war_locked' => false]);
                $defenderGuild->update(['is_war_locked' => false]);

                Log::info('Guild war finished (5v5 team battle)', [
                    'war_id' => $war->id,
                    'winner' => $winnerGuild->name,
                    'survivors' => "{$battleResult['challenger_survivors']}:{$battleResult['defender_survivors']}",
                    'rounds' => $battleResult['rounds'],
                    'gold_transferred' => $stolenGold,
                    'gems_transferred' => $stolenGems,
                ]);

                return Result::ok([
                    'war' => $war->fresh(),
                    'winner' => $winnerGuild,
                    'loser' => $loserGuild,
                    'survivors' => [
                        'challenger' => $battleResult['challenger_survivors'],
                        'defender' => $battleResult['defender_survivors'],
                    ],
                    'rounds' => $battleResult['rounds'],
                    'gold_prize' => $stolenGold,
                    'gems_prize' => $stolenGems,
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Guild war processing failed', [
                'war_id' => $war->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('WAR_PROCESS_FAILED', 'Przetwarzanie wojny gildii nie powiodło się.');
        }
    }

    /**
     * Symuluje JEDNO starcie drużynowe 5v5: obie drużyny (do 5 żywych postaci
     * każda) walczą jednocześnie na tej samej "planszy", zamiast rozgrywać 5
     * odseparowanych pojedynków 1v1. Kolejność działania (inicjatywa) ustalana
     * jest raz na starcie wg AGI (z losowym rozstrzyganiem remisów) i
     * obowiązuje przez całe starcie - w każdej rundzie każda żywa postać
     * wykonuje jedną akcję w tej kolejności.
     *
     * Cel każdego ataku: żywy przeciwnik z NAJNIŻSZYM aktualnym HP po
     * przeciwnej stronie (skupianie ognia na najsłabszym - taktyka drużynowa),
     * dzięki czemu drużyny realnie "topnieją" jeden przeciwnik na raz, zamiast
     * rozkładać obrażenia równo po całej piątce.
     *
     * Formuła obrażeń, szansy na trafienie krytyczne/unik oraz obsługa
     * umiejętności bojowych (direct_dmg / buff_phys_dmg / poison / fire) i
     * "magic burst" broni hybrydowych jest identyczna z
     * `PvPEncounterService::performAttack()`, żeby zachować pełny parytet
     * balansu między Areną (1v1) a Wojnami Gildii (5v5).
     *
     * @param array $challengerSnapshots Snapshoty (Character::createSnapshot()) drużyny atakującej, max 5.
     * @param array $defenderSnapshots Snapshoty drużyny broniącej, max 5.
     */
    private function simulateTeamBattle(array $challengerSnapshots, array $defenderSnapshots): array
    {
        $combatants = [];
        foreach (array_values($challengerSnapshots) as $i => $snap) {
            $combatants[] = [
                'side' => 'challenger', 'idx' => $i, 'snapshot' => $snap,
                'hp' => $snap['max_hp'], 'maxHp' => max(1, $snap['max_hp']), 'alive' => true,
                'cooldowns' => [], 'effects' => [], 'cc_turns' => 0,
            ];
        }
        foreach (array_values($defenderSnapshots) as $i => $snap) {
            $combatants[] = [
                'side' => 'defender', 'idx' => $i, 'snapshot' => $snap,
                'hp' => $snap['max_hp'], 'maxHp' => max(1, $snap['max_hp']), 'alive' => true,
                'cooldowns' => [], 'effects' => [], 'cc_turns' => 0,
            ];
        }

        // Kolejność inicjatywy: wyższe AGI działa wcześniej, remisy losowe.
        $order = array_keys($combatants);
        shuffle($order);
        usort($order, function ($a, $b) use ($combatants) {
            $agiA = $combatants[$a]['snapshot']['attributes']['agi'] ?? 0;
            $agiB = $combatants[$b]['snapshot']['attributes']['agi'] ?? 0;
            return $agiB <=> $agiA;
        });

        $turns = [];
        $round = 0;
        $maxRounds = 40;

        while ($round < $maxRounds) {
            if (!$this->anyCombatantAlive($combatants, 'challenger') || !$this->anyCombatantAlive($combatants, 'defender')) {
                break;
            }

            foreach ($order as $ci) {
                if (!$combatants[$ci]['alive']) {
                    continue;
                }

                $enemySide = $combatants[$ci]['side'] === 'challenger' ? 'defender' : 'challenger';
                $targetIdx = $this->selectLowestHpAlive($combatants, $enemySide);
                if ($targetIdx === null) {
                    break 2; // przeciwna drużyna została doszczętnie pokonana w trakcie rundy
                }

                // Ogłuszenie (ze skilla lub z procka ekwipunku) - aktor traci turę ataku,
                // ale jego cooldowny nadal tykają (parytet z PvPEncounterService::simulateCombat()).
                if (($combatants[$ci]['cc_turns'] ?? 0) > 0) {
                    $combatants[$ci]['cc_turns']--;
                    $turn = [
                        'actor_side' => $combatants[$ci]['side'],
                        'actor_idx' => $combatants[$ci]['idx'],
                        'actor_name' => $combatants[$ci]['snapshot']['name'] ?? null,
                        'type' => 'crowd_controlled',
                        'value' => 0,
                        'crit' => false,
                    ];
                    $turn['round'] = $round + 1;
                    $turn['team_state'] = $this->getTeamStateSummary($combatants);
                    $turns[] = $turn;

                    foreach ($combatants[$ci]['cooldowns'] as $skillId => $cd) {
                        if ($cd > 0) {
                            $combatants[$ci]['cooldowns'][$skillId]--;
                        }
                    }

                    continue;
                }

                $turn = $this->resolveTeamAttack($combatants[$ci], $combatants[$targetIdx]);
                $turn['round'] = $round + 1;
                $turn['team_state'] = $this->getTeamStateSummary($combatants);
                $turns[] = $turn;

                if (!empty($turn['cc_applied'])) {
                    $combatants[$targetIdx]['cc_turns'] = max($combatants[$targetIdx]['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
                }

                // Cooldowny umiejętności aktora tykają po jego własnej akcji.
                foreach ($combatants[$ci]['cooldowns'] as $skillId => $cd) {
                    if ($cd > 0) {
                        $combatants[$ci]['cooldowns'][$skillId]--;
                    }
                }

                if (!$this->anyCombatantAlive($combatants, 'challenger') || !$this->anyCombatantAlive($combatants, 'defender')) {
                    break 2;
                }
            }

            $round++;
        }

        $challengerSurvivors = $this->countAlive($combatants, 'challenger');
        $defenderSurvivors = $this->countAlive($combatants, 'defender');

        if ($challengerSurvivors > 0 && $defenderSurvivors === 0) {
            $winnerSide = 'challenger';
        } elseif ($defenderSurvivors > 0 && $challengerSurvivors === 0) {
            $winnerSide = 'defender';
        } else {
            // Limit rund osiągnięty (lub obie drużyny padły jednocześnie) -
            // rozstrzyga wyższy łączny procent pozostałego HP drużyny.
            $winnerSide = $this->aggregateHpPct($combatants, 'challenger') >= $this->aggregateHpPct($combatants, 'defender')
                ? 'challenger'
                : 'defender';
        }

        return [
            'winner_side' => $winnerSide,
            'turns' => $turns,
            'rounds' => $round,
            'challenger_survivors' => $challengerSurvivors,
            'defender_survivors' => $defenderSurvivors,
            'final_team_state' => $this->getTeamStateSummary($combatants),
        ];
    }

    /**
     * Rozwiązuje jeden atak w starciu drużynowym 5v5. Logika obrażeń, krytyka,
     * uniku, umiejętności bojowych oraz "magic burst" jest zduplikowana z
     * `PvPEncounterService::performAttack()` (tam operujemy na dwóch stałych
     * snapshotach atakujący/broniący, tutaj na dowolnej parze combatantów z
     * 10-osobowej "planszy" 5v5) - przy zmianie balansu obrażeń w jednym
     * miejscu, zmień też w drugim.
     */
    private function resolveTeamAttack(array &$actor, array &$target): array
    {
        $snap = $actor['snapshot'];
        $targetSnap = $target['snapshot'];

        $skills = $snap['skills'] ?? [];
        $weaponType = $snap['weapon_type'] ?? 'barehands';

        $skillToUse = null;
        foreach ($skills as $skill) {
            if (($skill['required_weapon_type'] ?? 'all') === 'all' || $skill['required_weapon_type'] === $weaponType) {
                $cd = $actor['cooldowns'][$skill['id']] ?? 0;
                if ($cd <= 0) {
                    $skillToUse = $skill;
                    break;
                }
            }
        }

        $attrs = $snap['attributes'] ?? [];
        $str = $attrs['str'] ?? 0;
        $int = $attrs['int'] ?? 0;
        $agi = $attrs['agi'] ?? 0;

        $rawStatBonus = match ($weaponType) {
            'bow', 'sword', 'dagger' => $str + $agi,
            'bell' => $str + $int,
            'wand' => $int * 2,
            'axe' => $str * 2,
            default => $str * 2,
        };
        $statBonus = (int) round($rawStatBonus * Character::ATTRIBUTE_DAMAGE_MULTIPLIER);

        $eq = $snap['equipment_stats'] ?? [];
        $weaponAtkMin = ($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0);
        $weaponAtkMax = ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0);

        $baseDmgMin = 10 + $statBonus + ($snap['level'] * 1) + $weaponAtkMin;
        $baseDmgMax = 10 + $statBonus + ($snap['level'] * 1) + $weaponAtkMax;
        if ($baseDmgMax < $baseDmgMin) $baseDmgMax = $baseDmgMin;

        $damage = mt_rand($baseDmgMin, $baseDmgMax);

        if ($skillToUse) {
            $actor['cooldowns'][$skillToUse['id']] = $skillToUse['base_cooldown'];
            $effLevel = max(1, $skillToUse['level'] ?? 1);
            $bonus = $skillToUse['base_value'] + (($effLevel - 1) * $skillToUse['scaling_value']);
            $effectType = $skillToUse['effect_type'] ?? 'direct_dmg';

            if (in_array($effectType, ['direct_dmg', 'direct', 'damage'])) {
                $damage = (int) ($damage * $bonus);
            } elseif (in_array($effectType, ['buff_phys_dmg', 'buff_damage'])) {
                $actor['effects']['buff_phys_dmg'] = [
                    'type' => 'buff_phys_dmg',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            } elseif (in_array($effectType, ['poison', 'dot_poison'])) {
                $target['effects'][$skillToUse['id']] = [
                    'type' => 'poison',
                    'name' => $skillToUse['name'] ?? 'Otrucie',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            } elseif (in_array($effectType, ['fire', 'dot_fire'])) {
                $target['effects'][$skillToUse['id']] = [
                    'type' => 'fire',
                    'name' => $skillToUse['name'] ?? 'Podpalenie',
                    'duration' => $skillToUse['base_duration'],
                    'value' => $bonus,
                ];
            }
        }

        if (isset($actor['effects']['buff_phys_dmg'])) {
            $buff = &$actor['effects']['buff_phys_dmg'];
            if ($buff['duration'] > 0) {
                $damage = (int) ($damage * (1 + $buff['value']));
                $buff['duration']--;
                if ($buff['duration'] <= 0) {
                    unset($actor['effects']['buff_phys_dmg']);
                }
            }
            unset($buff);
        }

        // DoT (otrucie/podpalenie) tyka na celu przy każdym ataku wymierzonym w niego.
        $dotDamage = 0;
        $dotType = null;
        if (!empty($target['effects'])) {
            foreach ($target['effects'] as $id => &$eff) {
                if (($eff['type'] ?? '') === 'buff_phys_dmg') continue;

                if (($eff['duration'] ?? 0) > 0 && in_array($eff['type'] ?? '', ['poison', 'dot_poison', 'fire', 'dot_fire'])) {
                    $dmg = max(1, (int) ($target['maxHp'] * $eff['value']));
                    $dotDamage += $dmg;
                    $dotType = $eff['type'];
                    $eff['duration']--;
                }
            }
            unset($eff);
            $target['effects'] = array_filter($target['effects'], fn ($e) => ($e['duration'] ?? 0) > 0);
        }

        // "Magic burst": bronie hybrydowe (np. Dzwon) mogą dołożyć osobne obrażenia magiczne.
        $magicBurstDamage = 0;
        $magicBurstChance = $eq['magic_burst_chance'] ?? 0;
        if ($magicBurstChance > 0 && mt_rand(1, 100) <= $magicBurstChance) {
            $burstMin = $eq['magic_burst_min'] ?? 0;
            $burstMax = max($burstMin, $eq['magic_burst_max'] ?? 0);
            if ($burstMax > 0) {
                $magicBurstDamage = mt_rand((int) $burstMin, (int) $burstMax);
                $damage += $magicBurstDamage;
            }
        }

        $defVit = $targetSnap['attributes']['vit'] ?? 1;
        $defEq = $targetSnap['equipment_stats'] ?? [];
        $defense = $defVit + ($targetSnap['level'] / 2) + ($defEq['defense'] ?? 0);
        $damage = max(1, $damage - ($defense / 2));

        // "Silny vs Bohaterów" - patrz identyczny bonus w PvPEncounterService::performAttack().
        $heroBonusPct = $eq['strong_vs_hero'] ?? 0;
        if ($heroBonusPct > 0) {
            $damage += $damage * ($heroBonusPct / 100);
        }

        $actingAgi = $attrs['agi'] ?? 1;
        $targetAgi = $targetSnap['attributes']['agi'] ?? 1;

        $baseCrit = 0.05 + ($actingAgi * 0.004) + (($eq['crit_chance'] ?? 0) / 100);
        $agiCritPenalty = max(0, ($targetAgi - $actingAgi) * 0.0008);
        $critChance = max(0.03, $baseCrit - $agiCritPenalty);
        $isCrit = mt_rand(1, 1000) <= (int) round($critChance * 1000);

        $agiDodgeAdvantage = max(0, $targetAgi - $actingAgi);
        $dodgeChance = 0.03 + ($agiDodgeAdvantage * 0.0015);
        $isMiss = mt_rand(1, 1000) <= (int) round($dodgeChance * 1000);

        $turn = [
            'actor_side' => $actor['side'],
            'actor_idx' => $actor['idx'],
            'actor_name' => $snap['name'] ?? null,
            'target_side' => $target['side'],
            'target_idx' => $target['idx'],
            'target_name' => $targetSnap['name'] ?? null,
        ];

        if ($isMiss) {
            $target['hp'] = max(0, $target['hp'] - $dotDamage);
            $turn += [
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
            ];
        } else {
            if ($isCrit) {
                $damage = (int) ($damage * 1.5);
            }

            $target['hp'] = max(0, $target['hp'] - (int) $damage - $dotDamage);

            $turn += [
                'type' => $skillToUse ? 'skill' : 'hit',
                'value' => (int) $damage,
                'crit' => $isCrit,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'magicDamage' => $magicBurstDamage > 0 ? (int) ($isCrit ? $magicBurstDamage * 1.5 : $magicBurstDamage) : null,
            ];

            if ($skillToUse) {
                $turn['skill_id'] = $skillToUse['id'];
                $turn['skill_name'] = $skillToUse['name'];
                $turn['effect_type'] = $skillToUse['effect_type'] ?? null;
            }

            // Procki otrucia/ogłuszenia z ekwipunku - patrz rollEquipmentProcs() na górze klasy.
            $procs = $this->rollEquipmentProcs($eq, $defEq);
            if ($procs['dot']) {
                $target['effects']['equipment_poison'] = $procs['dot'];
            }
            if ($procs['cc']) {
                $turn['cc_applied'] = $procs['cc'];
            }
        }

        if ($target['hp'] <= 0) {
            $target['hp'] = 0;
            $target['alive'] = false;
        }

        return $turn;
    }

    private function anyCombatantAlive(array $combatants, string $side): bool
    {
        foreach ($combatants as $c) {
            if ($c['side'] === $side && $c['alive']) {
                return true;
            }
        }
        return false;
    }

    private function countAlive(array $combatants, string $side): int
    {
        return count(array_filter($combatants, fn ($c) => $c['side'] === $side && $c['alive']));
    }

    /**
     * Wybiera żywego przeciwnika z NAJNIŻSZYM aktualnym HP po danej stronie
     * (taktyka "focus fire" drużynowego skupiania ognia na najsłabszym celu).
     */
    private function selectLowestHpAlive(array $combatants, string $side): ?int
    {
        $best = null;
        foreach ($combatants as $idx => $c) {
            if ($c['side'] !== $side || !$c['alive']) continue;
            if ($best === null || $c['hp'] < $combatants[$best]['hp']) {
                $best = $idx;
            }
        }
        return $best;
    }

    private function aggregateHpPct(array $combatants, string $side): float
    {
        $hp = 0;
        $maxHp = 0;
        foreach ($combatants as $c) {
            if ($c['side'] !== $side) continue;
            $hp += max(0, $c['hp']);
            $maxHp += max(1, $c['maxHp']);
        }
        return $maxHp > 0 ? $hp / $maxHp : 0.0;
    }

    private function getTeamStateSummary(array $combatants): array
    {
        return array_map(function ($c) {
            return [
                'side' => $c['side'],
                'idx' => $c['idx'],
                'character_id' => $c['snapshot']['character_id'] ?? null,
                'name' => $c['snapshot']['name'] ?? null,
                'level' => $c['snapshot']['level'] ?? null,
                'hp' => $c['hp'],
                'maxHp' => $c['maxHp'],
                'alive' => $c['alive'],
            ];
        }, $combatants);
    }
}
