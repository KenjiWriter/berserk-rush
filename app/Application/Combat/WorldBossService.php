<?php

namespace App\Application\Combat;

use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WorldBossService
{
    public function tickHourly(): void
    {
        DB::beginTransaction();
        try {
            // 1. Distribute rewards for all world bosses
            $this->distributeRewards();

            // 2. Spawn new world bosses for each map
            $this->spawnWorldBosses();

            DB::commit();
            Log::info("WorldBossTick executed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("WorldBossTick failed: " . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    public function ensureBossesSpawned(): void
    {
        $worldBosses = Monster::where('rank', 'worldboss')->get();

        foreach ($worldBosses as $boss) {
            $exists = WorldBossInstance::where('monster_id', $boss->id)
                ->where('is_defeated', false)
                ->exists();

            if (!$exists) {
                WorldBossInstance::create([
                    'monster_id' => $boss->id,
                    'map_id' => $boss->map_id,
                    'total_hp' => $boss->stats['hp'] ?? 10000,
                    'current_hp' => $boss->stats['hp'] ?? 10000,
                    'is_defeated' => false,
                ]);
            }
        }
    }

    private function distributeRewards(): void
    {
        $allInstances = WorldBossInstance::all();

        // 01k4jpx94j70x2vv10b835key1 is the rusty key ID, with fallback to type='key'
        $keyTemplateId = '01k4jpx94j70x2vv10b835key1';
        $keyTemplate = ItemTemplate::find($keyTemplateId) ?? ItemTemplate::where('type', 'key')->first();

        if (!$keyTemplate) {
            Log::warning("Key template not found for World Boss rewards.");
            return;
        }

        foreach ($allInstances as $instance) {
            $topLogs = WorldBossDamageLog::select('character_id', \Illuminate\Support\Facades\DB::raw('SUM(damage) as damage'))
                ->where('world_boss_instance_id', $instance->id)
                ->groupBy('character_id')
                ->orderByDesc('damage')
                ->limit(10)
                ->get();

            $rank = 1;
            foreach ($topLogs as $log) {
                $keysToGive = 1;
                if ($rank === 1) $keysToGive = 5;
                elseif ($rank === 2) $keysToGive = 4;
                elseif ($rank === 3) $keysToGive = 3;
                elseif ($rank === 4) $keysToGive = 2;
                elseif ($rank >= 5 && $rank <= 10) $keysToGive = 1;

                for ($i = 0; $i < $keysToGive; $i++) {
                    ItemInstance::create([
                        'template_id' => $keyTemplateId,
                        'owner_character_id' => $log->character_id,
                        'location' => 'inventory',
                        'stack_size' => 1,
                        'rarity' => 'uncommon',
                        'roll_stats' => [],
                        'upgrade_level' => 0,
                        'bound_to_character' => false,
                        'version' => 1,
                    ]);
                }
                $rank++;
            }
        }

        // Cleanup old instances and logs
        WorldBossDamageLog::query()->delete();
        WorldBossInstance::query()->delete();
    }

    private function spawnWorldBosses(): void
    {
        // Get all world boss monsters
        $worldBosses = Monster::where('rank', 'worldboss')->get();

        foreach ($worldBosses as $boss) {
            WorldBossInstance::create([
                'monster_id' => $boss->id,
                'map_id' => $boss->map_id,
                'total_hp' => $boss->stats['hp'] ?? 10000,
                'current_hp' => $boss->stats['hp'] ?? 10000,
                'is_defeated' => false,
            ]);
        }
    }
}
