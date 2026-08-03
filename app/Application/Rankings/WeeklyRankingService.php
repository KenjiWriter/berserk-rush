<?php

namespace App\Application\Rankings;

use App\Infrastructure\Persistence\WeeklyRankingEntry;
use App\Application\Mail\Actions\SendMailAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class WeeklyRankingService
{
    /**
     * Etykiety kategorii wyświetlane w UI.
     */
    public const CATEGORY_LABELS = [
        'monsters_killed'    => 'Pokonane Potwory',
        'dungeons_completed' => 'Ukończone Lochy',
        'world_boss_damage'  => 'DMG World Bossa',
        'levels_gained'      => 'Wbite Poziomy',
        'map_bosses_killed'  => 'Bossowie na Mapach',
        'arena_wins'         => 'Zwycięstwa na Arenie',
    ];

    /**
     * Ikony FA dla kategorii.
     */
    public const CATEGORY_ICONS = [
        'monsters_killed'    => 'fa-skull',
        'dungeons_completed' => 'fa-dungeon',
        'world_boss_damage'  => 'fa-fire-flame-curved',
        'levels_gained'      => 'fa-arrow-up',
        'map_bosses_killed'  => 'fa-crown',
        'arena_wins'         => 'fa-sword',
    ];

    /**
     * Kolory akcent dla kategorii (klasy Tailwind text-*).
     */
    public const CATEGORY_COLORS = [
        'monsters_killed'    => 'text-red-400',
        'dungeons_completed' => 'text-amber-400',
        'world_boss_damage'  => 'text-orange-400',
        'levels_gained'      => 'text-emerald-400',
        'map_bosses_killed'  => 'text-purple-400',
        'arena_wins'         => 'text-sky-400',
    ];

    /**
     * Nagrody gem według miejsca.
     */
    public const REWARDS = [
        1  => 300,
        2  => 250,
        3  => 200,
    ];
    public const REWARD_4_TO_10 = 100;

    /**
     * Zwraca poniedziałek bieżącego tygodnia (format Y-m-d).
     */
    public static function currentWeekStart(): string
    {
        return Carbon::now()->startOfWeek(Carbon::MONDAY)->toDateString();
    }

    /**
     * Atomowy upsert: dodaje $amount do score dla danej postaci, kategorii i bieżącego tygodnia.
     */
    public function incrementScore(string $characterId, string $category, int $amount = 1): void
    {
        if ($amount <= 0) {
            return;
        }

        if (!in_array($category, WeeklyRankingEntry::CATEGORIES)) {
            Log::warning("WeeklyRankingService: nieznana kategoria '{$category}'");
            return;
        }

        $weekStart = self::currentWeekStart();

        try {
            $entry = WeeklyRankingEntry::firstOrCreate(
                [
                    'character_id' => $characterId,
                    'week_start'   => $weekStart,
                    'category'     => $category,
                ],
                [
                    'score' => 0,
                ]
            );

            $entry->increment('score', $amount);
        } catch (\Throwable $e) {
            Log::error("WeeklyRankingService::incrementScore failed", [
                'character_id' => $characterId,
                'category'     => $category,
                'amount'       => $amount,
                'error'        => $e->getMessage(),
            ]);

            if (DB::transactionLevel() > 0) {
                throw $e;
            }
        }
    }

    /**
     * Zwraca Top 10 graczy dla danej kategorii i tygodnia.
     */
    public function getLeaderboard(string $category, string $weekStart): Collection
    {
        return WeeklyRankingEntry::with('character')
            ->where('week_start', $weekStart)
            ->where('category', $category)
            ->orderByDesc('score')
            ->limit(10)
            ->get();
    }

    /**
     * Zwraca pozycję i wynik danej postaci w rankingu.
     * ['rank' => int|null, 'score' => int]
     */
    public function getPlayerRank(string $characterId, string $category, string $weekStart): array
    {
        $entry = WeeklyRankingEntry::where('character_id', $characterId)
            ->where('week_start', $weekStart)
            ->where('category', $category)
            ->first();

        if (!$entry) {
            return ['rank' => null, 'score' => 0];
        }

        $rank = WeeklyRankingEntry::where('week_start', $weekStart)
            ->where('category', $category)
            ->where('score', '>', $entry->score)
            ->count() + 1;

        return ['rank' => $rank, 'score' => $entry->score];
    }

    /**
     * Zwraca pełne dane rankingów dla wszystkich kategorii (do modalu).
     * Struktura: [ category => ['leaderboard' => Collection, 'player' => ['rank', 'score']] ]
     */
    public function getAllRankingsForCharacter(string $characterId): array
    {
        $weekStart = self::currentWeekStart();
        $result = [];

        foreach (WeeklyRankingEntry::CATEGORIES as $category) {
            $result[$category] = [
                'leaderboard' => $this->getLeaderboard($category, $weekStart),
                'player'      => $this->getPlayerRank($characterId, $category, $weekStart),
            ];
        }

        return $result;
    }

    /**
     * Oblicza rankingi za zakończony tydzień i wysyła nagrody gemów mailowo.
     * Wywoływane co poniedziałek o 00:01 przez scheduler.
     */
    public function computeAndAwardWeekly(): void
    {
        // Poprzedni tydzień (tydzień, który właśnie się skończył)
        $weekStart = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();

        Log::info("WeeklyRankingService: rozliczanie tygodnia {$weekStart}");

        $sendMail = app(SendMailAction::class);

        foreach (WeeklyRankingEntry::CATEGORIES as $category) {
            $leaderboard = $this->getLeaderboard($category, $weekStart);

            if ($leaderboard->isEmpty()) {
                Log::info("WeeklyRankingService: brak wpisów dla {$category} w tygodniu {$weekStart}");
                continue;
            }

            $categoryLabel = self::CATEGORY_LABELS[$category] ?? $category;
            $rank = 1;

            foreach ($leaderboard as $entry) {
                $gems = $this->gemsForRank($rank);

                if ($gems > 0) {
                    $subject = "Nagroda za Ranking Tygodniowy — {$categoryLabel}";
                    $body    = "Gratulacje! Zajął{a/eś} #{$rank} miejsce w rankingu tygodniowym \"{$categoryLabel}\" z wynikiem {$entry->score}.\n\nOtrzymujesz {$gems} gemów!";

                    $sendMail->execute(
                        $entry->character_id,
                        $subject,
                        $body,
                        [['type' => 'gems', 'qty' => $gems]]
                    );

                    Log::info("WeeklyRankingService: wysłano {$gems} gemów dla #{$rank} w {$category}", [
                        'character_id' => $entry->character_id,
                    ]);
                }

                $rank++;
            }
        }

        // Opcjonalne: usuń stare wpisy (starsze niż 4 tygodnie) dla porządku w bazie
        $oldWeek = Carbon::now()->subWeeks(4)->startOfWeek(Carbon::MONDAY)->toDateString();
        WeeklyRankingEntry::where('week_start', '<', $oldWeek)->delete();

        Log::info("WeeklyRankingService: rozliczanie tygodnia {$weekStart} zakończone.");
    }

    /**
     * Zwraca liczbę gemów za daną pozycję.
     */
    public function gemsForRank(int $rank): int
    {
        return match (true) {
            $rank === 1            => self::REWARDS[1],
            $rank === 2            => self::REWARDS[2],
            $rank === 3            => self::REWARDS[3],
            $rank >= 4 && $rank <= 10 => self::REWARD_4_TO_10,
            default                => 0,
        };
    }

    /**
     * Zwraca datę i czas następnego resetu rankingów (najbliższy poniedziałek 00:01).
     */
    public static function nextResetAt(): Carbon
    {
        return Carbon::now()->next(Carbon::MONDAY)->startOfDay()->addMinute();
    }
}
