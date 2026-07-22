<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CombatSkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            [
                'id' => Str::ulid(),
                'name' => 'Trująca Strzała',
                'description' => 'Strzała powleczona jadem pająka. Zadaje obrażenia z upływem czasu równe procentowi aktualnego HP przeciwnika.',
                'type' => 'active',
                'required_weapon_type' => 'bow',
                'effect_type' => 'poison', // zadaje % current HP
                'base_cooldown' => 5,
                'base_duration' => 3,
                'base_value' => 0.05, // 5%
                'scaling_value' => 0.01, // +1% per skill level
                'required_level' => 5,
                'unlock_cost' => 5,
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Ognista Strzała',
                'description' => 'Płonąca strzała zadająca obrażenia na podstawie maksymalnego HP przeciwnika przez krótki czas.',
                'type' => 'active',
                'required_weapon_type' => 'bow',
                'effect_type' => 'fire', // zadaje % max HP
                'base_cooldown' => 8,
                'base_duration' => 2,
                'base_value' => 0.02, // 2%
                'scaling_value' => 0.01, // +1% per skill level
                'required_level' => 10,
                'unlock_cost' => 8,
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Mocny Cios',
                'description' => 'Potężne uderzenie mieczem, ignorujące część obrony. Zwiększa siłę ataku fizycznego.',
                'type' => 'active',
                'required_weapon_type' => 'sword',
                'effect_type' => 'buff_phys_dmg',
                'base_cooldown' => 6,
                'base_duration' => 3,
                'base_value' => 0.20, // +20% dmg
                'scaling_value' => 0.05, // +5% dmg per skill level
                'required_level' => 5,
                'unlock_cost' => 5,
            ],
            [
                'id' => Str::ulid(),
                'name' => 'Rozłupanie',
                'description' => 'Miażdżący atak toporem o niesamowitej sile uderzenia bazowego.',
                'type' => 'active',
                'required_weapon_type' => 'axe',
                'effect_type' => 'direct_dmg',
                'base_cooldown' => 4,
                'base_duration' => 1,
                'base_value' => 1.5, // 150% dmg
                'scaling_value' => 0.2, // +20% per skill level
                'required_level' => 5,
                'unlock_cost' => 5,
            ]
        ];

        foreach ($skills as $skill) {
            DB::table('combat_skills')->insert(array_merge($skill, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
}
