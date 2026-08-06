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
    // 'boss' NIE została w tym przebiegu ruszona.
    //
    // UWAGA (follow-up rebalansu, 2026-08-05 - zbadane i CELOWO odrzucone): próba
    // przeliczenia rangi `boss` przez ten sam kalkulator ujawniła, że metodologia
    // `BalanceMonstersCommand::$allMonstersRaw` (statyczna tabela referencyjna,
    // używana WYŁĄCZNIE do wyliczenia współczynników skalowania k_hp/k_atk/k_def/k_agi
    // i do wypisania linii "RAW ... (było ... efektywne)") jest niespójna z realnym
    // rosterem `MonsterSeeder.php`: (1) każda mapa ma dziś DWA potwory rangi `boss`
    // (np. Mroczny Las: "Widmowy Leśny Niedźwiedź" ORAZ "Strażnik Puszczy"), a
    // kalkulator śledzi tylko JEDNEGO na mapę; (2) nazwa "Władca Krypty" w tabeli
    // kalkulatora dla Starych Ruin okazała się odnosić do zupełnie INNEGO potwora -
    // bossa LOCHU zdefiniowanego osobno w `DungeonSeeder.php` (poziom 33), nie do
    // faktycznego bossa mapy "Starożytny Golem Kamienny" (poziom 25) - podstawienie
    // wartości jednego pod drugiego omyłkowo nadpisałoby staty niepowiązanego potwora.
    // Zastosowanie surowego wyniku `--rank=boss` bez naprawienia tej rozbieżności
    // (rozszerzenia `$allMonstersRaw` o drugi slot bossa per mapa I usunięcia
    // pomyłkowych nazw pokrywających się z bossami lochów) ryzykowałoby ciche
    // uszkodzenie/pominięcie części rosteru bossów. Rekalibracja `boss` odłożona do
    // osobnej, dedykowanej sesji - staty bossów (mapowych i lochowych) pozostają
    // nietknięte, na oryginalnych, przed-rebalansowych wartościach.
    //
    // UWAGA (Faza 5 rebalansu, rozdział D, 2026-08-05): staty rangi 'regular' PONOWNIE
    // przeliczone po spłaszczeniu skalowania `scale` między tierami przedmiotów
    // (`ItemTemplateSeeder`/`ShopEquipmentSeeder`, x1.20/tier zamiast dawnych
    // ~2-3.8x/tier) oraz po naprawie `BalanceMonstersCommand::$gearMultiplier`, który
    // wcześniej liczył bonus ulepszenia ze STAREJ, płaskiej krzywej 10%/poziom zamiast
    // nowej, przyspieszającej krzywej Kuźni (`ItemInstance::UPGRADE_BONUS_PERCENT_BY_LEVEL`).
    // Efekt: bezwzględne HP/ATK/DEF potworów znacznie niższe niż w poprzednim przebiegu
    // (proporcjonalnie do dużo słabszej bazowej mocy przedmiotów po rozdziale D) -
    // względna trudność (3-4 trafienia/90% winrate) pozostaje ta sama.
    //
    // UWAGA (follow-up rebalansu, 2026-08-05): naprawiony bug auto-ataku różdżką
    // (`wand` czytał fizyczny STR+AGI/attack_min/max zamiast magicznego INT*2/
    // magic_attack_min/max - patrz `docs/modules/combat.md` pkt 2) we wszystkich 5
    // silnikach walki i w kalkulatorze. `wand` wrócił do fazy strojenia (wcześniej
    // wykluczony jako jedyny sposób obejścia tego buga) - staty PONOWNIE przeliczone
    // (trzeci raz w tej serii), teraz z pełnym, sprawnym zestawem 6 archetypów.
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
                    'stats' => ['hp' => 386, 'atk' => 60, 'def' => 6, 'agi' => 5, 'int' => 3, 'crit' => 0.20, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Wilk Leśny',
                    'type' => 'animal',
                    'level' => 3,
                    'rank' => 'regular',
                    'stats' => ['hp' => 70, 'atk' => 34, 'def' => 3, 'agi' => 5, 'int' => 3, 'crit' => 0.2, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Nietoperz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 4,
                    'rank' => 'regular',
                    'stats' => ['hp' => 75, 'atk' => 37, 'def' => 2, 'agi' => 6, 'int' => 3, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Leśny',
                    'type' => 'animal',
                    'level' => 5,
                    'rank' => 'regular',
                    'stats' => ['hp' => 80, 'atk' => 40, 'def' => 3, 'agi' => 6, 'int' => 3, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Suchodrzew',
                    'type' => 'mystical',
                    'level' => 6,
                    'rank' => 'regular',
                    'stats' => ['hp' => 85, 'atk' => 42, 'def' => 5, 'agi' => 2, 'int' => 3, 'crit' => 0.12, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Zdziczały Dzik',
                    'type' => 'animal',
                    'level' => 7,
                    'rank' => 'regular',
                    'stats' => ['hp' => 90, 'atk' => 44, 'def' => 5, 'agi' => 4, 'int' => 2, 'crit' => 0.18, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Goblin Zwiadowca',
                    'type' => 'mystical',
                    'level' => 8,
                    'rank' => 'regular',
                    'stats' => ['hp' => 95, 'atk' => 46, 'def' => 4, 'agi' => 7, 'int' => 5, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Puszczy',
                    'type' => 'mystical',
                    'level' => 12,
                    'rank' => 'boss',
                    'stats' => ['hp' => 444, 'atk' => 67, 'def' => 7, 'agi' => 6, 'int' => 9, 'crit' => 0.25, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Lasu',
                    'type' => 'animal',
                    'level' => 10,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 6660, 'atk' => 101, 'def' => 9, 'agi' => 7, 'int' => 7, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Stare Ruiny' => [
                [
                    'name' => 'Starożytny Golem Kamienny',
                    'type' => 'mystical',
                    'level' => 25,
                    'rank' => 'boss',
                    'stats' => ['hp' => 1469, 'atk' => 88, 'def' => 21, 'agi' => 16, 'int' => 6, 'crit' => 0.15, 'dodge' => 0.05],
                    'abilities' => []
                ],
                [
                    'name' => 'Szkielet Wojownik',
                    'type' => 'undead',
                    'level' => 14,
                    'rank' => 'regular',
                    'stats' => ['hp' => 130, 'atk' => 48, 'def' => 8, 'agi' => 13, 'int' => 8, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Kultysta',
                    'type' => 'mystical',
                    'level' => 16,
                    'rank' => 'regular',
                    'stats' => ['hp' => 145, 'atk' => 52, 'def' => 9, 'agi' => 15, 'int' => 45, 'crit' => 0.26, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Duch Strażnik',
                    'type' => 'undead',
                    'level' => 18,
                    'rank' => 'regular',
                    'stats' => ['hp' => 160, 'atk' => 56, 'def' => 10, 'agi' => 19, 'int' => 32, 'crit' => 0.28, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Ghul',
                    'type' => 'undead',
                    'level' => 20,
                    'rank' => 'regular',
                    'stats' => ['hp' => 180, 'atk' => 60, 'def' => 12, 'agi' => 13, 'int' => 12, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Upiorny Łucznik',
                    'type' => 'undead',
                    'level' => 22,
                    'rank' => 'regular',
                    'stats' => ['hp' => 195, 'atk' => 65, 'def' => 13, 'agi' => 24, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Kamienny Gargulec',
                    'type' => 'mystical',
                    'level' => 23,
                    'rank' => 'regular',
                    'stats' => ['hp' => 210, 'atk' => 68, 'def' => 15, 'agi' => 11, 'int' => 10, 'crit' => 0.20, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Ruin',
                    'type' => 'undead',
                    'level' => 27,
                    'rank' => 'boss',
                    'stats' => ['hp' => 1689, 'atk' => 99, 'def' => 24, 'agi' => 18, 'int' => 40, 'crit' => 0.35, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Licz Cieni',
                    'type' => 'undead',
                    'level' => 25,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 25335, 'atk' => 149, 'def' => 31, 'agi' => 22, 'int' => 80, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Jaskinia Trolli' => [
                [
                    'name' => 'Mroczny Władca Trolli',
                    'type' => 'troll',
                    'level' => 36,
                    'rank' => 'boss',
                    'stats' => ['hp' => 2482, 'atk' => 196, 'def' => 34, 'agi' => 27, 'int' => 8, 'crit' => 0.20, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Paskudnik',
                    'type' => 'troll',
                    'level' => 26,
                    'rank' => 'regular',
                    'stats' => ['hp' => 535, 'atk' => 126, 'def' => 28, 'agi' => 14, 'int' => 8, 'crit' => 0.2, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Pełzacz Jaskiniowy',
                    'type' => 'animal',
                    'level' => 28,
                    'rank' => 'regular',
                    'stats' => ['hp' => 474, 'atk' => 116, 'def' => 24, 'agi' => 23, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Szaman',
                    'type' => 'troll',
                    'level' => 29,
                    'rank' => 'regular',
                    'stats' => ['hp' => 487, 'atk' => 109, 'def' => 22, 'agi' => 17, 'int' => 56, 'crit' => 0.24, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Ogr Rozłupywacz',
                    'type' => 'mystical',
                    'level' => 32,
                    'rank' => 'regular',
                    'stats' => ['hp' => 633, 'atk' => 151, 'def' => 31, 'agi' => 14, 'int' => 12, 'crit' => 0.28, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Jaskiniowy Nietoperz Alfa',
                    'type' => 'animal',
                    'level' => 33,
                    'rank' => 'regular',
                    'stats' => ['hp' => 438, 'atk' => 117, 'def' => 16, 'agi' => 39, 'int' => 16, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Troll Scalony',
                    'type' => 'troll',
                    'level' => 34,
                    'rank' => 'regular',
                    'stats' => ['hp' => 584, 'atk' => 141, 'def' => 30, 'agi' => 13, 'int' => 12, 'crit' => 0.25, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Starożytny Ogr',
                    'type' => 'mystical',
                    'level' => 38,
                    'rank' => 'boss',
                    'stats' => ['hp' => 2854, 'atk' => 219, 'def' => 39, 'agi' => 31, 'int' => 15, 'crit' => 0.32, 'dodge' => 0.06],
                    'abilities' => []
                ],
                [
                    'name' => 'Król Trolli',
                    'type' => 'troll',
                    'level' => 35,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 57795, 'atk' => 444, 'def' => 69, 'agi' => 50, 'int' => 20, 'crit' => 0.4, 'dodge' => 0.04],
                    'abilities' => []
                ]
            ],
            'Pustkowia Orków' => [
                [
                    'name' => 'Wojownik Cienia Orków',
                    'type' => 'orc',
                    'level' => 49,
                    'rank' => 'boss',
                    'stats' => ['hp' => 3436, 'atk' => 255, 'def' => 46, 'agi' => 37, 'int' => 10, 'crit' => 0.22, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Orczy Zwiad',
                    'type' => 'orc',
                    'level' => 37,
                    'rank' => 'regular',
                    'stats' => ['hp' => 609, 'atk' => 135, 'def' => 31, 'agi' => 32, 'int' => 22, 'crit' => 0.36, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Pustynny Skorpion',
                    'type' => 'animal',
                    'level' => 40,
                    'rank' => 'regular',
                    'stats' => ['hp' => 673, 'atk' => 152, 'def' => 33, 'agi' => 29, 'int' => 19, 'crit' => 0.38, 'dodge' => 0.18],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Berserker',
                    'type' => 'orc',
                    'level' => 42,
                    'rank' => 'regular',
                    'stats' => ['hp' => 749, 'atk' => 176, 'def' => 34, 'agi' => 27, 'int' => 16, 'crit' => 0.48, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Ork Topornik',
                    'type' => 'orc',
                    'level' => 44,
                    'rank' => 'regular',
                    'stats' => ['hp' => 761, 'atk' => 181, 'def' => 38, 'agi' => 24, 'int' => 21, 'crit' => 0.42, 'dodge' => 0.14],
                    'abilities' => []
                ],
                [
                    'name' => 'Szaman Krwi',
                    'type' => 'orc',
                    'level' => 46,
                    'rank' => 'regular',
                    'stats' => ['hp' => 679, 'atk' => 152, 'def' => 27, 'agi' => 23, 'int' => 111, 'crit' => 0.32, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Dowódca Watahy',
                    'type' => 'orc',
                    'level' => 48,
                    'rank' => 'regular',
                    'stats' => ['hp' => 819, 'atk' => 196, 'def' => 41, 'agi' => 30, 'int' => 33, 'crit' => 0.48, 'dodge' => 0.2],
                    'abilities' => []
                ],
                [
                    'name' => 'Niszczyciel Pustkowi',
                    'type' => 'orc',
                    'level' => 53,
                    'rank' => 'boss',
                    'stats' => ['hp' => 3951, 'atk' => 284, 'def' => 53, 'agi' => 43, 'int' => 41, 'crit' => 0.50, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Wódz Orków',
                    'type' => 'orc',
                    'level' => 50,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 80010, 'atk' => 575, 'def' => 94, 'agi' => 70, 'int' => 70, 'crit' => 0.6, 'dodge' => 0.2],
                    'abilities' => []
                ]
            ],
            'Bagna Grozy' => [
                [
                    'name' => 'Bagnisty Behemot Cienia',
                    'type' => 'demon',
                    'level' => 66,
                    'rank' => 'boss',
                    'stats' => ['hp' => 4725, 'atk' => 325, 'def' => 61, 'agi' => 51, 'int' => 15, 'crit' => 0.25, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Topielec',
                    'type' => 'undead',
                    'level' => 52,
                    'rank' => 'regular',
                    'stats' => ['hp' => 959, 'atk' => 197, 'def' => 42, 'agi' => 33, 'int' => 32, 'crit' => 0.32, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Błotny Bazyliszek',
                    'type' => 'mystical',
                    'level' => 55,
                    'rank' => 'regular',
                    'stats' => ['hp' => 999, 'atk' => 206, 'def' => 44, 'agi' => 36, 'int' => 45, 'crit' => 0.35, 'dodge' => 0.20],
                    'abilities' => []
                ],
                [
                    'name' => 'Wiedźmia Straż',
                    'type' => 'demon',
                    'level' => 58,
                    'rank' => 'regular',
                    'stats' => ['hp' => 906, 'atk' => 179, 'def' => 39, 'agi' => 39, 'int' => 104, 'crit' => 0.4, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Drzewiec Plugawy',
                    'type' => 'mystical',
                    'level' => 60,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1119, 'atk' => 222, 'def' => 53, 'agi' => 27, 'int' => 40, 'crit' => 0.24, 'dodge' => 0.16],
                    'abilities' => []
                ],
                [
                    'name' => 'Widmo Bagien',
                    'type' => 'undead',
                    'level' => 62,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1066, 'atk' => 233, 'def' => 42, 'agi' => 48, 'int' => 85, 'crit' => 0.44, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Hydra Bagienna',
                    'type' => 'animal',
                    'level' => 64,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1279, 'atk' => 250, 'def' => 50, 'agi' => 44, 'int' => 72, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Królowa Wiedźm',
                    'type' => 'demon',
                    'level' => 68,
                    'rank' => 'boss',
                    'stats' => ['hp' => 5434, 'atk' => 363, 'def' => 70, 'agi' => 59, 'int' => 150, 'crit' => 0.45, 'dodge' => 0.22],
                    'abilities' => []
                ],
                [
                    'name' => 'Moczarowy Behemot',
                    'type' => 'animal',
                    'level' => 65,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 110040, 'atk' => 735, 'def' => 124, 'agi' => 96, 'int' => 60, 'crit' => 0.4, 'dodge' => 0.08],
                    'abilities' => []
                ]
            ],
            'Góry Cienia' => [
                [
                    'name' => 'Wyvern Cienistego Szczytu',
                    'type' => 'mystical',
                    'level' => 76,
                    'rank' => 'boss',
                    'stats' => ['hp' => 6448, 'atk' => 380, 'def' => 81, 'agi' => 66, 'int' => 20, 'crit' => 0.28, 'dodge' => 0.10],
                    'abilities' => []
                ],
                [
                    'name' => 'Wilk Cienia',
                    'type' => 'animal',
                    'level' => 66,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1293, 'atk' => 267, 'def' => 54, 'agi' => 56, 'int' => 32, 'crit' => 0.56, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Mroczny Gryf',
                    'type' => 'animal',
                    'level' => 68,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1347, 'atk' => 276, 'def' => 58, 'agi' => 59, 'int' => 35, 'crit' => 0.52, 'dodge' => 0.38],
                    'abilities' => []
                ],
                [
                    'name' => 'Golem Bazaltowy',
                    'type' => 'mystical',
                    'level' => 70,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1601, 'atk' => 292, 'def' => 84, 'agi' => 21, 'int' => 24, 'crit' => 0.24, 'dodge' => 0.08],
                    'abilities' => []
                ],
                [
                    'name' => 'Harpia',
                    'type' => 'mystical',
                    'level' => 72,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1262, 'atk' => 259, 'def' => 46, 'agi' => 61, 'int' => 40, 'crit' => 0.56, 'dodge' => 0.48],
                    'abilities' => []
                ],
                [
                    'name' => 'Cieniowy Gargulec',
                    'type' => 'demon',
                    'level' => 73,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1424, 'atk' => 286, 'def' => 68, 'agi' => 55, 'int' => 50, 'crit' => 0.50, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Wędrowny Czarownik',
                    'type' => 'mystical',
                    'level' => 74,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1385, 'atk' => 236, 'def' => 50, 'agi' => 41, 'int' => 128, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Władca Cieni',
                    'type' => 'demon',
                    'level' => 78,
                    'rank' => 'boss',
                    'stats' => ['hp' => 7415, 'atk' => 423, 'def' => 93, 'agi' => 76, 'int' => 160, 'crit' => 0.65, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Smok Cienia',
                    'type' => 'mystical',
                    'level' => 75,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 150150, 'atk' => 857, 'def' => 164, 'agi' => 124, 'int' => 200, 'crit' => 0.8, 'dodge' => 0.4],
                    'abilities' => []
                ]
            ],
            'Wieża Magów' => [
                [
                    'name' => 'Arcymag Pustki i Arkanów',
                    'type' => 'mystical',
                    'level' => 86,
                    'rank' => 'boss',
                    'stats' => ['hp' => 7523, 'atk' => 446, 'def' => 93, 'agi' => 75, 'int' => 45, 'crit' => 0.30, 'dodge' => 0.12],
                    'abilities' => []
                ],
                [
                    'name' => 'Adepci Run',
                    'type' => 'mystical',
                    'level' => 76,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1514, 'atk' => 273, 'def' => 59, 'agi' => 56, 'int' => 144, 'crit' => 0.48, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Lodu',
                    'type' => 'mystical',
                    'level' => 78,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1564, 'atk' => 286, 'def' => 70, 'agi' => 53, 'int' => 150, 'crit' => 0.50, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Strażnik Arkanów',
                    'type' => 'mystical',
                    'level' => 80,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1778, 'atk' => 307, 'def' => 77, 'agi' => 49, 'int' => 160, 'crit' => 0.56, 'dodge' => 0.32],
                    'abilities' => []
                ],
                [
                    'name' => 'Żywiołak Płomieni',
                    'type' => 'mystical',
                    'level' => 82,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1646, 'atk' => 343, 'def' => 63, 'agi' => 63, 'int' => 120, 'crit' => 0.6, 'dodge' => 0.28],
                    'abilities' => []
                ],
                [
                    'name' => 'Runiczny Konstrukt',
                    'type' => 'mystical',
                    'level' => 83,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1810, 'atk' => 319, 'def' => 84, 'agi' => 44, 'int' => 165, 'crit' => 0.55, 'dodge' => 0.25],
                    'abilities' => []
                ],
                [
                    'name' => 'Mistrz Iluzji',
                    'type' => 'mystical',
                    'level' => 84,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1712, 'atk' => 299, 'def' => 59, 'agi' => 70, 'int' => 176, 'crit' => 0.64, 'dodge' => 0.4],
                    'abilities' => []
                ],
                [
                    'name' => 'Wielki Inkwizytor',
                    'type' => 'mystical',
                    'level' => 88,
                    'rank' => 'boss',
                    'stats' => ['hp' => 8651, 'atk' => 497, 'def' => 107, 'agi' => 86, 'int' => 450, 'crit' => 0.75, 'dodge' => 0.45],
                    'abilities' => []
                ],
                [
                    'name' => 'Arcymag',
                    'type' => 'mystical',
                    'level' => 85,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 175185, 'atk' => 1007, 'def' => 187, 'agi' => 139, 'int' => 600, 'crit' => 1, 'dodge' => 0.6],
                    'abilities' => []
                ]
            ],
            'Skażone Miasto' => [
                [
                    'name' => 'Władca Skażenia i Plagi',
                    'type' => 'demon',
                    'level' => 99,
                    'rank' => 'boss',
                    'stats' => ['hp' => 8824, 'atk' => 481, 'def' => 106, 'agi' => 85, 'int' => 150, 'crit' => 0.40, 'dodge' => 0.15],
                    'abilities' => []
                ],
                [
                    'name' => 'Zmutowany Nieumarły',
                    'type' => 'undead',
                    'level' => 86,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1852, 'atk' => 330, 'def' => 76, 'agi' => 55, 'int' => 64, 'crit' => 0.45, 'dodge' => 0.20],
                    'abilities' => []
                ],
                [
                    'name' => 'Plagowy Kat',
                    'type' => 'undead',
                    'level' => 88,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1929, 'atk' => 342, 'def' => 80, 'agi' => 57, 'int' => 70, 'crit' => 0.48, 'dodge' => 0.22],
                    'abilities' => []
                ],
                [
                    'name' => 'Czarownica Zgnilizny',
                    'type' => 'demon',
                    'level' => 90,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1790, 'atk' => 306, 'def' => 66, 'agi' => 61, 'int' => 192, 'crit' => 0.50, 'dodge' => 0.25],
                    'abilities' => []
                ],
                [
                    'name' => 'Zbezczeszczony Golem',
                    'type' => 'mystical',
                    'level' => 92,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2083, 'atk' => 357, 'def' => 91, 'agi' => 50, 'int' => 80, 'crit' => 0.42, 'dodge' => 0.18],
                    'abilities' => []
                ],
                [
                    'name' => 'Pająk Plagi',
                    'type' => 'animal',
                    'level' => 94,
                    'rank' => 'regular',
                    'stats' => ['hp' => 1728, 'atk' => 339, 'def' => 61, 'agi' => 86, 'int' => 48, 'crit' => 0.55, 'dodge' => 0.35],
                    'abilities' => []
                ],
                [
                    'name' => 'Rycerz Skazy',
                    'type' => 'demon',
                    'level' => 98,
                    'rank' => 'regular',
                    'stats' => ['hp' => 2160, 'atk' => 370, 'def' => 97, 'agi' => 67, 'int' => 80, 'crit' => 0.48, 'dodge' => 0.24],
                    'abilities' => []
                ],
                [
                    'name' => 'Książę Zniszczenia',
                    'type' => 'demon',
                    'level' => 102,
                    'rank' => 'boss',
                    'stats' => ['hp' => 10148, 'atk' => 537, 'def' => 122, 'agi' => 98, 'int' => 250, 'crit' => 0.60, 'dodge' => 0.30],
                    'abilities' => []
                ],
                [
                    'name' => 'Pan Zniszczenia',
                    'type' => 'demon',
                    'level' => 99,
                    'rank' => 'worldboss',
                    'stats' => ['hp' => 205500, 'atk' => 1088, 'def' => 215, 'agi' => 118, 'int' => 320, 'crit' => 1.2, 'dodge' => 0.8],
                    'abilities' => []
                ]
            ]
        ];

        // ==== Skille potworów (Faza 2 rebalansu, 2026-08-05) ====
        // Wybrane potwory (1-3 per mapa, NIE wszystkie - zwykłe walki nadal mają istnieć)
        // dostają umiejętności bojowe. Magowie (`is_caster` + `is_magic` na skillu
        // `direct_dmg`) rzucają wzmocnione, magiczne pociski. Reszta ma DoT (poison/fire),
        // CC (stun/freeze) lub samoleczenie (heal). Struktura per potwór: {is_caster?,
        // skills:[{name, effect_type, is_magic?, value, duration, cooldown, chance}]}.
        // Wartości: value = mnożnik dmg (direct_dmg/stun/freeze) LUB % (poison/fire/heal).
        // Patrz Monster::getCombatSkills() i docs/modules/combat.md. Klucz = nazwa potwora.
        $monsterSkills = [
            // Mroczny Las
            'Pająk Leśny' => ['skills' => [
                ['name' => 'Jadowity Kęs', 'effect_type' => 'poison', 'value' => 0.03, 'duration' => 3, 'cooldown' => 4, 'chance' => 35],
            ]],
            // Stare Ruiny
            'Mroczny Kultysta' => ['is_caster' => true, 'skills' => [
                ['name' => 'Mroczny Pocisk', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.6, 'cooldown' => 3, 'chance' => 40],
            ]],
            // Jaskinia Trolli
            'Troll Szaman' => ['is_caster' => true, 'skills' => [
                ['name' => 'Klątwa Trolla', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.6, 'cooldown' => 3, 'chance' => 40],
            ]],
            'Troll Scalony' => ['skills' => [
                ['name' => 'Regeneracja Trolla', 'effect_type' => 'heal', 'value' => 0.15, 'cooldown' => 5, 'chance' => 35],
            ]],
            'Ogr Rozłupywacz' => ['skills' => [
                ['name' => 'Miażdżący Cios', 'effect_type' => 'stun', 'value' => 1.0, 'duration' => 1, 'cooldown' => 4, 'chance' => 28],
            ]],
            // Pustkowia Orków
            'Szaman Krwi' => ['is_caster' => true, 'skills' => [
                ['name' => 'Krwawy Pocisk', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.7, 'cooldown' => 3, 'chance' => 42],
            ]],
            'Pustynny Skorpion' => ['skills' => [
                ['name' => 'Żądło Skorpiona', 'effect_type' => 'poison', 'value' => 0.04, 'duration' => 3, 'cooldown' => 4, 'chance' => 35],
            ]],
            // Bagna Grozy
            'Błotny Bazyliszek' => ['skills' => [
                ['name' => 'Jad Bazyliszka', 'effect_type' => 'poison', 'value' => 0.04, 'duration' => 3, 'cooldown' => 4, 'chance' => 38],
            ]],
            'Wiedźmia Straż' => ['skills' => [
                ['name' => 'Wiedźmie Ukojenie', 'effect_type' => 'heal', 'value' => 0.15, 'cooldown' => 5, 'chance' => 35],
            ]],
            // Góry Cienia
            'Wędrowny Czarownik' => ['is_caster' => true, 'skills' => [
                ['name' => 'Cień Zagłady', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.7, 'cooldown' => 3, 'chance' => 42],
            ]],
            'Golem Bazaltowy' => ['skills' => [
                ['name' => 'Trzęsienie Ziemi', 'effect_type' => 'stun', 'value' => 1.0, 'duration' => 1, 'cooldown' => 4, 'chance' => 28],
            ]],
            // Wieża Magów (dużo magów)
            'Adepci Run' => ['is_caster' => true, 'skills' => [
                ['name' => 'Runiczny Pocisk', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.6, 'cooldown' => 3, 'chance' => 40],
            ]],
            'Żywiołak Lodu' => ['is_caster' => true, 'skills' => [
                ['name' => 'Lodowe Okowy', 'effect_type' => 'freeze', 'is_magic' => true, 'value' => 1.1, 'duration' => 1, 'cooldown' => 5, 'chance' => 25],
            ]],
            'Żywiołak Płomieni' => ['is_caster' => true, 'skills' => [
                ['name' => 'Podpalenie', 'effect_type' => 'fire', 'value' => 0.04, 'duration' => 3, 'cooldown' => 4, 'chance' => 40],
            ]],
            'Mistrz Iluzji' => ['is_caster' => true, 'skills' => [
                ['name' => 'Iluzoryczny Pocisk', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.8, 'cooldown' => 3, 'chance' => 45],
            ]],
            // Skażone Miasto
            'Czarownica Zgnilizny' => ['is_caster' => true, 'skills' => [
                ['name' => 'Plugawy Pocisk', 'effect_type' => 'direct_dmg', 'is_magic' => true, 'value' => 1.7, 'cooldown' => 3, 'chance' => 42],
            ]],
            'Pająk Plagi' => ['skills' => [
                ['name' => 'Zaraza', 'effect_type' => 'poison', 'value' => 0.05, 'duration' => 3, 'cooldown' => 4, 'chance' => 40],
            ]],
            'Zbezczeszczony Golem' => ['skills' => [
                ['name' => 'Zmiażdżenie', 'effect_type' => 'stun', 'value' => 1.0, 'duration' => 1, 'cooldown' => 4, 'chance' => 28],
            ]],
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

                // Faza 2: przypisz skille potworów (jeśli zdefiniowane dla tej nazwy).
                $abilities = $monsterSkills[$monsterData['name']] ?? $monsterData['abilities'];

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
                        'abilities' => $abilities,
                        'avatar' => Str::slug($monsterData['name']) . '.png',
                    ]
                );
            }
        }

        $this->command->info('Monster seeder completed - created/updated monsters for all maps.');
    }
}
