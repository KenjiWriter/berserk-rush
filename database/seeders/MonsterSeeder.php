<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Str;

class MonsterSeeder extends Seeder
{
    // UWAGA (rebalans potworów 'regular'+'boss', 2026-07-29): staty hp/atk/def/agi
    // każdego potwora rangi 'regular' i 'boss' zostały przeliczone symulacyjnie
    // (Monte Carlo, patrz `php artisan balance:monsters [--rank=regular|boss]` /
    // app/Console/Commands/BalanceMonstersCommand.php) pod 6 referencyjnych buildów
    // (po jednym na typ broni: miecz/topór/łuk/sztylet/dzwon/różdżka, postać poziom =
    // level_min mapy+1).
    // 'regular': cel >=90% winrate/10 walk, zabicie w 3-4 trafieniach, ~60% straconego
    // max HP. 'boss': trudniejszy odpowiednik - cel ~65% winrate, 8-12 trafień, ~80-
    // 100% straconego HP (realne ryzyko śmierci). Stare wartości (ręcznie dobrane)
    // były w wyższych mapach mocno niedoszacowane względem realnej mocy sprzętu z
    // ItemTemplateSeeder/ShopEquipmentSeeder (stąd duży skok hp/atk na wyższych mapach).
    // UWAGA: cel 65% dla bossa to ŚREDNIA po 6 archetypach broni - realny winrate mocno
    // zależy od wybranej broni (np. na Skażonym Mieście różdżka ~99% vs miecz ~18%) -
    // to świadomy kompromis zaakceptowany przez projektanta, nie błąd kalibracji.
    //
    // UWAGA (rekalibracja 'regular' pod realnego gracza, 2026-08-05 - patrz feedback
    // gracza aso666 "wystarczy ubrać +2 i expić, potwory padają na 1-2 hity"): staty
    // rangi 'regular' PONOWNIE przeliczone - referencyjna postać z 2026-07-29 była
    // "gołym" bohaterem bez ulepszeń/zaklęć/zestawu klasowego, więc w praktyce była
    // dużo słabsza niż jakikolwiek realny gracz, co prowadziło do trywialnych starć.
    // Nowa referencja zakłada +2 ulepszenia (dosłownie to, co gracz zgłosił jako
    // trywializujące), 1 skromny naroll zaklęcia per sztuka ekwipunku oraz bonus
    // atrybutu z zestawu klasowego zbroi (_w/_m/_a) - patrz pełna notatka w docblocku
    // `BalanceMonstersCommand`. Docelowe cele (3-4 trafienia/90% winrate) się NIE
    // zmieniły - zmieniło się tylko to, względem jak silnego gracza są liczone. Ranga
    // 'boss' NIE została w tym przebiegu ruszona - solver dla 'boss' (target 8-12
    // trafień/65% winrate) po zastosowaniu tej samej korekty dawał wartości HP
    // WYRAŹNIE niższe niż obecne (np. Strażnik Puszczy 2200 -> ~400), co jest
    // podejrzane i wymaga osobnej weryfikacji przed wdrożeniem - zostawione jako
    // follow-up, żeby nie ryzykować niezamierzonego, drastycznego osłabienia bossów.
    //
    // 'worldboss' NIE został objęty tym Monte Carlo (ma zupełnie inną mechanikę walki -
    // regeneruje HP i nie da się go zabić, patrz docs/modules/world_boss.md). Statystyki
    // atk/def zostały jednak ręcznie przeskalowane (rework 2026-07-30) względem
    // najbliższego poziomowo, już skalibrowanego potwora rangi 'boss' (ATK x1.5, DEF
    // x1.25) - wcześniej część world bossów miała ATK wyraźnie niższe niż zwykły boss
    // mapy na zbliżonym poziomie, co czyniło ich nieadekwatnie słabymi jak na superbossów.
    public function run(): void
    {
        $monstersByMap = [
            'Mroczny Las' => [
                [
                    'name' => 'Widmowy Leśny Niedźwiedź',
                    'type' => 'animal',
                    'level' => 11,
                    'rank' => 'boss',
                    'stats' => ['hp' => 204, 'atk' => 26, 'def' => 5, 'agi' => 4, 'int' => 3, 'crit' => 0.20, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Wilk Leśny',
                    'type' => 'animal',
                    'level' => 3,
                    'rank' => 'regular',
                    'stats' => ['hp' => 99, 'atk' => 55, 'def' => 5, 'agi' => 5, 'int' => 3, 'crit' => 0.2, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Nietoperz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 4,
                    'rank' => 'regular',
                    'stats' => ['hp' => 80, 'atk' => 47, 'def' => 2, 'agi' => 6, 'int' => 3, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Leśny',
                    'type' => 'animal',
                    'level' => 5,
                    'rank' => 'regular',
                    'stats' => ['hp' => 111, 'atk' => 60, 'def' => 5, 'agi' => 6, 'int' => 3, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Suchodrzew',
                    'type' => 'mystical',
                    'level' => 6,
                    'rank' => 'regular',
                    'stats' => ['hp' => 143, 'atk' => 68, 'def' => 8, 'agi' => 2, 'int' => 3, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Zdziczały Dzik',
                    'type' => 'animal',
                    'level' => 7,
                    'rank' => 'regular',
                    'stats' => ['hp' => 133, 'atk' => 71, 'def' => 8, 'agi' => 4, 'int' => 2, 'crit' => 0.18, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Goblin Zwiadowca',
                    'type' => 'mystical',
                    'level' => 8,
                    'rank' => 'regular',
                    'stats' => ['hp' => 124, 'atk' => 77, 'def' => 6, 'agi' => 7, 'int' => 5, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Puszczy',
                    'type' => 'mystical',
                    'level' => 12,
                    'rank' => 'boss',
                    'stats' => ['hp' => 232, 'atk' => 27, 'def' => 5, 'agi' => 4, 'int' => 9, 'crit' => 0.25, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Lasu',
                    'type' => 'animal',
                    'level' => 10,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 8925, 'atk' => 41, 'def' => 7, 'agi' => 12, 'int' => 7, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Stare Ruiny' => [
                [
                    'name' => 'Starożytny Golem Kamienny',
                    'type' => 'mystical',
                    'level' => 25,
                    'rank' => 'boss',
                    'stats' => ['hp' => 480, 'atk' => 68, 'def' => 18, 'agi' => 4, 'int' => 6, 'crit' => 0.15, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Szkielet Wojownik',
                    'type' => 'undead',
                    'level' => 14,
                    'rank' => 'regular',
                    'stats' => ['hp' => 454, 'atk' => 136, 'def' => 22, 'agi' => 14, 'int' => 8, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Kultysta',
                    'type' => 'mystical',
                    'level' => 16,
                    'rank' => 'regular',
                    'stats' => ['hp' => 413, 'atk' => 128, 'def' => 17, 'agi' => 16, 'int' => 45, 'crit' => 0.26, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Duch Strażnik',
                    'type' => 'undead',
                    'level' => 18,
                    'rank' => 'regular',
                    'stats' => ['hp' => 392, 'atk' => 121, 'def' => 14, 'agi' => 20, 'int' => 32, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Ghul',
                    'type' => 'undead',
                    'level' => 20,
                    'rank' => 'regular',
                    'stats' => ['hp' => 537, 'atk' => 166, 'def' => 24, 'agi' => 14, 'int' => 12, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Upiorny Łucznik',
                    'type' => 'undead',
                    'level' => 22,
                    'rank' => 'regular',
                    'stats' => ['hp' => 433, 'atk' => 181, 'def' => 19, 'agi' => 25, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Kamienny Gargulec',
                    'type' => 'mystical',
                    'level' => 23,
                    'rank' => 'regular',
                    'stats' => ['hp' => 495, 'atk' => 158, 'def' => 29, 'agi' => 12, 'int' => 10, 'crit' => 0.20, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Ruin',
                    'type' => 'undead',
                    'level' => 27,
                    'rank' => 'boss',
                    'stats' => ['hp' => 1004, 'atk' => 82, 'def' => 15, 'agi' => 12, 'int' => 40, 'crit' => 0.35, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Licz Cieni',
                    'type' => 'undead',
                    'level' => 25,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 60000, 'atk' => 120, 'def' => 19, 'agi' => 40, 'int' => 80, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Jaskinia Trolli' => [
                [
                    'name' => 'Mroczny Władca Trolli',
                    'type' => 'troll',
                    'level' => 36,
                    'rank' => 'boss',
                    'stats' => ['hp' => 880, 'atk' => 125, 'def' => 32, 'agi' => 6, 'int' => 8, 'crit' => 0.20, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Paskudnik',
                    'type' => 'troll',
                    'level' => 26,
                    'rank' => 'regular',
                    'stats' => ['hp' => 641, 'atk' => 202, 'def' => 31, 'agi' => 15, 'int' => 8, 'crit' => 0.2, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Pełzacz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 28,
                    'rank' => 'regular',
                    'stats' => ['hp' => 568, 'atk' => 186, 'def' => 26, 'agi' => 24, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Szaman',
                    'type' => 'troll',
                    'level' => 29,
                    'rank' => 'regular',
                    'stats' => ['hp' => 582, 'atk' => 175, 'def' => 24, 'agi' => 18, 'int' => 56, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Ogr Rozłupywacz',
                    'type' => 'mystical',
                    'level' => 32,
                    'rank' => 'regular',
                    'stats' => ['hp' => 757, 'atk' => 242, 'def' => 35, 'agi' => 15, 'int' => 12, 'crit' => 0.28, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Jaskiniowy Nietoperz Alfa',
                    'type' => 'animal',
                    'level' => 33,
                    'rank' => 'regular',
                    'stats' => ['hp' => 524, 'atk' => 189, 'def' => 18, 'agi' => 40, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Scalony',
                    'type' => 'troll',
                    'level' => 34,
                    'rank' => 'regular',
                    'stats' => ['hp' => 699, 'atk' => 227, 'def' => 33, 'agi' => 14, 'int' => 12, 'crit' => 0.25, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Starożytny Ogr',
                    'type' => 'mystical',
                    'level' => 38,
                    'rank' => 'boss',
                    'stats' => ['hp' => 1387, 'atk' => 119, 'def' => 20, 'agi' => 15, 'int' => 15, 'crit' => 0.32, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Trolli',
                    'type' => 'troll',
                    'level' => 35,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 140000, 'atk' => 180, 'def' => 25, 'agi' => 20, 'int' => 20, 'crit' => 0.4, 'dodge' => 0.04],
                    'abilities' => []
                ]
            ],
            'Pustkowia Orków' => [
                [
                    'name' => 'Wojownik Cienia Orków',
                    'type' => 'orc',
                    'level' => 49,
                    'rank' => 'boss',
                    'stats' => ['hp' => 1450, 'atk' => 200, 'def' => 48, 'agi' => 10, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Orczy Zwiad',
                    'type' => 'orc',
                    'level' => 37,
                    'rank' => 'regular',
                    'stats' => ['hp' => 879, 'atk' => 281, 'def' => 40, 'agi' => 34, 'int' => 22, 'crit' => 0.36, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pustynny Skorpion',
                    'type' => 'animal',
                    'level' => 40,
                    'rank' => 'regular',
                    'stats' => ['hp' => 972, 'atk' => 316, 'def' => 43, 'agi' => 30, 'int' => 19, 'crit' => 0.38, 'dodge' => 0.18],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Berserker',
                    'type' => 'orc',
                    'level' => 42,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1081, 'atk' => 366, 'def' => 45, 'agi' => 29, 'int' => 16, 'crit' => 0.48, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Topornik',
                    'type' => 'orc',
                    'level' => 44,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1099, 'atk' => 378, 'def' => 50, 'agi' => 25, 'int' => 21, 'crit' => 0.42, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Szaman Krwi',
                    'type' => 'orc',
                    'level' => 46,
                    'rank' => 'regular',
                    'stats' => ['hp' => 980, 'atk' => 316, 'def' => 36, 'agi' => 24, 'int' => 111, 'crit' => 0.32, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Dowódca Watahy',
                    'type' => 'orc',
                    'level' => 48,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1183, 'atk' => 407, 'def' => 54, 'agi' => 31, 'int' => 33, 'crit' => 0.48, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Niszczyciel Pustkowi',
                    'type' => 'orc',
                    'level' => 53,
                    'rank' => 'boss',
                    'stats' => ['hp' => 3330, 'atk' => 704, 'def' => 48, 'agi' => 28, 'int' => 41, 'crit' => 0.50, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Wódz Orków',
                    'type' => 'orc',
                    'level' => 50,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 500000, 'atk' => 560, 'def' => 75, 'agi' => 100, 'int' => 70, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Bagna Grozy' => [
                [
                    'name' => 'Bagnisty Behemot Cienia',
                    'type' => 'demon',
                    'level' => 66,
                    'rank' => 'boss',
                    'stats' => ['hp' => 6750, 'atk' => 570, 'def' => 136, 'agi' => 12, 'int' => 15, 'crit' => 0.25, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Topielec',
                    'type' => 'undead',
                    'level' => 52,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1632, 'atk' => 934, 'def' => 70, 'agi' => 35, 'int' => 32, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Błotny Bazyliszek',
                    'type' => 'mystical',
                    'level' => 55,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1700, 'atk' => 979, 'def' => 73, 'agi' => 37, 'int' => 45, 'crit' => 0.35, 'dodge' => 0.20],
                    'abilities' => []
                ],
                [
                    'name' => 'Wiedźmia Straż',
                    'type' => 'demon',
                    'level' => 58,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1541, 'atk' => 850, 'def' => 64, 'agi' => 40, 'int' => 104, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Drzewiec Plugawy',
                    'type' => 'mystical',
                    'level' => 60,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1904, 'atk' => 1054, 'def' => 88, 'agi' => 29, 'int' => 40, 'crit' => 0.24, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Widmo Bagien',
                    'type' => 'undead',
                    'level' => 62,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1813, 'atk' => 1104, 'def' => 70, 'agi' => 50, 'int' => 85, 'crit' => 0.44, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Hydra Bagienna',
                    'type' => 'animal',
                    'level' => 64,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2176, 'atk' => 1189, 'def' => 82, 'agi' => 46, 'int' => 72, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Królowa Wiedźm',
                    'type' => 'demon',
                    'level' => 68,
                    'rank' => 'boss',
                    'stats' => ['hp' => 13011, 'atk' => 1402, 'def' => 112, 'agi' => 28, 'int' => 150, 'crit' => 0.45, 'dodge' => 0.22],
                    'abilities' => []
                ],
                [
                    'name' => 'Moczarowy Behemot',
                    'type' => 'animal',
                    'level' => 65,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 500000, 'atk' => 2000, 'def' => 140, 'agi' => 40, 'int' => 60, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Góry Cienia' => [
                [
                    'name' => 'Wyvern Cienistego Szczytu',
                    'type' => 'mystical',
                    'level' => 76,
                    'rank' => 'boss',
                    'stats' => ['hp' => 8250, 'atk' => 790, 'def' => 276, 'agi' => 16, 'int' => 20, 'crit' => 0.28, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Wilk Cienia',
                    'type' => 'animal',
                    'level' => 66,
                    'rank' => 'regular',
                    'stats' => ['hp' => 5477, 'atk' => 2227, 'def' => 205, 'agi' => 59, 'int' => 32, 'crit' => 0.56, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Gryf',
                    'type' => 'animal',
                    'level' => 68,
                    'rank' => 'regular',
                    'stats' => ['hp' => 5707, 'atk' => 2295, 'def' => 219, 'agi' => 61, 'int' => 35, 'crit' => 0.52, 'dodge' => 0.38],
                    'abilities' => []
                ],
                [
                    'name' => 'Golem Bazaltowy',
                    'type' => 'mystical',
                    'level' => 70,
                    'rank' => 'regular',
                    'stats' => ['hp' => 6781, 'atk' => 2428, 'def' => 317, 'agi' => 21, 'int' => 24, 'crit' => 0.24, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Harpia',
                    'type' => 'mystical',
                    'level' => 72,
                    'rank' => 'regular',
                    'stats' => ['hp' => 5347, 'atk' => 2161, 'def' => 175, 'agi' => 64, 'int' => 40, 'crit' => 0.56, 'dodge' => 0.48],
                    'abilities' => []
                ],
                [
                    'name' => 'Cieniowy Gargulec',
                    'type' => 'demon',
                    'level' => 73,
                    'rank' => 'regular',
                    'stats' => ['hp' => 6033, 'atk' => 2379, 'def' => 259, 'agi' => 57, 'int' => 50, 'crit' => 0.50, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Wędrowny Czarownik',
                    'type' => 'mystical',
                    'level' => 74,
                    'rank' => 'regular',
                    'stats' => ['hp' => 5868, 'atk' => 1967, 'def' => 190, 'agi' => 43, 'int' => 128, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Władca Cieni',
                    'type' => 'demon',
                    'level' => 78,
                    'rank' => 'boss',
                    'stats' => ['hp' => 38643, 'atk' => 3102, 'def' => 555, 'agi' => 35, 'int' => 160, 'crit' => 0.65, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Smok Cienia',
                    'type' => 'mystical',
                    'level' => 75,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 800000000, 'atk' => 4200, 'def' => 660, 'agi' => 100, 'int' => 200, 'crit' => 0.8, 'dodge' => 0.4],
                    'abilities' => []
                ]
            ],
            'Wieża Magów' => [
                [
                    'name' => 'Arcymag Pustki i Arkanów',
                    'type' => 'mystical',
                    'level' => 86,
                    'rank' => 'boss',
                    'stats' => ['hp' => 4500, 'atk' => 530, 'def' => 112, 'agi' => 18, 'int' => 45, 'crit' => 0.30, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Adepci Run',
                    'type' => 'mystical',
                    'level' => 76,
                    'rank' => 'regular',
                    'stats' => ['hp' => 11959, 'atk' => 4445, 'def' => 423, 'agi' => 59, 'int' => 144, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Lodu',
                    'type' => 'mystical',
                    'level' => 78,
                    'rank' => 'regular',
                    'stats' => ['hp' => 12349, 'atk' => 4658, 'def' => 510, 'agi' => 56, 'int' => 150, 'crit' => 0.50, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Arkanów',
                    'type' => 'mystical',
                    'level' => 80,
                    'rank' => 'regular',
                    'stats' => ['hp' => 14039, 'atk' => 5016, 'def' => 556, 'agi' => 53, 'int' => 160, 'crit' => 0.56, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Płomieni',
                    'type' => 'mystical',
                    'level' => 82,
                    'rank' => 'regular',
                    'stats' => ['hp' => 12999, 'atk' => 5587, 'def' => 456, 'agi' => 67, 'int' => 120, 'crit' => 0.6, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Runiczny Konstrukt',
                    'type' => 'mystical',
                    'level' => 83,
                    'rank' => 'regular',
                    'stats' => ['hp' => 14299, 'atk' => 5201, 'def' => 613, 'agi' => 47, 'int' => 165, 'crit' => 0.55, 'dodge' => 0.25],
                    'abilities' => []
                ],
                [
                    'name' => 'Mistrz Iluzji',
                    'type' => 'mystical',
                    'level' => 84,
                    'rank' => 'regular',
                    'stats' => ['hp' => 13519, 'atk' => 4870, 'def' => 423, 'agi' => 74, 'int' => 176, 'crit' => 0.64, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Wielki Inkwizytor',
                    'type' => 'mystical',
                    'level' => 88,
                    'rank' => 'boss',
                    'stats' => ['hp' => 36132, 'atk' => 3527, 'def' => 420, 'agi' => 39, 'int' => 450, 'crit' => 0.75, 'dodge' => 0.45],
                    'abilities' => []
                ],
                [
                    'name' => 'Arcymag',
                    'type' => 'mystical',
                    'level' => 85,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 800000000, 'atk' => 4800, 'def' => 500, 'agi' => 120, 'int' => 600, 'crit' => 1, 'dodge' => 0.6],
                    'abilities' => []
                ]
            ],
            'Skażone Miasto' => [
                [
                    'name' => 'Władca Skażenia i Plagi',
                    'type' => 'demon',
                    'level' => 99,
                    'rank' => 'boss',
                    'stats' => ['hp' => 105000, 'atk' => 46000, 'def' => 1600, 'agi' => 30, 'int' => 150, 'crit' => 0.40, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Zmutowany Nieumarły',
                    'type' => 'undead',
                    'level' => 86,
                    'rank' => 'regular',
                    'stats' => ['hp' => 36254, 'atk' => 13756, 'def' => 1328, 'agi' => 59, 'int' => 64, 'crit' => 0.45, 'dodge' => 0.20],
                    'abilities' => []
                ],
                [
                    'name' => 'Plagowy Kat',
                    'type' => 'undead',
                    'level' => 88,
                    'rank' => 'regular',
                    'stats' => ['hp' => 37764, 'atk' => 14253, 'def' => 1415, 'agi' => 61, 'int' => 70, 'crit' => 0.48, 'dodge' => 0.22],
                    'abilities' => []
                ],
                [
                    'name' => 'Czarownica Zgnilizny',
                    'type' => 'demon',
                    'level' => 90,
                    'rank' => 'regular',
                    'stats' => ['hp' => 35045, 'atk' => 12732, 'def' => 1170, 'agi' => 65, 'int' => 192, 'crit' => 0.50, 'dodge' => 0.25],
                    'abilities' => []
                ],
                [
                    'name' => 'Zbezczeszczony Golem',
                    'type' => 'mystical',
                    'level' => 92,
                    'rank' => 'regular',
                    'stats' => ['hp' => 40786, 'atk' => 14874, 'def' => 1610, 'agi' => 53, 'int' => 80, 'crit' => 0.42, 'dodge' => 0.18],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Plagi',
                    'type' => 'animal',
                    'level' => 94,
                    'rank' => 'regular',
                    'stats' => ['hp' => 33837, 'atk' => 14098, 'def' => 1090, 'agi' => 90, 'int' => 48, 'crit' => 0.55, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Skazy',
                    'type' => 'demon',
                    'level' => 98,
                    'rank' => 'regular',
                    'stats' => ['hp' => 42296, 'atk' => 15433, 'def' => 1718, 'agi' => 71, 'int' => 80, 'crit' => 0.48, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Książę Zniszczenia',
                    'type' => 'demon',
                    'level' => 102,
                    'rank' => 'boss',
                    'stats' => ['hp' => 160000, 'atk' => 52000, 'def' => 2000, 'agi' => 44, 'int' => 250, 'crit' => 0.60, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Pan Zniszczenia',
                    'type' => 'demon',
                    'level' => 99,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 800000000, 'atk' => 65000, 'def' => 10000, 'agi' => 160, 'int' => 320, 'crit' => 1.2, 'dodge' => 0.8],
                    'abilities' => []
                ]
            ]
        ];

        $mapCount = 0;
        foreach ($monstersByMap as $mapName => $monsters) {
            $mapCount++;
            $map = Map::where('name', $mapName)->first();

            if (!$map) {
                $this->command->warn("Map '{$mapName}' not found, skipping monsters.");
                continue;
            }

            foreach ($monsters as $monsterData) {
                $stats = $monsterData['stats'];
                if ($monsterData['rank'] !== 'worldboss' && ($mapCount >= 3 || ($map->tier && $map->tier >= 3))) {
                    foreach ($stats as $key => $val) {
                        if (in_array($key, ['hp', 'atk', 'def', 'agi', 'int'])) {
                            $stats[$key] = (int)round($val * 1.35);
                        } elseif (in_array($key, ['crit', 'dodge'])) {
                            $stats[$key] = round($val * 1.35, 2);
                        }
                    }
                }

                Monster::updateOrCreate(
                    [
                        'name' => $monsterData['name'],
                    ],
                    [
                        'map_id' => $map->id,
                        'type' => $monsterData['type'],
                        'level' => $monsterData['level'],
                        'rank' => $monsterData['rank'],
                        'stats' => $stats,
                        'abilities' => $monsterData['abilities'],
                        'avatar' => Str::slug($monsterData['name']) . '.png',
                    ]
                );
            }
        }

        $this->command->info('Monster seeder completed - created/updated monsters for all maps.');
    }
}
