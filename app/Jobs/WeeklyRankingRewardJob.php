<?php

namespace App\Jobs;

use App\Application\Rankings\WeeklyRankingService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Job rozliczający rankingi tygodniowe i wysyłający nagrody gemów.
 *
 * UWAGA: celowo NIE implementuje ShouldQueue — analogicznie do WorldBossRewardJob.
 * Wykonywany synchronicznie przez scheduler w poniedziałek o 00:01,
 * jedyną zależnością infrastrukturalną jest działający cron.
 */
class WeeklyRankingRewardJob
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        Log::info('WeeklyRankingRewardJob: start');

        try {
            app(WeeklyRankingService::class)->computeAndAwardWeekly();
        } catch (\Throwable $e) {
            Log::error('WeeklyRankingRewardJob failed: ' . $e->getMessage(), [
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        Log::info('WeeklyRankingRewardJob: done');
    }
}
