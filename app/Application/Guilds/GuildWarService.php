<?php

namespace App\Application\Guilds;

use App\Application\Shared\Result;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\GuildWar;
use App\Infrastructure\Persistence\GuildWarFight;
use App\Infrastructure\Persistence\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GuildWarService
{
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
     * Process all 5 fights of a guild war.
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

                // Shuffle defender roster to randomize matchups
                shuffle($defenderRoster);

                $challengerWins = 0;
                $defenderWins = 0;

                for ($i = 0; $i < min(5, count($challengerRoster), count($defenderRoster)); $i++) {
                    $challChar = Character::find($challengerRoster[$i]);
                    $defChar = Character::find($defenderRoster[$i]);

                    if (!$challChar || !$defChar) continue;

                    $challSnapshot = $challChar->createSnapshot();
                    $defSnapshot = $defChar->createSnapshot();

                    // Simulate mini-fight using snapshots directly
                    $fightResult = $this->simulateGvGFight($challSnapshot, $defSnapshot);

                    $winnerId = $fightResult['winner_id'];
                    if ($winnerId === $challChar->id) {
                        $challengerWins++;
                    } else {
                        $defenderWins++;
                    }

                    GuildWarFight::create([
                        'guild_war_id' => $war->id,
                        'fight_order' => $i + 1,
                        'challenger_character_id' => $challChar->id,
                        'defender_character_id' => $defChar->id,
                        'winner_character_id' => $winnerId,
                        'challenger_snapshot' => $challSnapshot,
                        'defender_snapshot' => $defSnapshot,
                        'turns' => $fightResult['turns'],
                        'combat_data' => $fightResult['combat_data'],
                    ]);
                }

                // Determine overall winner
                $challengerGuild = $war->challengerGuild;
                $defenderGuild = $war->defenderGuild;
                
                // Refresh models to get latest gold/gems
                $challengerGuild->refresh();
                $defenderGuild->refresh();
                
                $winnerGuild = $challengerWins > $defenderWins ? $challengerGuild : $defenderGuild;
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

                Log::info('Guild war finished', [
                    'war_id' => $war->id,
                    'winner' => $winnerGuild->name,
                    'score' => "{$challengerWins}:{$defenderWins}",
                    'gold_transferred' => $stolenGold,
                    'gems_transferred' => $stolenGems,
                ]);

                return Result::ok([
                    'war' => $war->fresh(),
                    'winner' => $winnerGuild,
                    'loser' => $loserGuild,
                    'score' => ['challenger' => $challengerWins, 'defender' => $defenderWins],
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

    private function simulateGvGFight(array $attackerSnap, array $defenderSnap): array
    {
        $attackerHp = $attackerSnap['max_hp'];
        $defenderHp = $defenderSnap['max_hp'];

        $attackerAgi = $attackerSnap['attributes']['agi'] ?? 0;
        $defenderAgi = $defenderSnap['attributes']['agi'] ?? 0;
        $attackerFirst = $attackerAgi >= $defenderAgi;

        $turns = [];
        $turnCount = 0;
        $maxTurns = 50;

        while ($attackerHp > 0 && $defenderHp > 0 && $turnCount < $maxTurns) {
            $isAttackerTurn = $attackerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            $result = $this->gvgAttack($attackerSnap, $defenderSnap, $attackerHp, $defenderHp, $isAttackerTurn ? 'attacker' : 'defender');
            $attackerHp = $result['attackerHp'];
            $defenderHp = $result['defenderHp'];

            $turns[] = $result;
            $turnCount++;
        }

        // Determine winner
        $winnerId = null;
        if ($defenderHp <= 0) {
            $winnerId = $attackerSnap['character_id'];
        } elseif ($attackerHp <= 0) {
            $winnerId = $defenderSnap['character_id'];
        } else {
            $aPct = $attackerHp / $attackerSnap['max_hp'];
            $dPct = $defenderHp / $defenderSnap['max_hp'];
            $winnerId = $aPct >= $dPct ? $attackerSnap['character_id'] : $defenderSnap['character_id'];
        }

        return [
            'winner_id' => $winnerId,
            'turns' => $turns,
            'combat_data' => [
                'attacker_max_hp' => $attackerSnap['max_hp'],
                'defender_max_hp' => $defenderSnap['max_hp'],
            ],
        ];
    }

    private function gvgAttack(array $atkSnap, array $defSnap, int $atkHp, int $defHp, string $actor): array
    {
        $actingSnap = $actor === 'attacker' ? $atkSnap : $defSnap;
        $targetSnap = $actor === 'attacker' ? $defSnap : $atkSnap;

        $str = $actingSnap['attributes']['str'] ?? 1;
        $eq = $actingSnap['equipment_stats'] ?? [];
        $dmgMin = 10 + ($str * 2) + ($actingSnap['level'] * 1) + ($eq['attack_min'] ?? 0);
        $dmgMax = 10 + ($str * 2) + ($actingSnap['level'] * 1) + ($eq['attack_max'] ?? 0);
        if ($dmgMax < $dmgMin) $dmgMax = $dmgMin;
        $damage = mt_rand($dmgMin, $dmgMax);

        $defVit = $targetSnap['attributes']['vit'] ?? 1;
        $defEq = $targetSnap['equipment_stats'] ?? [];
        $defense = $defVit + ($targetSnap['level'] / 2) + ($defEq['defense'] ?? 0);
        $damage = max(1, $damage - ($defense / 2));

        $agi = $actingSnap['attributes']['agi'] ?? 1;
        $critChance = min(0.3, 0.05 + ($agi * 0.01) + (($eq['crit_chance'] ?? 0) / 100));
        $isCrit = mt_rand(1, 100) <= ($critChance * 100);
        $isMiss = mt_rand(1, 100) <= 5;

        if ($isMiss) {
            return [
                'actor' => $actor,
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'attackerHp' => $atkHp,
                'defenderHp' => $defHp,
            ];
        }

        if ($isCrit) $damage = (int)($damage * 1.5);

        if ($actor === 'attacker') {
            return [
                'actor' => 'attacker', 'type' => 'hit', 'value' => (int)$damage, 'crit' => $isCrit,
                'attackerHp' => $atkHp, 'defenderHp' => max(0, $defHp - (int)$damage),
            ];
        } else {
            return [
                'actor' => 'defender', 'type' => 'hit', 'value' => (int)$damage, 'crit' => $isCrit,
                'attackerHp' => max(0, $atkHp - (int)$damage), 'defenderHp' => $defHp,
            ];
        }
    }
}
