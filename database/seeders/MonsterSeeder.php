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
                    'type' => 'animal',
                    'level' => 3,
                    'rank' => 'regular',
                    'stats' => ['hp' => 40, 'atk' => 7, 'def' => 2, 'agi' => 6, 'int' => 1, 'crit' => 0.05, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Nietoperz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 4,
                    'rank' => 'regular',
                    'stats' => ['hp' => 32, 'atk' => 6, 'def' => 1, 'agi' => 8, 'int' => 1, 'crit' => 0.07, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Suchodrzew',
                    'type' => 'plant',
                    'level' => 6,
                    'rank' => 'regular',
                    'stats' => ['hp' => 58, 'atk' => 9, 'def' => 4, 'agi' => 3, 'int' => 1, 'crit' => 0.03, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Goblin Zwiadowca',
                    'type' => 'goblin',
                    'level' => 8,
                    'rank' => 'regular',
                    'stats' => ['hp' => 50, 'atk' => 10, 'def' => 3, 'agi' => 9, 'int' => 2, 'crit' => 0.08, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Lasu',
                    'type' => 'animal',
                    'level' => 10,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 5000, 'atk' => 15, 'def' => 8, 'agi' => 5, 'int' => 3, 'crit' => 0.1, 'dodge' => 0.02],
                    'abilities' => []
                ]
            ],
            'Stare Ruiny' => [
                [
                    'name' => 'Szkielet Wojownik',
                    'type' => 'undead',
                    'level' => 14,
                    'rank' => 'regular',
                    'stats' => ['hp' => 110, 'atk' => 18, 'def' => 9, 'agi' => 8, 'int' => 2, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Duch Strażnik',
                    'type' => 'undead',
                    'level' => 18,
                    'rank' => 'regular',
                    'stats' => ['hp' => 95, 'atk' => 16, 'def' => 6, 'agi' => 11, 'int' => 8, 'crit' => 0.07, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Ghul',
                    'type' => 'undead',
                    'level' => 20,
                    'rank' => 'regular',
                    'stats' => ['hp' => 130, 'atk' => 22, 'def' => 10, 'agi' => 8, 'int' => 3, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Upiorny Łucznik',
                    'type' => 'undead',
                    'level' => 22,
                    'rank' => 'regular',
                    'stats' => ['hp' => 105, 'atk' => 24, 'def' => 8, 'agi' => 14, 'int' => 4, 'crit' => 0.10, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Licz Cieni',
                    'type' => 'undead',
                    'level' => 25,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 15000, 'atk' => 35, 'def' => 15, 'agi' => 10, 'int' => 20, 'crit' => 0.15, 'dodge' => 0.05],
                    'abilities' => []
                ]
            ],
            'Jaskinia Trolli' => [
                [
                    'name' => 'Troll Paskudnik',
                    'type' => 'troll',
                    'level' => 26,
                    'rank' => 'regular',
                    'stats' => ['hp' => 220, 'atk' => 30, 'def' => 18, 'agi' => 6, 'int' => 2, 'crit' => 0.05, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Szaman',
                    'type' => 'troll',
                    'level' => 29,
                    'rank' => 'regular',
                    'stats' => ['hp' => 200, 'atk' => 26, 'def' => 14, 'agi' => 7, 'int' => 14, 'crit' => 0.06, 'dodge' => 0.03],
                    'abilities' => []
                ],
                [
                    'name' => 'Ogr Rozłupywacz',
                    'type' => 'ogre',
                    'level' => 32,
                    'rank' => 'regular',
                    'stats' => ['hp' => 260, 'atk' => 36, 'def' => 20, 'agi' => 6, 'int' => 3, 'crit' => 0.07, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Jaskiniowy Nietoperz Alfa',
                    'type' => 'animal',
                    'level' => 33,
                    'rank' => 'regular',
                    'stats' => ['hp' => 180, 'atk' => 28, 'def' => 10, 'agi' => 16, 'int' => 4, 'crit' => 0.10, 'dodge' => 0.07],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Trolli',
                    'type' => 'troll',
                    'level' => 35,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 35000, 'atk' => 50, 'def' => 30, 'agi' => 5, 'int' => 5, 'crit' => 0.1, 'dodge' => 0.01],
                    'abilities' => []
                ]
            ],
            'Pustkowia Orków' => [
                [
                    'name' => 'Orczy Zwiad',
                    'type' => 'orc',
                    'level' => 37,
                    'rank' => 'regular',
                    'stats' => ['hp' => 260, 'atk' => 40, 'def' => 18, 'agi' => 14, 'int' => 4, 'crit' => 0.09, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Berserker',
                    'type' => 'orc',
                    'level' => 42,
                    'rank' => 'regular',
                    'stats' => ['hp' => 320, 'atk' => 52, 'def' => 20, 'agi' => 12, 'int' => 3, 'crit' => 0.12, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Szaman Krwi',
                    'type' => 'orc',
                    'level' => 46,
                    'rank' => 'regular',
                    'stats' => ['hp' => 290, 'atk' => 45, 'def' => 16, 'agi' => 10, 'int' => 20, 'crit' => 0.08, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Dowódca Watahy',
                    'type' => 'orc',
                    'level' => 48,
                    'rank' => 'regular',
                    'stats' => ['hp' => 350, 'atk' => 58, 'def' => 24, 'agi' => 13, 'int' => 6, 'crit' => 0.12, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Wódz Orków',
                    'type' => 'orc',
                    'level' => 50,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 60000, 'atk' => 80, 'def' => 35, 'agi' => 15, 'int' => 10, 'crit' => 0.15, 'dodge' => 0.05],
                    'abilities' => []
                ]
            ],
            'Bagna Grozy' => [
                [
                    'name' => 'Topielec',
                    'type' => 'undead',
                    'level' => 52,
                    'rank' => 'regular',
                    'stats' => ['hp' => 360, 'atk' => 55, 'def' => 24, 'agi' => 12, 'int' => 8, 'crit' => 0.08, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Wiedźmia Straż',
                    'type' => 'demon',
                    'level' => 58,
                    'rank' => 'regular',
                    'stats' => ['hp' => 340, 'atk' => 50, 'def' => 22, 'agi' => 14, 'int' => 26, 'crit' => 0.10, 'dodge' => 0.07],
                    'abilities' => []
                ],
                [
                    'name' => 'Drzewiec Plugawy',
                    'type' => 'plant',
                    'level' => 60,
                    'rank' => 'regular',
                    'stats' => ['hp' => 420, 'atk' => 62, 'def' => 30, 'agi' => 10, 'int' => 10, 'crit' => 0.06, 'dodge' => 0.04],
                    'abilities' => []
                ],
                [
                    'name' => 'Hydra Bagienna',
                    'type' => 'animal',
                    'level' => 64,
                    'rank' => 'regular',
                    'stats' => ['hp' => 480, 'atk' => 70, 'def' => 28, 'agi' => 16, 'int' => 18, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Moczarowy Behemot',
                    'type' => 'animal',
                    'level' => 65,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 100000, 'atk' => 110, 'def' => 45, 'agi' => 10, 'int' => 15, 'crit' => 0.1, 'dodge' => 0.02],
                    'abilities' => []
                ]
            ],
            'Góry Cienia' => [
                [
                    'name' => 'Wilk Cienia',
                    'type' => 'animal',
                    'level' => 66,
                    'rank' => 'regular',
                    'stats' => ['hp' => 420, 'atk' => 68, 'def' => 26, 'agi' => 22, 'int' => 8, 'crit' => 0.14, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Golem Bazaltowy',
                    'type' => 'golem',
                    'level' => 70,
                    'rank' => 'regular',
                    'stats' => ['hp' => 520, 'atk' => 74, 'def' => 40, 'agi' => 8, 'int' => 6, 'crit' => 0.06, 'dodge' => 0.02],
                    'abilities' => []
                ],
                [
                    'name' => 'Harpia',
                    'type' => 'monster',
                    'level' => 72,
                    'rank' => 'regular',
                    'stats' => ['hp' => 410, 'atk' => 66, 'def' => 22, 'agi' => 24, 'int' => 10, 'crit' => 0.14, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Wędrowny Czarownik',
                    'type' => 'human',
                    'level' => 74,
                    'rank' => 'regular',
                    'stats' => ['hp' => 450, 'atk' => 60, 'def' => 24, 'agi' => 16, 'int' => 32, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Smok Cienia',
                    'type' => 'dragon',
                    'level' => 75,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 180000, 'atk' => 150, 'def' => 60, 'agi' => 25, 'int' => 50, 'crit' => 0.2, 'dodge' => 0.1],
                    'abilities' => []
                ]
            ],
            'Wieża Magów' => [
                [
                    'name' => 'Adepci Run',
                    'type' => 'human',
                    'level' => 76,
                    'rank' => 'regular',
                    'stats' => ['hp' => 460, 'atk' => 62, 'def' => 26, 'agi' => 16, 'int' => 36, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Arkanów',
                    'type' => 'golem',
                    'level' => 80,
                    'rank' => 'regular',
                    'stats' => ['hp' => 540, 'atk' => 70, 'def' => 34, 'agi' => 14, 'int' => 40, 'crit' => 0.14, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Płomieni',
                    'type' => 'elemental',
                    'level' => 82,
                    'rank' => 'regular',
                    'stats' => ['hp' => 500, 'atk' => 78, 'def' => 28, 'agi' => 18, 'int' => 30, 'crit' => 0.15, 'dodge' => 0.07],
                    'abilities' => []
                ],
                [
                    'name' => 'Mistrz Iluzji',
                    'type' => 'human',
                    'level' => 84,
                    'rank' => 'regular',
                    'stats' => ['hp' => 520, 'atk' => 68, 'def' => 26, 'agi' => 20, 'int' => 44, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Arcymag',
                    'type' => 'human',
                    'level' => 85,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 250000, 'atk' => 120, 'def' => 50, 'agi' => 30, 'int' => 150, 'crit' => 0.25, 'dodge' => 0.15],
                    'abilities' => []
                ]
            ],
            'Skażone Miasto' => [
                [
                    'name' => 'Zmutowany Nieumarły',
                    'type' => 'undead',
                    'level' => 86,
                    'rank' => 'regular',
                    'stats' => ['hp' => 600, 'atk' => 82, 'def' => 34, 'agi' => 18, 'int' => 16, 'crit' => 0.14, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Czarownica Zgnilizny',
                    'type' => 'demon',
                    'level' => 90,
                    'rank' => 'regular',
                    'stats' => ['hp' => 580, 'atk' => 76, 'def' => 30, 'agi' => 20, 'int' => 48, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Plagi',
                    'type' => 'animal',
                    'level' => 94,
                    'rank' => 'regular',
                    'stats' => ['hp' => 560, 'atk' => 84, 'def' => 28, 'agi' => 28, 'int' => 12, 'crit' => 0.18, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Skazy',
                    'type' => 'demon',
                    'level' => 98,
                    'rank' => 'regular',
                    'stats' => ['hp' => 700, 'atk' => 92, 'def' => 44, 'agi' => 22, 'int' => 20, 'crit' => 0.16, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Pan Zniszczenia',
                    'type' => 'demon',
                    'level' => 100,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 500000, 'atk' => 250, 'def' => 100, 'agi' => 40, 'int' => 80, 'crit' => 0.3, 'dodge' => 0.2],
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
                Monster::updateOrCreate(
                    [
                        'map_id' => $map->id,
                        'name' => $monsterData['name'],
                    ],
                    [
                        'type' => $monsterData['type'],
                        'level' => $monsterData['level'],
                        'rank' => $monsterData['rank'],
                        'stats' => $monsterData['stats'],
                        'abilities' => $monsterData['abilities'],
                    ]
                );
            }
        }

        $this->command->info('Monster seeder completed - created/updated monsters for all maps.');
    }
}

