<?php

namespace App\Jobs;

use App\Application\AntiCheat\AntiCheatDetectionService;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Job wykrywający nienaturalne tempo polowań (spam requestów do EncounterService::start()).
 *
 * UWAGA: celowo NIE implementuje ShouldQueue - analogicznie do WeeklyRankingRewardJob,
 * wykonywany synchronicznie przez scheduler co minutę.
 */
class DetectSuspiciousActivityJob
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        try {
            app(AntiCheatDetectionService::class)->detectKillRateAnomalies();
        } catch (\Throwable $e) {
            Log::error('DetectSuspiciousActivityJob failed: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
