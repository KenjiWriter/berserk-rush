<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;

class PotionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $potions = [
            [
                'id' => 'potion-str-s',
                'name' => 'Mikstura Siły (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['str_bonus' => 10, 'duration_minutes' => 15],
                'description' => 'Zwiększa obrażenia poprzez dodanie siły na 15 minut.',
            ],
            [
                'id' => 'potion-str-m',
                'name' => 'Mikstura Siły (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['str_bonus' => 25, 'duration_minutes' => 20],
                'description' => 'Znacznie zwiększa obrażenia poprzez dodanie siły na 20 minut.',
            ],
            [
                'id' => 'potion-def-s',
                'name' => 'Mikstura Obrony (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['defense' => 10, 'duration_minutes' => 15],
                'description' => 'Zwiększa pancerz na 15 minut.',
            ],
            [
                'id' => 'potion-def-m',
                'name' => 'Mikstura Obrony (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['defense' => 25, 'duration_minutes' => 20],
                'description' => 'Znacznie zwiększa pancerz na 20 minut.',
            ],
            [
                'id' => 'potion-crit-s',
                'name' => 'Mikstura Szału (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['crit_chance' => 5, 'duration_minutes' => 15],
                'description' => 'Zwiększa szansę na cios krytyczny na 15 minut.',
            ],
            [
                'id' => 'potion-crit-m',
                'name' => 'Mikstura Szału (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['crit_chance' => 10, 'duration_minutes' => 20],
                'description' => 'Znacznie zwiększa szansę na cios krytyczny na 20 minut.',
            ],
            [
                'id' => 'potion-hp-s',
                'name' => 'Eliksir Życia (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['hp_bonus' => 50, 'duration_minutes' => 15],
                'description' => 'Zwiększa maksymalne punkty zdrowia na 15 minut.',
            ],
            [
                'id' => 'potion-hp-m',
                'name' => 'Eliksir Życia (M)',
                'type' => 'consumable',
                'level_requirement' => 10,
                'base_stats' => ['hp_bonus' => 120, 'duration_minutes' => 20],
                'description' => 'Znacznie zwiększa maksymalne punkty zdrowia na 20 minut.',
            ],
            [
                'id' => 'potion-agi-s',
                'name' => 'Mikstura Uniku (S)',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['agi_bonus' => 10, 'duration_minutes' => 15],
                'description' => 'Zwiększa zwinność (i szansę na unik/rozpoczęcie) na 15 minut.',
            ],
            [
                'id' => 'potion-exp-special',
                'name' => 'Specjalna Mikstura Doświadczenia',
                'type' => 'consumable',
                'level_requirement' => 1,
                'base_stats' => ['exp_bonus' => 20, 'duration_minutes' => 10],
                'description' => 'Tajemniczy wywar zwiększający zdobywane doświadczenie z potworów o 20% przez 10 minut.',
                'is_tradeable' => false,
            ],
        ];

        foreach ($potions as $potion) {
            ItemTemplate::updateOrCreate(['id' => $potion['id']], $potion);
        }
    }
}
