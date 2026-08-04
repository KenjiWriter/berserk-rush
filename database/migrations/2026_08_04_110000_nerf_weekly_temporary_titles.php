<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $weeklyTitles = [
            // Pokonane Potwory
            'Top 1 Łowca' => ['strong_vs_monsters' => 5, 'attack' => 20],
            'Top 2 Łowca' => ['strong_vs_monsters' => 3, 'attack' => 12],
            'Top 3 Łowca' => ['strong_vs_monsters' => 1, 'attack' => 6],

            // DMG World Bossa
            'Top 1 Pogromca Bossów' => ['strong_vs_bosses' => 5, 'crit_chance' => 2],
            'Top 2 Pogromca Bossów' => ['strong_vs_bosses' => 3, 'crit_chance' => 1],
            'Top 3 Pogromca Bossów' => ['strong_vs_bosses' => 1, 'crit_chance' => 0.5],

            // Ukończone Lochy
            'Top 1 Zdobywca Lochów' => ['armor_pen_pct' => 5, 'defense' => 15],
            'Top 2 Zdobywca Lochów' => ['armor_pen_pct' => 3, 'defense' => 10],
            'Top 3 Zdobywca Lochów' => ['armor_pen_pct' => 1, 'defense' => 5],

            // Wbite Poziomy
            'Top 1 Mistrz Doświadczenia' => ['exp_bonus' => 5, 'all_attributes' => 10],
            'Top 2 Mistrz Doświadczenia' => ['exp_bonus' => 3, 'all_attributes' => 6],
            'Top 3 Mistrz Doświadczenia' => ['exp_bonus' => 1, 'all_attributes' => 3],

            // Bossowie na Mapach
            'Top 1 Łowca Czempionów' => ['double_drop_chance' => 5, 'attack' => 15],
            'Top 2 Łowca Czempionów' => ['double_drop_chance' => 3, 'attack' => 10],
            'Top 3 Łowca Czempionów' => ['double_drop_chance' => 1, 'attack' => 5],

            // Zwycięstwa na Arenie
            'Top 1 Gladiator' => ['strong_vs_hero' => 5, 'dodge_chance' => 2],
            'Top 2 Gladiator' => ['strong_vs_hero' => 3, 'dodge_chance' => 1],
            'Top 3 Gladiator' => ['strong_vs_hero' => 1, 'dodge_chance' => 0.5],
        ];

        foreach ($weeklyTitles as $name => $statsBonus) {
            DB::table('titles')
                ->where('name', $name)
                ->update(['stats_bonus' => json_encode($statsBonus)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $weeklyTitles = [
            'Top 1 Łowca' => ['strong_vs_monsters' => 10, 'attack' => 50],
            'Top 2 Łowca' => ['strong_vs_monsters' => 5, 'attack' => 30],
            'Top 3 Łowca' => ['strong_vs_monsters' => 3, 'attack' => 15],

            'Top 1 Pogromca Bossów' => ['strong_vs_bosses' => 10, 'crit_chance' => 5],
            'Top 2 Pogromca Bossów' => ['strong_vs_bosses' => 5, 'crit_chance' => 3],
            'Top 3 Pogromca Bossów' => ['strong_vs_bosses' => 3, 'crit_chance' => 2],

            'Top 1 Zdobywca Lochów' => ['armor_pen_pct' => 10, 'defense' => 40],
            'Top 2 Zdobywca Lochów' => ['armor_pen_pct' => 5, 'defense' => 25],
            'Top 3 Zdobywca Lochów' => ['armor_pen_pct' => 3, 'defense' => 12],

            'Top 1 Mistrz Doświadczenia' => ['exp_bonus' => 10, 'all_attributes' => 30],
            'Top 2 Mistrz Doświadczenia' => ['exp_bonus' => 5, 'all_attributes' => 20],
            'Top 3 Mistrz Doświadczenia' => ['exp_bonus' => 3, 'all_attributes' => 10],

            'Top 1 Łowca Czempionów' => ['double_drop_chance' => 10, 'attack' => 40],
            'Top 2 Łowca Czempionów' => ['double_drop_chance' => 5, 'attack' => 25],
            'Top 3 Łowca Czempionów' => ['double_drop_chance' => 3, 'attack' => 12],

            'Top 1 Gladiator' => ['strong_vs_hero' => 10, 'dodge_chance' => 5],
            'Top 2 Gladiator' => ['strong_vs_hero' => 5, 'dodge_chance' => 3],
            'Top 3 Gladiator' => ['strong_vs_hero' => 3, 'dodge_chance' => 2],
        ];

        foreach ($weeklyTitles as $name => $statsBonus) {
            DB::table('titles')
                ->where('name', $name)
                ->update(['stats_bonus' => json_encode($statsBonus)]);
        }
    }
};
