<?php

use Illuminate\Database\Migrations\Migration;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;

return new class extends Migration
{
    public function up(): void
    {
        $caps = [
            'double_strike_chance' => 15,
            'bleed_chance' => 25,
            'poison_chance' => 25,
            'armor_pen_pct' => 20,
            'magic_infusion_chance' => 20,
            'magic_burst_chance' => 25,
            'crit_chance' => 15,
        ];

        // 1. Clamp base_stats in ItemTemplates
        $templates = ItemTemplate::where('type', 'weapon')->get();
        foreach ($templates as $template) {
            $base = $template->base_stats ?? [];
            $modified = false;

            foreach ($caps as $stat => $cap) {
                if (isset($base[$stat])) {
                    if (is_array($base[$stat])) {
                        $min = min($cap, (int) $base[$stat][0]);
                        $max = min($cap, (int) $base[$stat][1]);
                        if ($max <= $min) {
                            $max = $min + 1;
                        }
                        if ($base[$stat][0] !== $min || $base[$stat][1] !== $max) {
                            $base[$stat] = [$min, $max];
                            $modified = true;
                        }
                    } elseif (is_numeric($base[$stat]) && $base[$stat] > $cap) {
                        $base[$stat] = $cap;
                        $modified = true;
                    }
                }
            }

            if ($modified) {
                $template->base_stats = $base;
                $template->save();
            }
        }

        // 2. Clamp roll_stats in ItemInstances
        $instances = ItemInstance::all();
        foreach ($instances as $instance) {
            $rolls = $instance->roll_stats ?? [];
            $modified = false;

            foreach ($caps as $stat => $cap) {
                if (isset($rolls[$stat]) && is_numeric($rolls[$stat]) && $rolls[$stat] > $cap) {
                    $rolls[$stat] = $cap;
                    $modified = true;
                }
            }

            if ($modified) {
                $instance->roll_stats = $rolls;
                $instance->save();
            }
        }
    }

    public function down(): void
    {
        // No reverse operation needed
    }
};
