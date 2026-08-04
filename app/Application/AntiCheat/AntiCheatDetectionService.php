<?php

namespace App\Application\AntiCheat;

use App\Infrastructure\Persistence\AntiCheatFlag;
use App\Infrastructure\Persistence\Encounter;
use Illuminate\Support\Facades\DB;

class AntiCheatDetectionService
{
    public function detectKillRateAnomalies(): void
    {
        $config = config('anticheat.kill_rate');
        $windowMinutes = $config['window_minutes'];
        $mediumThreshold = $config['medium_threshold'];
        $highThreshold = $config['high_threshold'];
        $dedupMinutes = $config['dedup_minutes'];

        $rows = Encounter::whereIn('state', ['win', 'lose'])
            ->where('created_at', '>=', now()->subMinutes($windowMinutes))
            ->groupBy('character_id')
            ->select('character_id', DB::raw('count(*) as kills'))
            ->having('kills', '>=', $mediumThreshold)
            ->get();

        foreach ($rows as $row) {
            $severity = $row->kills >= $highThreshold ? 'high' : 'medium';

            $details = [
                'kills_per_minute' => round($row->kills / $windowMinutes, 1),
            ];

            $existingOpenFlag = AntiCheatFlag::open()
                ->where('character_id', $row->character_id)
                ->where('type', 'kill_rate')
                ->where('created_at', '>=', now()->subMinutes($dedupMinutes))
                ->latest('id')
                ->first();

            if ($existingOpenFlag) {
                $existingOpenFlag->update([
                    'severity' => $severity,
                    'metric_value' => $row->kills,
                    'threshold' => $mediumThreshold,
                    'details' => $details,
                ]);
                continue;
            }

            AntiCheatFlag::create([
                'character_id' => $row->character_id,
                'type' => 'kill_rate',
                'severity' => $severity,
                'metric_value' => $row->kills,
                'threshold' => $mediumThreshold,
                'window_minutes' => $windowMinutes,
                'details' => $details,
                'status' => 'open',
            ]);
        }
    }
}
