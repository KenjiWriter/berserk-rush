<?php

use Illuminate\Database\Migrations\Migration;
use App\Infrastructure\Persistence\ItemInstance;

return new class extends Migration
{
    public function up(): void
    {
        $instances = ItemInstance::whereNotNull('roll_stats')->get();

        foreach ($instances as $instance) {
            $rollStats = $instance->roll_stats;
            if (isset($rollStats['enchants']['hp_bonus'])) {
                $hpBonus = (int) $rollStats['enchants']['hp_bonus'];
                if ($hpBonus > 50) {
                    $rollStats['enchants']['hp_bonus'] = 50;
                    $instance->roll_stats = $rollStats;
                    $instance->save();
                }
            }
        }
    }

    public function down(): void
    {
        // No reverse operation needed for bugfix
    }
};
