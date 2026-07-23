<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Str;

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
                    'stats' => ['hp' => 112, 'atk' => 20, 'def' => 6, 'agi' => 17, 'int' => 3, 'crit' => 0.2, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Nietoperz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 4,
                    'rank' => 'regular',
                    'stats' => ['hp' => 90, 'atk' => 17, 'def' => 3, 'agi' => 22, 'int' => 3, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Leśny',
                    'type' => 'animal',
                    'level' => 5,
                    'rank' => 'regular',
                    'stats' => ['hp' => 125, 'atk' => 22, 'def' => 7, 'agi' => 19, 'int' => 4, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Suchodrzew',
                    'type' => 'plant',
                    'level' => 6,
                    'rank' => 'regular',
                    'stats' => ['hp' => 162, 'atk' => 25, 'def' => 11, 'agi' => 8, 'int' => 3, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Zdziczały Dzik',
                    'type' => 'animal',
                    'level' => 7,
                    'rank' => 'regular',
                    'stats' => ['hp' => 150, 'atk' => 26, 'def' => 10, 'agi' => 12, 'int' => 2, 'crit' => 0.18, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Goblin Zwiadowca',
                    'type' => 'goblin',
                    'level' => 8,
                    'rank' => 'regular',
                    'stats' => ['hp' => 140, 'atk' => 28, 'def' => 8, 'agi' => 25, 'int' => 6, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Puszczy',
                    'type' => 'plant',
                    'level' => 12,
                    'rank' => 'boss',
                    'stats' => ['hp' => 2200, 'atk' => 38, 'def' => 18, 'agi' => 12, 'int' => 10, 'crit' => 0.25, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Lasu',
                    'type' => 'animal',
                    'level' => 10,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 14000, 'atk' => 42, 'def' => 22, 'agi' => 14, 'int' => 8, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Stare Ruiny' => [
                [
                    'name' => 'Szkielet Wojownik',
                    'type' => 'undead',
                    'level' => 14,
                    'rank' => 'regular',
                    'stats' => ['hp' => 440, 'atk' => 72, 'def' => 36, 'agi' => 32, 'int' => 8, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Kultysta',
                    'type' => 'human',
                    'level' => 16,
                    'rank' => 'regular',
                    'stats' => ['hp' => 400, 'atk' => 68, 'def' => 28, 'agi' => 36, 'int' => 45, 'crit' => 0.26, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Duch Strażnik',
                    'type' => 'undead',
                    'level' => 18,
                    'rank' => 'regular',
                    'stats' => ['hp' => 380, 'atk' => 64, 'def' => 24, 'agi' => 44, 'int' => 32, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Ghul',
                    'type' => 'undead',
                    'level' => 20,
                    'rank' => 'regular',
                    'stats' => ['hp' => 520, 'atk' => 88, 'def' => 40, 'agi' => 32, 'int' => 12, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Upiorny Łucznik',
                    'type' => 'undead',
                    'level' => 22,
                    'rank' => 'regular',
                    'stats' => ['hp' => 420, 'atk' => 96, 'def' => 32, 'agi' => 56, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Kamienny Gargulec',
                    'type' => 'golem',
                    'level' => 23,
                    'rank' => 'regular',
                    'stats' => ['hp' => 480, 'atk' => 84, 'def' => 48, 'agi' => 26, 'int' => 10, 'crit' => 0.20, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Władca Krypty',
                    'type' => 'undead',
                    'level' => 27,
                    'rank' => 'boss',
                    'stats' => ['hp' => 7500, 'atk' => 115, 'def' => 52, 'agi' => 35, 'int' => 40, 'crit' => 0.35, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Licz Cieni',
                    'type' => 'undead',
                    'level' => 25,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 60000, 'atk' => 140, 'def' => 60, 'agi' => 40, 'int' => 80, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Jaskinia Trolli' => [
                [
                    'name' => 'Troll Paskudnik',
                    'type' => 'troll',
                    'level' => 26,
                    'rank' => 'regular',
                    'stats' => ['hp' => 880, 'atk' => 120, 'def' => 72, 'agi' => 24, 'int' => 8, 'crit' => 0.2, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Pełzacz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 28,
                    'rank' => 'regular',
                    'stats' => ['hp' => 780, 'atk' => 110, 'def' => 60, 'agi' => 38, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Szaman',
                    'type' => 'troll',
                    'level' => 29,
                    'rank' => 'regular',
                    'stats' => ['hp' => 800, 'atk' => 104, 'def' => 56, 'agi' => 28, 'int' => 56, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Ogr Rozłupywacz',
                    'type' => 'ogre',
                    'level' => 32,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1040, 'atk' => 144, 'def' => 80, 'agi' => 24, 'int' => 12, 'crit' => 0.28, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Jaskiniowy Nietoperz Alfa',
                    'type' => 'animal',
                    'level' => 33,
                    'rank' => 'regular',
                    'stats' => ['hp' => 720, 'atk' => 112, 'def' => 40, 'agi' => 64, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Scalony',
                    'type' => 'troll',
                    'level' => 34,
                    'rank' => 'regular',
                    'stats' => ['hp' => 960, 'atk' => 135, 'def' => 76, 'agi' => 22, 'int' => 12, 'crit' => 0.25, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Starożytny Ogr',
                    'type' => 'ogre',
                    'level' => 38,
                    'rank' => 'boss',
                    'stats' => ['hp' => 18000, 'atk' => 175, 'def' => 95, 'agi' => 25, 'int' => 15, 'crit' => 0.32, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Trolli',
                    'type' => 'troll',
                    'level' => 35,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 140000, 'atk' => 200, 'def' => 120, 'agi' => 20, 'int' => 20, 'crit' => 0.4, 'dodge' => 0.04],
                    'abilities' => []
                ]
            ],
            'Pustkowia Orków' => [
                [
                    'name' => 'Orczy Zwiad',
                    'type' => 'orc',
                    'level' => 37,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1040, 'atk' => 160, 'def' => 72, 'agi' => 56, 'int' => 16, 'crit' => 0.36, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pustynny Skorpion',
                    'type' => 'animal',
                    'level' => 40,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1150, 'atk' => 180, 'def' => 76, 'agi' => 50, 'int' => 14, 'crit' => 0.38, 'dodge' => 0.18],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Berserker',
                    'type' => 'orc',
                    'level' => 42,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1280, 'atk' => 208, 'def' => 80, 'agi' => 48, 'int' => 12, 'crit' => 0.48, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Topornik',
                    'type' => 'orc',
                    'level' => 44,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1300, 'atk' => 215, 'def' => 88, 'agi' => 42, 'int' => 15, 'crit' => 0.42, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Szaman Krwi',
                    'type' => 'orc',
                    'level' => 46,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1160, 'atk' => 180, 'def' => 64, 'agi' => 40, 'int' => 80, 'crit' => 0.32, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Dowódca Watahy',
                    'type' => 'orc',
                    'level' => 48,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1400, 'atk' => 232, 'def' => 96, 'agi' => 52, 'int' => 24, 'crit' => 0.48, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Niszczyciel Pustkowi',
                    'type' => 'orc',
                    'level' => 53,
                    'rank' => 'boss',
                    'stats' => ['hp' => 35000, 'atk' => 280, 'def' => 120, 'agi' => 55, 'int' => 30, 'crit' => 0.50, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Wódz Orków',
                    'type' => 'orc',
                    'level' => 50,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 240000, 'atk' => 320, 'def' => 140, 'agi' => 60, 'int' => 40, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Bagna Grozy' => [
                [
                    'name' => 'Topielec',
                    'type' => 'undead',
                    'level' => 52,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1440, 'atk' => 220, 'def' => 96, 'agi' => 48, 'int' => 32, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Błotny Bazyliszek',
                    'type' => 'monster',
                    'level' => 55,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1500, 'atk' => 230, 'def' => 100, 'agi' => 52, 'int' => 45, 'crit' => 0.35, 'dodge' => 0.20],
                    'abilities' => []
                ],
                [
                    'name' => 'Wiedźmia Straż',
                    'type' => 'demon',
                    'level' => 58,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1360, 'atk' => 200, 'def' => 88, 'agi' => 56, 'int' => 104, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Drzewiec Plugawy',
                    'type' => 'plant',
                    'level' => 60,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1680, 'atk' => 248, 'def' => 120, 'agi' => 40, 'int' => 40, 'crit' => 0.24, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Widmo Bagien',
                    'type' => 'undead',
                    'level' => 62,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1600, 'atk' => 260, 'def' => 95, 'agi' => 70, 'int' => 85, 'crit' => 0.44, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Hydra Bagienna',
                    'type' => 'animal',
                    'level' => 64,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1920, 'atk' => 280, 'def' => 112, 'agi' => 64, 'int' => 72, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Królowa Wiedźm',
                    'type' => 'demon',
                    'level' => 68,
                    'rank' => 'boss',
                    'stats' => ['hp' => 65000, 'atk' => 380, 'def' => 150, 'agi' => 60, 'int' => 150, 'crit' => 0.45, 'dodge' => 0.22],
                    'abilities' => []
                ],
                [
                    'name' => 'Moczarowy Behemot',
                    'type' => 'animal',
                    'level' => 65,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 400000, 'atk' => 440, 'def' => 180, 'agi' => 40, 'int' => 60, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Góry Cienia' => [
                [
                    'name' => 'Wilk Cienia',
                    'type' => 'animal',
                    'level' => 66,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1680, 'atk' => 272, 'def' => 104, 'agi' => 88, 'int' => 32, 'crit' => 0.56, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Gryf',
                    'type' => 'animal',
                    'level' => 68,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1750, 'atk' => 280, 'def' => 110, 'agi' => 92, 'int' => 35, 'crit' => 0.52, 'dodge' => 0.38],
                    'abilities' => []
                ],
                [
                    'name' => 'Golem Bazaltowy',
                    'type' => 'golem',
                    'level' => 70,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2080, 'atk' => 296, 'def' => 160, 'agi' => 32, 'int' => 24, 'crit' => 0.24, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Harpia',
                    'type' => 'monster',
                    'level' => 72,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1640, 'atk' => 264, 'def' => 88, 'agi' => 96, 'int' => 40, 'crit' => 0.56, 'dodge' => 0.48],
                    'abilities' => []
                ],
                [
                    'name' => 'Cieniowy Gargulec',
                    'type' => 'demon',
                    'level' => 73,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1850, 'atk' => 290, 'def' => 130, 'agi' => 85, 'int' => 50, 'crit' => 0.50, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Wędrowny Czarownik',
                    'type' => 'human',
                    'level' => 74,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1800, 'atk' => 240, 'def' => 96, 'agi' => 64, 'int' => 128, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Władca Cieni',
                    'type' => 'demon',
                    'level' => 78,
                    'rank' => 'boss',
                    'stats' => ['hp' => 120000, 'atk' => 490, 'def' => 200, 'agi' => 95, 'int' => 160, 'crit' => 0.65, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Smok Cienia',
                    'type' => 'dragon',
                    'level' => 75,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 720000, 'atk' => 600, 'def' => 240, 'agi' => 100, 'int' => 200, 'crit' => 0.8, 'dodge' => 0.4],
                    'abilities' => []
                ]
            ],
            'Wieża Magów' => [
                [
                    'name' => 'Adepci Run',
                    'type' => 'human',
                    'level' => 76,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1840, 'atk' => 248, 'def' => 104, 'agi' => 64, 'int' => 144, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Lodu',
                    'type' => 'elemental',
                    'level' => 78,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1900, 'atk' => 260, 'def' => 125, 'agi' => 60, 'int' => 150, 'crit' => 0.50, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Arkanów',
                    'type' => 'golem',
                    'level' => 80,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2160, 'atk' => 280, 'def' => 136, 'agi' => 56, 'int' => 160, 'crit' => 0.56, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Płomieni',
                    'type' => 'elemental',
                    'level' => 82,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2000, 'atk' => 312, 'def' => 112, 'agi' => 72, 'int' => 120, 'crit' => 0.6, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Runiczny Konstrukt',
                    'type' => 'golem',
                    'level' => 83,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2200, 'atk' => 290, 'def' => 150, 'agi' => 50, 'int' => 165, 'crit' => 0.55, 'dodge' => 0.25],
                    'abilities' => []
                ],
                [
                    'name' => 'Mistrz Iluzji',
                    'type' => 'human',
                    'level' => 84,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2080, 'atk' => 272, 'def' => 104, 'agi' => 80, 'int' => 176, 'crit' => 0.64, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Wielki Inkwizytor',
                    'type' => 'human',
                    'level' => 88,
                    'rank' => 'boss',
                    'stats' => ['hp' => 220000, 'atk' => 430, 'def' => 180, 'agi' => 90, 'int' => 450, 'crit' => 0.75, 'dodge' => 0.45],
                    'abilities' => []
                ],
                [
                    'name' => 'Arcymag',
                    'type' => 'human',
                    'level' => 85,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 1000000, 'atk' => 480, 'def' => 200, 'agi' => 120, 'int' => 600, 'crit' => 1, 'dodge' => 0.6],
                    'abilities' => []
                ]
            ],
            'Skażone Miasto' => [
                [
                    'name' => 'Zmutowany Nieumarły',
                    'type' => 'undead',
                    'level' => 86,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2400, 'atk' => 328, 'def' => 136, 'agi' => 72, 'int' => 64, 'crit' => 0.56, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Plagowy Kat',
                    'type' => 'undead',
                    'level' => 88,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2500, 'atk' => 340, 'def' => 145, 'agi' => 75, 'int' => 70, 'crit' => 0.60, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Czarownica Zgnilizny',
                    'type' => 'demon',
                    'level' => 90,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2320, 'atk' => 304, 'def' => 120, 'agi' => 80, 'int' => 192, 'crit' => 0.64, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Zbezczeszczony Golem',
                    'type' => 'golem',
                    'level' => 92,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2700, 'atk' => 355, 'def' => 165, 'agi' => 65, 'int' => 80, 'crit' => 0.58, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Plagi',
                    'type' => 'animal',
                    'level' => 94,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2240, 'atk' => 336, 'def' => 112, 'agi' => 112, 'int' => 48, 'crit' => 0.72, 'dodge' => 0.56],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Skazy',
                    'type' => 'demon',
                    'level' => 98,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2800, 'atk' => 368, 'def' => 176, 'agi' => 88, 'int' => 80, 'crit' => 0.64, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Książę Zniszczenia',
                    'type' => 'demon',
                    'level' => 102,
                    'rank' => 'boss',
                    'stats' => ['hp' => 450000, 'atk' => 780, 'def' => 320, 'agi' => 130, 'int' => 250, 'crit' => 0.90, 'dodge' => 0.50],
                    'abilities' => []
                ],
                [
                    'name' => 'Pan Zniszczenia',
                    'type' => 'demon',
                    'level' => 100,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 2000000, 'atk' => 1000, 'def' => 400, 'agi' => 160, 'int' => 320, 'crit' => 1.2, 'dodge' => 0.8],
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
                        'avatar' => Str::slug($monsterData['name']) . '.png',
                    ]
                );
            }
        }

        $this->command->info('Monster seeder completed - created/updated monsters for all maps.');
    }
}
