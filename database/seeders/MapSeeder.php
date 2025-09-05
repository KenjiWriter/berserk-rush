<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Map;

class MapSeeder extends Seeder
{
    public function run(): void
    {
        $maps = [
            [
                'name' => 'Mroczny Las',
                'level_min' => 0,
                'level_max' => 15,
                'tier' => 1,
                'image_path' => 'maps/dark-forest.png',
            ],
            [
                'name' => 'Stare Ruiny',
                'level_min' => 10,
                'level_max' => 30,
                'tier' => 2,
                'image_path' => 'maps/old-ruins.png',
            ],
            [
                'name' => 'Jaskinia Trolli',
                'level_min' => 25,
                'level_max' => 45,
                'tier' => 3,
                'image_path' => 'maps/troll-cave.png',
            ],
            [
                'name' => 'Pustkowia Orków',
                'level_min' => 35,
                'level_max' => 60,
                'tier' => 4,
                'image_path' => 'maps/orc-wasteland.png',
            ],
            [
                'name' => 'Bagna Grozy',
                'level_min' => 50,
                'level_max' => 70,
                'tier' => 5,
                'image_path' => 'maps/horror-swamps.png',
            ],
            [
                'name' => 'Góry Cienia',
                'level_min' => 65,
                'level_max' => 80,
                'tier' => 6,
                'image_path' => 'maps/shadow-mountains.png',
            ],
            [
                'name' => 'Wieża Magów',
                'level_min' => 75,
                'level_max' => 85,
                'tier' => 7,
                'image_path' => 'maps/mage-tower.png',
            ],
            [
                'name' => 'Skażone Miasto',
                'level_min' => 85,
                'level_max' => 99,
                'tier' => 8,
                'image_path' => 'maps/corrupted-city.png',
            ],
        ];

        foreach ($maps as $mapData) {
            Map::create($mapData);
        }
    }
}