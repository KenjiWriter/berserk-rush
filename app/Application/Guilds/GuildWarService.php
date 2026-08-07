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

    // Balans PvP (2026-08-07): ten sam przelicznik i to samo uzasadnienie co
    // `PvPEncounterService::PVP_DAMAGE_MULTIPLIER` (patrz docs/modules/pvp_and_arena.md) -
    // Wojna Gildii to również starcie gracz-vs-gracz, dotknięte tym samym problemem
    // oneshotów. PvE pozostaje bez zmian.
    private const PVP_DAMAGE_MULTIPLIER = 0.33;

    /**
     * Bonusy z drzewka Mistrzostwa (patrz docs/modules/mastery.md) dla kombatanta
     * 5v5 - snapshoty to płaskie tablice, więc doliczamy je raz przy budowie
     * `$combatants` zamiast odpytywać bazę na każdą wymianę ciosów.
     */
    private function buildChampionBonuses(?string $characterId): array
    {
        $zero = [
            'phys_dmg_pct' => 0.0, 'magic_dmg_pct' => 0.0, 'mana_regen_pct' => 0.0,
            'dodge_pct' => 0.0, 'accuracy_pct' => 0.0, 'dmg_reduction_pct' => 0.0,
        ];

        if (!$characterId) {
            return $zero;
        }

        $character = Character::find($characterId);
        if (!$character) {
            return $zero;
        }

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
                'mana' => $snap['max_mana'] ?? 50, 'maxMana' => max(1, $snap['max_mana'] ?? 50),
                'cooldowns' => [], 'effects' => [], 'cc_turns' => 0,
                'passives' => ['aura_dmg' => 0, 'extra_attack_chance' => 0],
                'championBonuses' => $this->buildChampionBonuses($snap['character_id'] ?? null),
            ];
        }
        foreach (array_values($defenderSnapshots) as $i => $snap) {
            $combatants[] = [
                'side' => 'defender', 'idx' => $i, 'snapshot' => $snap,
                'hp' => $snap['max_hp'], 'maxHp' => max(1, $snap['max_hp']), 'alive' => true,
                'mana' => $snap['max_mana'] ?? 50, 'maxMana' => max(1, $snap['max_mana'] ?? 50),
                'cooldowns' => [], 'effects' => [], 'cc_turns' => 0,
                'passives' => ['aura_dmg' => 0, 'extra_attack_chance' => 0],
                'championBonuses' => $this->buildChampionBonuses($snap['character_id'] ?? null),
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

                // Regeneracja many aktora na początku jego akcji (5% maxMana, min 5 MP) + Koncentracja (Mistrzostwo)
                $manaRegenPct = 0.05 + ($combatants[$ci]['championBonuses']['mana_regen_pct'] ?? 0);
                $combatants[$ci]['mana'] = min($combatants[$ci]['maxMana'], $combatants[$ci]['mana'] + max(5, (int)ceil($combatants[$ci]['maxMana'] * $manaRegenPct)));

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

                // Może zwrócić kilka turnów naraz (skill obszarowy trafiający całą wrogą
                // drużynę, lub dodatkowy atak z pasywnej szansy) - CC jest aplikowane
                // bezpośrednio na trafiony cel wewnątrz resolveHitAgainstTarget() (referencja
                // do $combatants), więc tutaj tylko dopisujemy turny do logu walki.
                $resultTurns = $this->resolveTeamAttack($combatants, $ci, $targetIdx, $enemySide);
                foreach ($resultTurns as $rt) {
                    $rt['round'] = $round + 1;
                    $rt['team_state'] = $this->getTeamStateSummary($combatants);
                    $turns[] = $rt;
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
     * Rozwiązuje jedną AKCJĘ w starciu drużynowym 5v5 (może wygenerować kilka turnów -
     * skill obszarowy trafia całą wrogą drużynę naraz, a pasywna szansa może dołożyć
     * dodatkowy atak). Logika obrażeń, krytyka, uniku, umiejętności bojowych oraz
     * "magic burst" jest zduplikowana z `PvPEncounterService::performAttack()` (tam
     * operujemy na dwóch stałych snapshotach atakujący/broniący, tutaj na dowolnej
     * parze combatantów z 10-osobowej "planszy" 5v5) - przy zmianie balansu obrażeń
     * w jednym miejscu, zmień też w drugim. Od 2026-07-29 obsługuje pełen parytet
     * z PvE/PvP: `heal`, `freeze`/`stun` (CC), `buff_defense`, `is_aoe` oraz pasywy
     * (`passive_aura_dmg`/`passive_extra_attack`) - wcześniej te effect_type po
     * prostu wykonywały niezmodyfikowany atak zamiast swojego właściwego efektu.
     */
    private function resolveTeamAttack(array &$combatants, int $actorIdx, int $primaryTargetIdx, string $enemySide): array
    {
        // Pobranie many i ocena efektów pasywnych na tę turę
        $actorSnap = $combatants[$actorIdx]['snapshot'] ?? [];
        $combatants[$actorIdx]['passives'] = $this->computeTeamPassivesForTurn($actorSnap, $combatants[$actorIdx]['mana']);

        $turns = $this->resolveTeamAction($combatants, $actorIdx, $primaryTargetIdx, $enemySide);

        $anyHit = false;
        foreach ($turns as $t) {
            if (($t['type'] ?? '') !== 'miss') {
                $anyHit = true;
                break;
            }
        }

        // Pasywna szansa na natychmiastowy dodatkowy atak (np. Furia Berserkera - topór).
        // Nie rzuca ponownie szansy na kolejny dodatkowy atak (brak nieskończonych łańcuchów) -
        // dlatego woła resolveTeamAction() bezpośrednio, a nie resolveTeamAttack() rekurencyjnie.
        if ($anyHit && $combatants[$actorIdx]['alive']) {
            $extraChance = $combatants[$actorIdx]['passives']['extra_attack_chance'] ?? 0;
            if ($this->rollPassiveChance($extraChance)) {
                $bonusTargetIdx = $this->selectLowestHpAlive($combatants, $enemySide);
                if ($bonusTargetIdx !== null) {
                    $bonusTurns = $this->resolveTeamAction($combatants, $actorIdx, $bonusTargetIdx, $enemySide);
                    foreach ($bonusTurns as &$bt) {
                        $bt['extra_attack'] = true;
                    }
                    unset($bt);
                    $turns = array_merge($turns, $bonusTurns);
                }
            }
        }

        return $turns;
    }

    /**
     * Rdzeń pojedynczej akcji: wybór gotowego skilla, jego efekt (heal / self-buff /
     * atak pojedynczy / AOE) - bez rzutu na pasywną szansę dodatkowego ataku (patrz
     * resolveTeamAttack() powyżej).
     */
    private function resolveTeamAction(array &$combatants, int $actorIdx, int $primaryTargetIdx, string $enemySide): array
    {
        $actor = &$combatants[$actorIdx];
        $snap = $actor['snapshot'];
        $skills = $snap['skills'] ?? [];
        $weaponType = $snap['weapon_type'] ?? 'barehands';

        $skillToUse = null;
        $skillManaCost = 0;
        foreach ($skills as $skill) {
            // Umiejętności pasywne nie są "rzucane" - działają cały czas poprzez
            // computeTeamPassives(), nie mogą więc zostać wybrane jako akcja tej tury.
            if (($skill['type'] ?? 'active') !== 'active') {
                continue;
            }
            if (($skill['required_weapon_type'] ?? 'all') === 'all' || $skill['required_weapon_type'] === $weaponType) {
                $cd = $actor['cooldowns'][$skill['id']] ?? 0;
                $effLevel = max(1, $skill['level'] ?? 1);
                $cost = max(0, (int) round(($skill['base_mana_cost'] ?? 0) + (($effLevel - 1) * ($skill['scaling_mana_cost'] ?? 0))));
                $cost = $this->applyPetManaReduction($cost, $snap);

                if ($cd <= 0 && ($actor['mana'] ?? 0) >= $cost) {
                    if (($skill['effect_type'] ?? null) === 'heal') {
                        $effVal = ($skill['base_value'] ?? 0) + (($effLevel - 1) * ($skill['scaling_value'] ?? 0));
                        $healAmount = max(1, (int) round($actor['maxHp'] * $effVal));
                        $maxAllowedHp = min($actor['maxHp'] - 1, $actor['maxHp'] - $healAmount + (int) round($actor['maxHp'] * 0.15));
                        if ($actor['hp'] > $maxAllowedHp) {
                            continue; // Nie lecz na pełnym HP ani przy braku potrzeby
                        }
                    }

                    $skillToUse = $skill;
                    $skillManaCost = $cost;
                    break;
                }
            }
        }

        $effectType = $skillToUse['effect_type'] ?? null;
        $bonus = 0.0;

        if ($skillToUse) {
            $actor['mana'] = max(0, ($actor['mana'] ?? 0) - $skillManaCost);
            $actor['cooldowns'][$skillToUse['id']] = $skillToUse['base_cooldown'];
            $effLevel = max(1, $skillToUse['level'] ?? 1);
            $bonus = $skillToUse['base_value'] + (($effLevel - 1) * $skillToUse['scaling_value']);
        }

        // --- HEAL: leczy aktora, nie atakuje (aktywne DoT-y na wybranym celu nadal tykają). ---
        if ($effectType === 'heal') {
            return [$this->resolveTeamHeal($combatants, $actorIdx, $primaryTargetIdx, $bonus, $skillToUse)];
        }

        if (in_array($effectType, ['buff_phys_dmg', 'buff_damage'], true)) {
            $actor['effects']['buff_phys_dmg'] = [
                'type' => 'buff_phys_dmg',
                'duration' => $skillToUse['base_duration'],
                'value' => $bonus,
            ];
        } elseif ($effectType === 'buff_defense') {
            $actor['effects']['buff_defense'] = [
                'type' => 'buff_defense',
                'duration' => $skillToUse['base_duration'],
                'value' => $bonus,
            ];
        }

        $physBuffValue = ($actor['effects']['buff_phys_dmg']['value'] ?? 0) + ($actor['passives']['aura_dmg'] ?? 0);

        $isAoe = $skillToUse && !empty($skillToUse['is_aoe']) && in_array($effectType, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true);

        if ($isAoe) {
            $turns = [];
            foreach ($combatants as $idx => $candidate) {
                if ($candidate['side'] === $enemySide && $candidate['alive']) {
                    $turns[] = $this->resolveHitAgainstTarget($combatants, $actorIdx, $idx, $skillToUse, $effectType, $bonus, $physBuffValue, true);
                }
            }
        } else {
            $turns = [$this->resolveHitAgainstTarget($combatants, $actorIdx, $primaryTargetIdx, $skillToUse, $effectType, $bonus, $physBuffValue, false)];
        }

        // Dekrementacja własnych self-buffów aktora (buff_phys_dmg/buff_defense) - raz na
        // akcję, niezależnie od liczby trafionych celów przy AOE.
        foreach (['buff_phys_dmg', 'buff_defense'] as $buffKey) {
            if (isset($actor['effects'][$buffKey]) && $actor['effects'][$buffKey]['duration'] > 0) {
                $actor['effects'][$buffKey]['duration']--;
                if ($actor['effects'][$buffKey]['duration'] <= 0) {
                    unset($actor['effects'][$buffKey]);
                }
            }
        }

        return $turns;
    }

    /**
     * Wylicza pojedyncze trafienie aktora w jeden cel - używane zarówno dla zwykłego
     * ataku/skilla pojedynczego celu, jak i (wywoływane wielokrotnie z resolveTeamAction())
     * dla każdego trafionego przeciwnika skillem obszarowym (is_aoe). DoT na celu tyka
     * tylko dla trafień pojedynczych - w starciach AOE efekty obszarowe działają
     * wyłącznie na obrażeniach bezpośrednich/CC, nie na DoT (parytet z
     * `EncounterService::playerAttackAoeTarget()`).
     */
    private function resolveHitAgainstTarget(array &$combatants, int $actorIdx, int $targetIdx, ?array $skillToUse, ?string $effectType, float $bonus, float $physBuffValue, bool $isAoe): array
    {
        $actor = &$combatants[$actorIdx];
        $target = &$combatants[$targetIdx];

        $snap = $actor['snapshot'];
        $targetSnap = $target['snapshot'];
        $weaponType = $snap['weapon_type'] ?? 'barehands';

        $attrs = $snap['attributes'] ?? [];
        $str = $attrs['str'] ?? 0;
        $int = $attrs['int'] ?? 0;
        $agi = $attrs['agi'] ?? 0;
        // Naprawiony bug: $eq nigdy nie było przypisane w tej metodzie, więc każdy
        // odczyt $eq['x'] ?? 0 poniżej cichutko zwracał 0 - w praktyce ekwipunek
        // (armor pen, magic burst, "strong vs hero", crit chance, atak
        // różdżki/dzwonu, procki z rollEquipmentProcs) nie miał żadnego wpływu na
        // starcia Wojny Gildii.
        $eq = $snap['equipment_stats'] ?? [];

        $isMagicSkill = (bool) ($skillToUse['is_magic'] ?? false);

        if ($isMagicSkill) {
            $rawStatBonus = match ($weaponType) {
                'bow', 'sword', 'dagger' => $str + $agi,
                'bell' => $str + $int,
                'wand' => $int * 2,
                'axe' => $str * 2,
                default => $str * 2,
            };
            if ($weaponType === 'wand') {
                $weaponAtkMin = (int) ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $weaponAtkMin = ($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0);
            } else {
                $weaponAtkMin = ($eq['magic_attack_min'] ?? 0) > 0 ? ($eq['magic_attack_min'] ?? 0) : ($eq['attack_min'] ?? 0);
                $weaponAtkMax = ($eq['magic_attack_max'] ?? 0) > 0 ? ($eq['magic_attack_max'] ?? 0) : ($eq['attack_max'] ?? 0);
            }
        } else {
            // Standard basic auto-attack (lub atak fizyczny)
            if ($weaponType === 'wand') {
                // Naprawiony bug (2026-08-05, follow-up rebalansu) - patrz identyczna
                // notatka w EncounterService::calculateDamage().
                $rawStatBonus = $int * 2;
                $weaponAtkMin = (int) ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $rawStatBonus = $str + $int;
                $weaponAtkMin = (int) ($eq['attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['attack_max'] ?? 0);
            } else {
                $rawStatBonus = match ($weaponType) {
                    'bow', 'sword', 'dagger' => $str + $agi,
                    'axe' => $str * 2,
                    default => $str * 2,
                };
                $weaponAtkMin = ($eq['attack_min'] ?? 0);
                $weaponAtkMax = ($eq['attack_max'] ?? 0);
            }
        }
        $statBonus = (int) round($rawStatBonus * Character::ATTRIBUTE_DAMAGE_MULTIPLIER);

        $baseDmgMin = 10 + $statBonus + ($snap['level'] * 1) + $weaponAtkMin;
        $baseDmgMax = 10 + $statBonus + ($snap['level'] * 1) + $weaponAtkMax;
        if ($baseDmgMax < $baseDmgMin) $baseDmgMax = $baseDmgMin;

        $damage = mt_rand($baseDmgMin, $baseDmgMax);

        if ($skillToUse && in_array($effectType, ['direct_dmg', 'direct', 'damage', 'aoe_dmg', 'freeze', 'stun'], true)) {
            $damage = (int) ($damage * $bonus);
        }

        if ($physBuffValue > 0) {
            $damage = (int) ($damage * (1 + $physBuffValue));
        }

        // Siła/Mądrość (drzewko Mistrzostwa) - patrz identyczna logika w EncounterService::calculateDamage().
        $isMagicDamage = $isMagicSkill || $weaponType === 'wand';
        $championDmgPct = $isMagicDamage
            ? ($actor['championBonuses']['magic_dmg_pct'] ?? 0)
            : ($actor['championBonuses']['phys_dmg_pct'] ?? 0);
        if ($championDmgPct > 0) {
            $damage = (int) round($damage * (1 + $championDmgPct));
        }

        // DoT (otrucie/podpalenie) tyka na celu przy każdym ataku wymierzonym w niego -
        // pomijane dla trafień AOE (patrz docblock powyżej).
        $dotDamage = 0;
        $dotType = null;
        if (!$isAoe && !empty($target['effects'])) {
            foreach ($target['effects'] as $id => &$eff) {
                if (in_array($eff['type'] ?? '', ['buff_phys_dmg', 'buff_defense'], true)) continue;

                if (($eff['duration'] ?? 0) > 0 && in_array($eff['type'] ?? '', ['poison', 'dot_poison', 'fire', 'dot_fire', 'bleed', 'dot_bleed'])) {
                    if (in_array($eff['type'] ?? '', ['bleed', 'dot_bleed'], true)) {
                        $targetCurrentHp = $target['hp'] ?? $target['maxHp'];
                        $dmg = max(1, (int) ($targetCurrentHp * $eff['value']));
                    } else {
                        $dmg = max(1, (int) ($target['maxHp'] * $eff['value']));
                    }
                    $dotDamage += $dmg;
                    $dotType = $eff['type'];
                    $eff['duration']--;
                }
            }
            unset($eff);
            $target['effects'] = array_filter($target['effects'], fn ($e) => ($e['duration'] ?? 0) > 0);
        }
        if ($dotDamage > 0) {
            $dotDamage = max(1, (int) round($dotDamage * self::PVP_DAMAGE_MULTIPLIER));
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

        // Defender's defense & Armor Penetration (Łuk / Infuzja różdżki)
        $defVit = $targetSnap['attributes']['vit'] ?? 1;
        $defEq = $targetSnap['equipment_stats'] ?? [];
        $defense = $defVit + ($targetSnap['level'] / 2) + ($defEq['defense'] ?? 0);

        // Procki otrucia/ogłuszenia/przebicia z ekwipunku - patrz rollEquipmentProcs()
        // na górze klasy. Naprawiony bug: wcześniej rzucane było DOPIERO na końcu tej
        // metody, mimo że sekcje armor pen/podwójnego ciosu poniżej odczytywały już
        // $procs['infusion_armor_pen']/$procs['double_strike'] - w praktyce te dwa
        // procki nigdy nie miały żadnego efektu w Wojnie Gildii.
        $procs = $this->rollEquipmentProcs($eq, $defEq);

        $armorPenPct = min(85, max(0, ($eq['armor_pen_pct'] ?? 0) + ($procs['infusion_armor_pen'] ?? 0)));
        if ($armorPenPct > 0) {
            $defense = (int) round($defense * (1.0 - ($armorPenPct / 100.0)));
        }

        $damage = max(1, $damage - ($defense * 0.2));

        // Podwójny Cios (Miecz / Infuzja różdżki)
        if (!empty($procs['double_strike'])) {
            $damage += max(1, (int) round($damage * 0.5));
        }

        // Redukcja obrażeń przychodzących z aktywnego buffa buff_defense celu (np. "Postawa
        // Tarczy") + Twardziel celu (Mistrzostwo) - capowana na 75%, ten sam wzorzec
        // bezpieczeństwa co passive_extra_attack.
        $targetDefenseBuffValue = min(0.75, max(0, ($target['effects']['buff_defense']['value'] ?? 0) + ($target['championBonuses']['dmg_reduction_pct'] ?? 0)));
        if ($targetDefenseBuffValue > 0) {
            $damage = max(1, $damage * (1 - $targetDefenseBuffValue));
        }

        // "Silny vs Bohaterów" - patrz identyczny bonus w PvPEncounterService::performAttack().
        $heroBonusPct = $eq['strong_vs_hero'] ?? 0;
        if ($heroBonusPct > 0) {
            $damage += $damage * ($heroBonusPct / 100);
        }

        // "Odporność na Ludzi" obrońcy (GvG) (cap do 75%)
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

        // "Silny na Obrażenia Magiczne" oraz "Odporność na Magię" przy starciu magicznym
        if ($isMagicSkill || $weaponType === 'wand') {
            $strongVsMagic = $eq['strong_vs_magic'] ?? 0;
            if ($strongVsMagic !== 0) {
                $damage = max(1, (int) round($damage * (1 + ($strongVsMagic / 100))));
            }
            $resistMagic = min(75, max(-75, $defEq['resist_magic'] ?? 0));
            if ($resistMagic !== 0) {
                $damage = max(1, (int) round($damage * (1 - ($resistMagic / 100))));
            }
        }

        $actingAgi = $attrs['agi'] ?? 1;
        $targetAgi = $targetSnap['attributes']['agi'] ?? 1;

        $baseCrit = 0.05 + ($actingAgi * 0.0015) + (($eq['crit_chance'] ?? 0) / 100);
        $critChance = max(0.03, min(1.00, $baseCrit));
        $isCrit = mt_rand(1, 1000) <= (int) round($critChance * 1000);

        $targetEq = $targetSnap['equipment_stats'] ?? [];
        // Zwinność celu zwiększa jego unik, Celność atakującego go kontruje (Mistrzostwo).
        $championDodgeMod = ($target['championBonuses']['dodge_pct'] ?? 0) - ($actor['championBonuses']['accuracy_pct'] ?? 0);
        $dodgeChance = max(0.00, min(0.30, 0.03 + ($targetAgi * 0.0006) + (($targetEq['dodge_chance'] ?? 0) / 100) + $championDodgeMod));
        $isMiss = mt_rand(1, 1000) <= (int) round($dodgeChance * 1000);

        $turn = [
            'actor_side' => $actor['side'],
            'actor_idx' => $actor['idx'],
            'actor_name' => $snap['name'] ?? null,
            'target_side' => $target['side'],
            'target_idx' => $target['idx'],
            'target_name' => $targetSnap['name'] ?? null,
        ];
        if ($isAoe) {
            $turn['aoe'] = true;
        }

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

            // Balans PvP - patrz stała PVP_DAMAGE_MULTIPLIER powyżej. Zastosowane na
            // samym końcu, po krytyku, żeby zredukować RÓWNIEŻ obrażenia krytyczne.
            $damage = max(1, (int) round($damage * self::PVP_DAMAGE_MULTIPLIER));

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
                $turn['effect_type'] = $effectType;

                if (in_array($effectType, ['freeze', 'stun'], true)) {
                    $ccDuration = max(1, (int) ($skillToUse['base_duration'] ?? 1));
                    $turn['cc_applied'] = ['type' => $effectType, 'duration' => $ccDuration];
                    $target['cc_turns'] = max($target['cc_turns'] ?? 0, $ccDuration);
                }
            }

            // Procki dot/cc z tej samej puli $procs wylosowanej wcześniej (przed sekcją
            // armor pen/podwójnego ciosu) - patrz rollEquipmentProcs() na górze klasy.
            if ($procs['dot']) {
                $target['effects']['equipment_poison'] = $procs['dot'];
            }
            if ($procs['cc']) {
                $mergedDuration = max($turn['cc_applied']['duration'] ?? 0, $procs['cc']['duration']);
                $turn['cc_applied'] = ['type' => $procs['cc']['type'], 'duration' => $mergedDuration];
                $target['cc_turns'] = max($target['cc_turns'] ?? 0, $mergedDuration);
            }
        }

        if ($target['hp'] <= 0) {
            $target['hp'] = 0;
            $target['alive'] = false;
        }

        return $turn;
    }

    /**
     * Aktor leczy się o % maksymalnego HP zamiast atakować (analogicznie do
     * `EncounterService::playerAttack()`/`PvPEncounterService::performAttack()` -
     * patrz docs/modules/skills.md pkt. 5). Aktywne DoT-y na uprzednio wybranym
     * celu nadal tykają tej samej tury.
     */
    private function resolveTeamHeal(array &$combatants, int $actorIdx, int $targetIdx, float $bonus, array $skillToUse): array
    {
        $actor = &$combatants[$actorIdx];
        $target = &$combatants[$targetIdx];

        $dotDamage = 0;
        $dotType = null;
        if (!empty($target['effects'])) {
            foreach ($target['effects'] as $id => &$eff) {
                if (in_array($eff['type'] ?? '', ['buff_phys_dmg', 'buff_defense'], true)) continue;

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

        if ($dotDamage > 0) {
            $dotDamage = max(1, (int) round($dotDamage * self::PVP_DAMAGE_MULTIPLIER));
            $target['hp'] = max(0, $target['hp'] - $dotDamage);
            if ($target['hp'] <= 0) {
                $target['hp'] = 0;
                $target['alive'] = false;
            }
        }

        $healAmount = max(1, (int) round($actor['maxHp'] * $bonus));
        $actor['hp'] = min($actor['maxHp'], $actor['hp'] + $healAmount);

        return [
            'actor_side' => $actor['side'],
            'actor_idx' => $actor['idx'],
            'actor_name' => $actor['snapshot']['name'] ?? null,
            'target_side' => $target['side'],
            'target_idx' => $target['idx'],
            'target_name' => $target['snapshot']['name'] ?? null,
            'type' => 'skill_heal',
            'skill_id' => $skillToUse['id'] ?? null,
            'skill_name' => $skillToUse['name'] ?? null,
            'effect_type' => 'heal',
            'value' => $healAmount,
            'healAmount' => $healAmount,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
            'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => false,
        ];
    }

    /**
     * Wylicza sumaryczne efekty pasywnych umiejętności (type = 'passive') z wyposażonego
     * decku danego combatanta 5v5 na daną turę. Pobiera MP.
     */
    private function computeTeamPassivesForTurn(array $snapshot, int &$actorMana): array
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

            $effVal = ($skill['base_value'] ?? 0) + (($effLevel - 1) * ($skill['scaling_value'] ?? 0));

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
                'mana' => $c['mana'] ?? 50,
                'maxMana' => $c['maxMana'] ?? 50,
                'alive' => $c['alive'],
            ];
        }, $combatants);
    }
}
