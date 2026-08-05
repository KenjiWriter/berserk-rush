<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\LocationEvent;
use App\Infrastructure\Persistence\LocationEventUpgradeLevel;

/**
 * Statyczne dane rang eventów lokacji (T1-T6) i poziomów ulepszenia (0-5),
 * przeniesione 1:1 z arkusza projektowego użytkownika (2026-08-05).
 * Patrz docs/modules/location_events.md.
 */
class LocationEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            ['rank' => 1, 'name' => 'Jaskinie', 'spawn_chance_pct' => 20, 'monster_count_min' => 2, 'monster_count_max' => 5, 'attack_multiplier' => 1.00, 'reward_multiplier' => 1.00, 'group_chance_pct' => 0, 'group_max_size' => 0, 'chest_min' => 0, 'chest_max' => 1],
            ['rank' => 2, 'name' => 'Ruiny Wioski', 'spawn_chance_pct' => 15, 'monster_count_min' => 3, 'monster_count_max' => 6, 'attack_multiplier' => 1.00, 'reward_multiplier' => 1.00, 'group_chance_pct' => 0, 'group_max_size' => 0, 'chest_min' => 0, 'chest_max' => 1],
            ['rank' => 3, 'name' => 'Kaplica na Cmentarzu', 'spawn_chance_pct' => 10, 'monster_count_min' => 4, 'monster_count_max' => 7, 'attack_multiplier' => 1.00, 'reward_multiplier' => 1.00, 'group_chance_pct' => 0, 'group_max_size' => 0, 'chest_min' => 1, 'chest_max' => 1],
            ['rank' => 4, 'name' => 'Stara Warownia', 'spawn_chance_pct' => 5, 'monster_count_min' => 5, 'monster_count_max' => 8, 'attack_multiplier' => 1.10, 'reward_multiplier' => 1.10, 'group_chance_pct' => 5, 'group_max_size' => 2, 'chest_min' => 1, 'chest_max' => 2],
            ['rank' => 5, 'name' => 'Twierdza Upadłego Króla', 'spawn_chance_pct' => 4, 'monster_count_min' => 6, 'monster_count_max' => 9, 'attack_multiplier' => 1.20, 'reward_multiplier' => 1.20, 'group_chance_pct' => 5, 'group_max_size' => 3, 'chest_min' => 2, 'chest_max' => 2],
            ['rank' => 6, 'name' => 'Przeklęta Metropolia', 'spawn_chance_pct' => 2, 'monster_count_min' => 7, 'monster_count_max' => 10, 'attack_multiplier' => 1.30, 'reward_multiplier' => 1.30, 'group_chance_pct' => 5, 'group_max_size' => 4, 'chest_min' => 3, 'chest_max' => 3],
        ];

        foreach ($events as $event) {
            LocationEvent::updateOrCreate(['rank' => $event['rank']], $event);
        }

        $upgradeLevels = [
            ['level' => 0, 'roll_chance_pct' => 30, 'monster_count_delta_min' => 0, 'monster_count_delta_max' => 0, 'attack_multiplier' => 1.00, 'hp_multiplier' => 1.00, 'exp_multiplier' => 1.00, 'drop_multiplier' => 1.00, 'bonus_group_chance_pct' => 0, 'chest_bonus_min' => 1, 'chest_bonus_max' => 1],
            ['level' => 1, 'roll_chance_pct' => 25, 'monster_count_delta_min' => 0, 'monster_count_delta_max' => 1, 'attack_multiplier' => 1.10, 'hp_multiplier' => 1.15, 'exp_multiplier' => 1.08, 'drop_multiplier' => 1.05, 'bonus_group_chance_pct' => 0, 'chest_bonus_min' => 1, 'chest_bonus_max' => 1],
            ['level' => 2, 'roll_chance_pct' => 18, 'monster_count_delta_min' => 1, 'monster_count_delta_max' => 1, 'attack_multiplier' => 1.22, 'hp_multiplier' => 1.32, 'exp_multiplier' => 1.18, 'drop_multiplier' => 1.12, 'bonus_group_chance_pct' => 3, 'chest_bonus_min' => 2, 'chest_bonus_max' => 2],
            ['level' => 3, 'roll_chance_pct' => 12, 'monster_count_delta_min' => 1, 'monster_count_delta_max' => 2, 'attack_multiplier' => 1.36, 'hp_multiplier' => 1.52, 'exp_multiplier' => 1.30, 'drop_multiplier' => 1.20, 'bonus_group_chance_pct' => 6, 'chest_bonus_min' => 2, 'chest_bonus_max' => 3],
            ['level' => 4, 'roll_chance_pct' => 9, 'monster_count_delta_min' => 2, 'monster_count_delta_max' => 2, 'attack_multiplier' => 1.52, 'hp_multiplier' => 1.75, 'exp_multiplier' => 1.45, 'drop_multiplier' => 1.30, 'bonus_group_chance_pct' => 10, 'chest_bonus_min' => 3, 'chest_bonus_max' => 3],
            ['level' => 5, 'roll_chance_pct' => 6, 'monster_count_delta_min' => 2, 'monster_count_delta_max' => 3, 'attack_multiplier' => 1.70, 'hp_multiplier' => 2.00, 'exp_multiplier' => 1.65, 'drop_multiplier' => 1.45, 'bonus_group_chance_pct' => 15, 'chest_bonus_min' => 4, 'chest_bonus_max' => 5],
        ];

        foreach ($upgradeLevels as $level) {
            LocationEventUpgradeLevel::updateOrCreate(['level' => $level['level']], $level);
        }
    }
}
