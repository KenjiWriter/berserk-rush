<?php

namespace Database\Seeders;

use App\Infrastructure\Persistence\ChampionSkill;
use Illuminate\Database\Seeder;

class ChampionSkillSeeder extends Seeder
{
    /**
     * Seed the champion (Mistrzostwo) passive skill tree - 10 uniwersalnych
     * statystyk, wartości zgodne z projektem game designera (max 10 pkt/skill,
     * max 50 pkt do zdobycia łącznie - patrz App\Application\Mastery\ChampionService::LEVEL_CAP).
     */
    public function run(): void
    {
        $skills = [
            [
                'key' => 'strength',
                'name' => 'Siła',
                'description' => '+1% obrażeń fizycznych za poziom',
                'stat_type' => 'phys_dmg_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-hand-fist',
                'sort_order' => 1,
            ],
            [
                'key' => 'wisdom',
                'name' => 'Mądrość',
                'description' => '+1% obrażeń magicznych za poziom',
                'stat_type' => 'magic_dmg_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-brain',
                'sort_order' => 2,
            ],
            [
                'key' => 'endurance',
                'name' => 'Wytrzymałość',
                'description' => '+2% maksymalnego HP za poziom',
                'stat_type' => 'max_hp_pct',
                'bonus_per_point' => 2,
                'icon' => 'fa-solid fa-heart',
                'sort_order' => 3,
            ],
            [
                'key' => 'concentration',
                'name' => 'Koncentracja',
                'description' => '+1% regeneracji many za poziom',
                'stat_type' => 'mana_regen_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-droplet',
                'sort_order' => 4,
            ],
            [
                'key' => 'accuracy',
                'name' => 'Celność',
                'description' => '+1% szansy trafienia za poziom',
                'stat_type' => 'accuracy_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-bullseye',
                'sort_order' => 5,
            ],
            [
                'key' => 'agility',
                'name' => 'Zwinność',
                'description' => '+1% uniku za poziom',
                'stat_type' => 'dodge_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-wind',
                'sort_order' => 6,
            ],
            [
                'key' => 'toughness',
                'name' => 'Twardziel',
                'description' => '-1% otrzymywanych obrażeń za poziom',
                'stat_type' => 'dmg_reduction_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-shield-halved',
                'sort_order' => 7,
            ],
            [
                'key' => 'fortune',
                'name' => 'Fortuna',
                'description' => '+2% więcej złota z potworów za poziom',
                'stat_type' => 'gold_pct',
                'bonus_per_point' => 2,
                'icon' => 'fa-solid fa-coins',
                'sort_order' => 8,
            ],
            [
                'key' => 'treasure_hunter',
                'name' => 'Łowca Skarbów',
                'description' => '+1% szansy na lepszy łup za poziom',
                'stat_type' => 'loot_pct',
                'bonus_per_point' => 1,
                'icon' => 'fa-solid fa-gem',
                'sort_order' => 9,
            ],
            [
                'key' => 'knowledge',
                'name' => 'Wiedza',
                'description' => '+2% więcej doświadczenia za poziom',
                'stat_type' => 'exp_pct',
                'bonus_per_point' => 2,
                'icon' => 'fa-solid fa-book',
                'sort_order' => 10,
            ],
        ];

        foreach ($skills as $skill) {
            ChampionSkill::updateOrCreate(
                ['key' => $skill['key']],
                $skill + ['max_points' => 10]
            );
        }
    }
}
