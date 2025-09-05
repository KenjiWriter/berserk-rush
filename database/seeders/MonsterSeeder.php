<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;

class MonsterSeeder extends Seeder
{
    public function run(): void
    {
        $monstersByMap = [
            'Mroczny Las' => [
                [
                    'name' => 'Wilk Leśny',
                    'level' => 3,
                    'stats' => ['hp' => 40, 'atk' => 7, 'def' => 2, 'agi' => 6, 'int' => 1, 'crit' => 0.05, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Nietoperz Jaskiniowy',
                    'level' => 4,
                    'stats' => ['hp' => 32, 'atk' => 6, 'def' => 1, 'agi' => 8, 'int' => 1, 'crit' => 0.07, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Suchodrzew',
                    'level' => 6,
                    'stats' => ['hp' => 58, 'atk' => 9, 'def' => 4, 'agi' => 3, 'int' => 1, 'crit' => 0.03, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Goblin Zwiadowca',
                    'level' => 8,
                    'stats' => ['hp' => 50, 'atk' => 10, 'def' => 3, 'agi' => 9, 'int' => 2, 'crit' => 0.08, 'dodge' => 0.06],
                    'abilities' => []
                ]
            ],
            'Stare Ruiny' => [
                [
                    'name' => 'Szkielet Wojownik',
                    'level' => 14,
                    'stats' => ['hp' => 110, 'atk' => 18, 'def' => 9, 'agi' => 8, 'int' => 2, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Duch Strażnik',
                    'level' => 18,
                    'stats' => ['hp' => 95, 'atk' => 16, 'def' => 6, 'agi' => 11, 'int' => 8, 'crit' => 0.07, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Ghul',
                    'level' => 20,
                    'stats' => ['hp' => 130, 'atk' => 22, 'def' => 10, 'agi' => 8, 'int' => 3, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Upiorny Łucznik',
                    'level' => 22,
                    'stats' => ['hp' => 105, 'atk' => 24, 'def' => 8, 'agi' => 14, 'int' => 4, 'crit' => 0.10, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Jaskinia Trolli' => [
                [
                    'name' => 'Troll Paskudnik',
                    'level' => 26,
                    'stats' => ['hp' => 220, 'atk' => 30, 'def' => 18, 'agi' => 6, 'int' => 2, 'crit' => 0.05, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Szaman',
                    'level' => 29,
                    'stats' => ['hp' => 200, 'atk' => 26, 'def' => 14, 'agi' => 7, 'int' => 14, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Ogr Rozłupywacz',
                    'level' => 32,
                    'stats' => ['hp' => 260, 'atk' => 36, 'def' => 20, 'agi' => 6, 'int' => 3, 'crit' => 0.07, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Jaskiniowy Nietoperz Alfa',
                    'level' => 33,
                    'stats' => ['hp' => 180, 'atk' => 28, 'def' => 10, 'agi' => 16, 'int' => 4, 'crit' => 0.10, 'dodge' => 0.07],
                    'abilities' => []
                ]
            ],
            'Pustkowia Orków' => [
                [
                    'name' => 'Orczy Zwiad',
                    'level' => 37,
                    'stats' => ['hp' => 260, 'atk' => 40, 'def' => 18, 'agi' => 14, 'int' => 4, 'crit' => 0.09, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Berserker',
                    'level' => 42,
                    'stats' => ['hp' => 320, 'atk' => 52, 'def' => 20, 'agi' => 12, 'int' => 3, 'crit' => 0.12, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Szaman Krwi',
                    'level' => 46,
                    'stats' => ['hp' => 290, 'atk' => 45, 'def' => 16, 'agi' => 10, 'int' => 20, 'crit' => 0.08, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Dowódca Watahy',
                    'level' => 48,
                    'stats' => ['hp' => 350, 'atk' => 58, 'def' => 24, 'agi' => 13, 'int' => 6, 'crit' => 0.12, 'dodge' => 0.05],
                    'abilities' => []
                ]
            ],
            'Bagna Grozy' => [
                [
                    'name' => 'Topielec',
                    'level' => 52,
                    'stats' => ['hp' => 360, 'atk' => 55, 'def' => 24, 'agi' => 12, 'int' => 8, 'crit' => 0.08, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Wiedźmia Straż',
                    'level' => 58,
                    'stats' => ['hp' => 340, 'atk' => 50, 'def' => 22, 'agi' => 14, 'int' => 26, 'crit' => 0.10, 'dodge' => 0.07],
                    'abilities' => []
                ],
                [
                    'name' => 'Drzewiec Plugawy',
                    'level' => 60,
                    'stats' => ['hp' => 420, 'atk' => 62, 'def' => 30, 'agi' => 10, 'int' => 10, 'crit' => 0.06, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Hydra Bagienna',
                    'level' => 64,
                    'stats' => ['hp' => 480, 'atk' => 70, 'def' => 28, 'agi' => 16, 'int' => 18, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Góry Cienia' => [
                [
                    'name' => 'Wilk Cienia',
                    'level' => 66,
                    'stats' => ['hp' => 420, 'atk' => 68, 'def' => 26, 'agi' => 22, 'int' => 8, 'crit' => 0.14, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Golem Bazaltowy',
                    'level' => 70,
                    'stats' => ['hp' => 520, 'atk' => 74, 'def' => 40, 'agi' => 8, 'int' => 6, 'crit' => 0.06, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Harpia',
                    'level' => 72,
                    'stats' => ['hp' => 410, 'atk' => 66, 'def' => 22, 'agi' => 24, 'int' => 10, 'crit' => 0.14, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Wędrowny Czarownik',
                    'level' => 74,
                    'stats' => ['hp' => 450, 'atk' => 60, 'def' => 24, 'agi' => 16, 'int' => 32, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Wieża Magów' => [
                [
                    'name' => 'Adepci Run',
                    'level' => 76,
                    'stats' => ['hp' => 460, 'atk' => 62, 'def' => 26, 'agi' => 16, 'int' => 36, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Arkanów',
                    'level' => 80,
                    'stats' => ['hp' => 540, 'atk' => 70, 'def' => 34, 'agi' => 14, 'int' => 40, 'crit' => 0.14, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Płomieni',
                    'level' => 82,
                    'stats' => ['hp' => 500, 'atk' => 78, 'def' => 28, 'agi' => 18, 'int' => 30, 'crit' => 0.15, 'dodge' => 0.07],
                    'abilities' => []
                ],
                [
                    'name' => 'Mistrz Iluzji',
                    'level' => 84,
                    'stats' => ['hp' => 520, 'atk' => 68, 'def' => 26, 'agi' => 20, 'int' => 44, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ]
            ],
            'Skażone Miasto' => [
                [
                    'name' => 'Zmutowany Nieumarły',
                    'level' => 86,
                    'stats' => ['hp' => 600, 'atk' => 82, 'def' => 34, 'agi' => 18, 'int' => 16, 'crit' => 0.14, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Czarownica Zgnilizny',
                    'level' => 90,
                    'stats' => ['hp' => 580, 'atk' => 76, 'def' => 30, 'agi' => 20, 'int' => 48, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Plagi',
                    'level' => 94,
                    'stats' => ['hp' => 560, 'atk' => 84, 'def' => 28, 'agi' => 28, 'int' => 12, 'crit' => 0.18, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Skazy',
                    'level' => 98,
                    'stats' => ['hp' => 700, 'atk' => 92, 'def' => 44, 'agi' => 22, 'int' => 20, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ]
            ]
        ];

        foreach ($monstersByMap as $mapName => $monsters) {
            $map = Map::where('name', $mapName)->first();

            if (!$map) {
                $this->command->warn("Map '{$mapName}' not found, skipping monsters.");
                continue;
            }

            foreach ($monsters as $monsterData) {
                Monster::create([
                    'map_id' => $map->id,
                    'name' => $monsterData['name'],
                    'level' => $monsterData['level'],
                    'stats' => $monsterData['stats'],
                    'abilities' => $monsterData['abilities'],
                    'loot_table_id' => null, // Will be set up later
                ]);
            }
        }

        $this->command->info('Monster seeder completed - created monsters for all maps.');
    }
}
