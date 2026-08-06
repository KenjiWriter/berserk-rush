<?php

namespace App\Application\Characters;

use App\Infrastructure\Persistence\Character;
use App\Application\Shared\Result;
use App\Application\Characters\DTOs\LevelUpResult;
use App\Domain\Characters\Events\CharacterLeveledUp;
use App\Application\Rankings\WeeklyRankingService;
use Illuminate\Support\Facades\DB;

class LevelUpService
{
    public const MAX_LEVEL = 99;

    /**
     * Globalny mnożnik krzywej XP (Faza 1 rebalansu, 2026-08-05 - patrz feedback
     * graczy aso666/Swiiezzy/swag: max poziom wbijany w ~3 dni, "nawet tydzień byłby
     * za szybko"). Kształt krzywej (`xpToNext()`) pozostaje BEZ ZMIAN - mnożnik tylko
     * proporcjonalnie wydłuża czas na każdym poziomie, żeby relacje tempa pomiędzy
     * wczesną/środkową/późną grą się nie rozjechały. x6 orientacyjnie przekłada
     * dotychczasowe ~3 dni do maxa w ~2.5-3 tygodnie - do doprecyzowania realnymi
     * danymi telemetrycznymi XP/h po wdrożeniu (patrz `Session Tracker`,
     * `docs/modules/combat.md` pkt 4).
     */
    private const XP_CURVE_MULTIPLIER = 6.0;

    public function checkAndApply(Character $character): Result
    {
        try {
            return DB::transaction(function () use ($character) {
                $levelUps = [];
                $originalLevel = $character->level;
                $currentLevel = $character->level;
                $currentXp = $character->xp;

                $maxXpAtMaxLevel = max(0, $this->xpToNext(self::MAX_LEVEL) - 1);

                if ($currentLevel >= self::MAX_LEVEL) {
                    $currentLevel = self::MAX_LEVEL;
                    // Nadwyżka expa ponad cap 99 poziomu nie jest tracona - dopóki gracz nie
                    // dobił max poziomu championa (99(50)), zasila champion_xp zamiast
                    // przepadać. To JEDYNE niezawodne miejsce do przechwycenia tej nadwyżki:
                    // większość ścieżek nagradzania expem woła Character::increment('xp', ...),
                    // które NIE odpala eventu `saving` modelu (bypassuje pipeline zapisu Eloquenta),
                    // więc hook w Character::booted() sam w sobie by tego nie złapał - ten serwis
                    // jest jedynym wspólnym miejscem wołanym po KAŻDYM przyznaniu expa.
                    $overflow = max(0, $currentXp - $maxXpAtMaxLevel);
                    $currentXp = min($currentXp, $maxXpAtMaxLevel);

                    $updates = [];
                    if ($character->level !== self::MAX_LEVEL) {
                        $updates['level'] = self::MAX_LEVEL;
                    }
                    if ($character->xp !== $currentXp) {
                        $updates['xp'] = $currentXp;
                    }
                    if ($overflow > 0 && ($character->champion_level ?? 0) < \App\Application\Mastery\ChampionService::LEVEL_CAP) {
                        $updates['champion_xp'] = ($character->champion_xp ?? 0) + $overflow;
                    }
                    if (!empty($updates)) {
                        $character->update($updates);
                    }

                    if ($character->user) {
                        $character->user->checkAndRepairTutorialStage($character);
                    }

                    return Result::ok(new LevelUpResult(
                        levelUps: [],
                        newLevel: self::MAX_LEVEL,
                        pointsGained: 0,
                        hadLevelUp: false
                    ));
                }

                // Automatyczna donacja EXP do gildii: gdy pasek doświadczenia
                // przekroczy 50%, nadwyżka ponad ten próg trafia do skarbca
                // gildii, a graczowi zostaje dokładnie połowa paska. Przycinanie
                // wykonujemy po KAŻDYM pojedynczym awansie wewnątrz pętli (nie
                // dopiero raz na końcu), żeby duża jednorazowa premia XP (np.
                // odbiór nagrody z Lustra po kilku godzinach) nie mogła nabić
                // kilku poziomów naraz, zanim nadwyżka zostanie oddana gildii.
                $autoDonatedXp = 0;
                $autoDonateActive = $character->auto_donate_exp_guild && $character->guild_id;

                // Check for level ups
                while ($currentLevel < self::MAX_LEVEL && $currentXp >= $this->xpToNext($currentLevel)) {
                    $currentXp -= $this->xpToNext($currentLevel);
                    $currentLevel++;

                    $levelUps[] = [
                        'from' => $currentLevel - 1,
                        'to' => $currentLevel
                    ];

                    if ($autoDonateActive && $currentLevel < self::MAX_LEVEL) {
                        $donateThreshold = (int) floor($this->xpToNext($currentLevel) * 0.5);
                        if ($currentXp > $donateThreshold) {
                            $autoDonatedXp += $currentXp - $donateThreshold;
                            $currentXp = $donateThreshold;
                        }
                    }
                }

                $championXpGain = 0;
                if ($currentLevel >= self::MAX_LEVEL) {
                    $currentLevel = self::MAX_LEVEL;
                    $championXpGain = max(0, $currentXp - $maxXpAtMaxLevel);
                    $currentXp = min($currentXp, $maxXpAtMaxLevel);
                } elseif ($autoDonateActive) {
                    $donateThreshold = (int) floor($this->xpToNext($currentLevel) * 0.5);
                    if ($currentXp > $donateThreshold) {
                        $autoDonatedXp += $currentXp - $donateThreshold;
                        $currentXp = $donateThreshold;
                    }
                }

                if (!empty($levelUps) || $currentLevel !== $character->level || $currentXp !== $character->xp) {
                    $pointsGained = count($levelUps) * 3;

                    $updates = [
                        'level'              => $currentLevel,
                        'character_points'   => ($character->character_points ?? 0) + $pointsGained,
                        'skill_points'       => ($character->skill_points ?? 0) + (count($levelUps) * 3),
                        'xp'                 => $currentXp,
                        // Aktualizujemy timestamp każdego nowego poziomu — ranking sortuje ASC,
                        // więc kto WCZEŚNIEJ osiągnął dany poziom, jest wyżej w tabeli.
                        'max_level_reached_at' => !empty($levelUps) ? now() : $character->max_level_reached_at,
                    ];

                    if ($championXpGain > 0 && ($character->champion_level ?? 0) < \App\Application\Mastery\ChampionService::LEVEL_CAP) {
                        $updates['champion_xp'] = ($character->champion_xp ?? 0) + $championXpGain;
                    }

                    $character->update($updates);


                    $character->syncMissingPoints();

                    // Fire events for each level up
                    foreach ($levelUps as $levelUp) {
                        event(new CharacterLeveledUp(
                            $character->fresh(),
                            $levelUp['from'],
                            $levelUp['to']
                        ));

                        // Ranking tygodniowy: wbite poziomy
                        app(WeeklyRankingService::class)->incrementScore(
                            $character->id,
                            'levels_gained'
                        );
                    }
                }

                if ($autoDonatedXp > 0) {
                    $guild = \App\Models\Guild::find($character->guild_id);
                    if ($guild) {
                        $guild->addXp($autoDonatedXp);
                        \App\Models\GuildLog::create([
                            'guild_id' => $guild->id,
                            'character_id' => $character->id,
                            'action' => 'donate_exp_auto',
                            'amount' => $autoDonatedXp,
                        ]);
                    }
                }

                // Check and repair tutorial stage if character is level >= 5
                if ($character->user) {
                    $character->user->checkAndRepairTutorialStage($character);
                }

                $result = new LevelUpResult(
                    levelUps: $levelUps,
                    newLevel: $currentLevel,
                    pointsGained: empty($levelUps) ? 0 : count($levelUps) * 3,
                    hadLevelUp: !empty($levelUps)
                );

                return Result::ok($result);
            });
        } catch (\Exception $e) {
            return Result::error('LEVEL_UP_FAILED', 'Sprawdzanie awansu nie powiodło się', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    public function xpToNext(int $level): int
    {
        $base = 15 * pow($level, 2) + 50 * $level + 0.15 * pow($level, 4.1);
        if ($level > 85) {
            $base += 0.025 * pow($level - 85, 5.5);
        }
        return (int) round($base * self::XP_CURVE_MULTIPLIER);
    }
}
