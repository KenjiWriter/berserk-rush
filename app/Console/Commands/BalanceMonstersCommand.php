<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Kalkulator balansu potwór/ekwipunek (bez Laravela/DB) - patrz też
 * database/balance/README.md dla wyjaśnienia metodologii.
 *
 * Wzory bojowe są RĘCZNIE przepisane 1:1 z app/Application/Combat/EncounterService.php
 * i app/Infrastructure/Persistence/Character.php (stan na 2026-07-29). Jeśli te pliki
 * się zmienią, ten kalkulator trzeba zsynchronizować ręcznie - nie ma między nimi
 * automatycznego powiązania.
 *
 * Metodologia (ustalona z użytkownikiem):
 * - Dla każdej z 8 map budujemy 6 postaci referencyjnych - po jednej na każdy z 6
 *   typów broni w grze (sword/axe/bow/dagger/bell = fizyczne, wand = magiczna).
 * - Poziom postaci = level_min mapy + 1. Ekwipunek = najlepszy dostępny na ten poziom
 *   TIER SKLEPOWY (ShopEquipmentSeeder) - celowo klasyczny/uniwersalny, bez surowych
 *   bonusów atrybutów (to właśnie znaczy "bez dodatkowych atrybutów" z prośby
 *   użytkownika - zestawy klasowe _w/_m/_a z ItemTemplateSeeder NIE są tu używane).
 * - Punkty atrybutów postaci (10 + (lvl-1)*3) rozdzielone 40/30/30: fizyczne
 *   archetypy -> STR/AGI/VIT, wand -> INT/AGI/VIT.
 * - Cel: >=90% winrate (z wielu tysięcy symulacji), zabicie potwora w 3-4 trafieniach,
 *   potwór zdejmuje ~60% (3/5) maxHP bohatera w trakcie walki.
 * - Pola 'crit'/'dodge' w danych potworów są MARTWE (EncounterService liczy je zawsze
 *   z formuł opartych o AGI, nigdy nie czyta monster.stats['crit'/'dodge']) - dlatego
 *   solver dobiera tylko HP/ATK/DEF/AGI.
 */
class BalanceMonstersCommand extends Command
{
    protected $signature = 'balance:monsters {--rank=regular} {--tune-iterations=25} {--sims=4000} {--verify-sims=8000}';
    protected $description = 'Symuluje walki referencyjnych postaci i dobiera statystyki potworów (HP/ATK/DEF/AGI) pod zadane cele balansu. --rank=regular|boss (worldboss celowo pominięty - system czeka na osobny rework).';

    // --- Stałe z Character.php ---
    private const ATTRIBUTE_DAMAGE_MULTIPLIER = 1.5;
    private const ATTRIBUTE_HP_MULTIPLIER = 15;

    // --- Cele balansu dla 'regular' (ustalone z użytkownikiem, 2026-07-29) ---
    private const TARGET_WINRATE = 0.90;
    private const TARGET_HITS_MID = 3.5;
    private const TARGET_DMG_TAKEN_FRACTION = 0.60;

    // --- Cele balansu dla 'boss' (ustalone z użytkownikiem, 2026-07-29): trudniejszy
    // regular - niższy winrate, dużo więcej trafień, bliżej śmierci. Worldboss
    // celowo POMINIĘTY na życzenie użytkownika (walczy się z nim raz/h w osobnym
    // panelu, mechanika ma przejść osobny rework, nie ma sensu go teraz kalibrować).
    private const TARGET_WINRATE_BOSS = 0.65;
    private const TARGET_HITS_MID_BOSS = 10.0;
    private const TARGET_DMG_TAKEN_FRACTION_BOSS = 0.90;

    /**
     * Tiery sklepowe (ShopEquipmentSeeder::$themes), BEZ tieru gladiatora (lvl 55,
     * dostępny tylko przez Arenę, nie przez zwykły zakup) - indeks tutaj = indeks
     * w oryginalnym $themes (potrzebny do formuły crit_chance = base + themeIndex*2).
     */
    private array $shopTiers = [
        0  => ['level' => 1,  'scale' => 1.2],
        1  => ['level' => 10, 'scale' => 4.0],
        2  => ['level' => 20, 'scale' => 10.0],
        3  => ['level' => 30, 'scale' => 22.0],
        4  => ['level' => 40, 'scale' => 45.0],
        5  => ['level' => 50, 'scale' => 100.0],
        // themeIndex 6 = gladiator (lvl 55) - pominięty w merchantTiers poniżej
        7  => ['level' => 60, 'scale' => 250.0],
        8  => ['level' => 70, 'scale' => 600.0],
        9  => ['level' => 80, 'scale' => 1800.0],
        10 => ['level' => 90, 'scale' => 4500.0],
    ];

    /**
     * Prototypy broni ze SKLEPU (ShopEquipmentSeeder) - UWAGA: sklep sprzedaje WSZYSTKIE
     * 6 typów broni tylko na tierze poziom=1 (themeIndex 0)! Każdy wyższy tier sklepowy
     * ma w $theme['names'] zdefiniowany wyłącznie 'sword' - topór/łuk/sztylet/dzwon/
     * różdżka NIE są tam sprzedawane od poziomu 10 wzwyż (patrz pętla `if
     * (!isset($theme['names'][$protoKey])) continue;` w ShopEquipmentSeeder::run()).
     * Dlatego ten prototyp jest używany TYLKO dla mapy 1 (poziom ~4, tier sklepowy 1)
     * - dla map 2-8 broń pochodzi z $craftWeaponProtos/$craftTiers (crafting/drop),
     * bo tam WSZYSTKIE 6 typów istnieje na każdym tierze.
     */
    private array $shopWeaponProtos = [
        'sword'  => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 1],
        'axe'    => ['attack_min' => 0.75, 'attack_max' => 6.75],
        'bow'    => ['attack_min' => 1.5, 'attack_max' => 3.75, 'crit_chance' => 2],
        'wand'   => ['magic_attack_min' => 2.25, 'magic_attack_max' => 5.25, 'crit_chance' => 1],
        'dagger' => ['attack_min' => 1.2, 'attack_max' => 4.8, 'crit_chance' => 6],
        'bell'   => ['attack_min' => 1.5, 'attack_max' => 3.5, 'magic_burst_chance' => 25, 'magic_burst_min' => 2.25, 'magic_burst_max' => 4.5],
    ];

    /**
     * Prototypy broni CRAFT/DROP (ItemTemplateSeeder) - pełne pokrycie wszystkich 6 typów
     * na każdym tierze (5/15/25/35/45/55/65/75/85/95/99). Bronie tu też NIE dają surowych
     * atrybutów (tylko obrażenia/crit/magic burst) - tylko zestawy zbroi _w/_m/_a mają
     * bonusy atrybutów, a tych celowo nie używamy (bierzemy zbroję ze sklepu).
     * Wartości str/agi/vit-DOSTROJONE (patrz balans sztyletu/dzwonu niżej) względem
     * oryginału z ItemTemplateSeeder.php, żeby wyrównać ich winrate do reszty broni.
     */
    private array $craftWeaponProtos = [
        'sword'  => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 1],
        'axe'    => ['attack_min' => 0.75, 'attack_max' => 7.5],
        'bow'    => ['attack_min' => 1.5, 'attack_max' => 4.5, 'crit_chance' => 3],
        'wand'   => ['magic_attack_min' => 3, 'magic_attack_max' => 6.75, 'crit_chance' => 1],
        'dagger' => ['attack_min' => 1.2, 'attack_max' => 4.8, 'crit_chance' => 8],
        'bell'   => ['attack_min' => 1.8, 'attack_max' => 3.6, 'magic_burst_chance' => 30, 'magic_burst_min' => 3, 'magic_burst_max' => 6.75],
    ];

    /** Zbroja/biżuteria uniwersalna (ShopEquipmentSeeder) - też wartości bazowe przed scale. */
    private array $armorProtos = [
        'armor'  => ['defense' => 4, 'hp_bonus' => 13.5],
        'helmet' => ['defense' => 2, 'hp_bonus' => 7.5],
        'boots'  => ['defense' => 1, 'hp_bonus' => 4.5],
        'amulet' => ['defense' => 1, 'hp_bonus' => 11.25, 'mana_bonus' => 10],
        'ring'   => ['crit_chance' => 2, 'hp_bonus' => 6],
    ];

    /**
     * Tiery craft/drop (ItemTemplateSeeder::$themes) - indeks = pozycja w oryginalnym
     * $themes (potrzebne do formuły crit_chance/magic_burst_chance = base + idx*2,
     * capowanej na 50/70 - INNE caps niż w sklepie, patrz scaleStat()).
     */
    private array $craftTiers = [
        0  => ['level' => 5,  'scale' => 2.0],
        1  => ['level' => 15, 'scale' => 6.0],
        2  => ['level' => 25, 'scale' => 15.0],
        3  => ['level' => 35, 'scale' => 35.0],
        4  => ['level' => 45, 'scale' => 80.0],
        5  => ['level' => 55, 'scale' => 180.0],
        6  => ['level' => 65, 'scale' => 400.0],
        7  => ['level' => 75, 'scale' => 1000.0],
        8  => ['level' => 85, 'scale' => 3000.0],
        9  => ['level' => 95, 'scale' => 7000.0],
        10 => ['level' => 99, 'scale' => 15000.0],
    ];

    /**
     * 8 map gry (MonsterSeeder) z level_min i mapCount (pozycja w seederze - decyduje
     * o mnożniku x1.35 dla mapCount>=3 na ISTNIEJĄCYCH statach, potrzebne tylko do
     * policzenia "starej" efektywnej średniej dla raportu porównawczego).
     */
    private array $maps = [
        ['name' => 'Mroczny Las',      'levelMin' => 3,  'mapCount' => 1],
        ['name' => 'Stare Ruiny',      'levelMin' => 14, 'mapCount' => 2],
        ['name' => 'Jaskinia Trolli',  'levelMin' => 26, 'mapCount' => 3],
        ['name' => 'Pustkowia Orków',  'levelMin' => 37, 'mapCount' => 4],
        ['name' => 'Bagna Grozy',      'levelMin' => 52, 'mapCount' => 5],
        ['name' => 'Góry Cienia',      'levelMin' => 66, 'mapCount' => 6],
        ['name' => 'Wieża Magów',      'levelMin' => 76, 'mapCount' => 7],
        ['name' => 'Skażone Miasto',   'levelMin' => 86, 'mapCount' => 8],
    ];

    /** Aktualne (ręcznie wpisane) staty potworów 'regular' per mapa - z MonsterSeeder.php, PRZED x1.35. */
    private array $currentRegularStatsRaw = [
        'Mroczny Las' => [
            ['hp' => 112, 'atk' => 20, 'def' => 6, 'agi' => 17],
            ['hp' => 90, 'atk' => 17, 'def' => 3, 'agi' => 22],
            ['hp' => 125, 'atk' => 22, 'def' => 7, 'agi' => 19],
            ['hp' => 162, 'atk' => 25, 'def' => 11, 'agi' => 8],
            ['hp' => 150, 'atk' => 26, 'def' => 10, 'agi' => 12],
            ['hp' => 140, 'atk' => 28, 'def' => 8, 'agi' => 25],
        ],
        'Stare Ruiny' => [
            ['hp' => 440, 'atk' => 72, 'def' => 36, 'agi' => 32],
            ['hp' => 400, 'atk' => 68, 'def' => 28, 'agi' => 36],
            ['hp' => 380, 'atk' => 64, 'def' => 24, 'agi' => 44],
            ['hp' => 520, 'atk' => 88, 'def' => 40, 'agi' => 32],
            ['hp' => 420, 'atk' => 96, 'def' => 32, 'agi' => 56],
            ['hp' => 480, 'atk' => 84, 'def' => 48, 'agi' => 26],
        ],
        'Jaskinia Trolli' => [
            ['hp' => 880, 'atk' => 120, 'def' => 72, 'agi' => 24],
            ['hp' => 780, 'atk' => 110, 'def' => 60, 'agi' => 38],
            ['hp' => 800, 'atk' => 104, 'def' => 56, 'agi' => 28],
            ['hp' => 1040, 'atk' => 144, 'def' => 80, 'agi' => 24],
            ['hp' => 720, 'atk' => 112, 'def' => 40, 'agi' => 64],
            ['hp' => 960, 'atk' => 135, 'def' => 76, 'agi' => 22],
        ],
        'Pustkowia Orków' => [
            ['hp' => 1040, 'atk' => 160, 'def' => 72, 'agi' => 56],
            ['hp' => 1150, 'atk' => 180, 'def' => 76, 'agi' => 50],
            ['hp' => 1280, 'atk' => 208, 'def' => 80, 'agi' => 48],
            ['hp' => 1300, 'atk' => 215, 'def' => 88, 'agi' => 42],
            ['hp' => 1160, 'atk' => 180, 'def' => 64, 'agi' => 40],
            ['hp' => 1400, 'atk' => 232, 'def' => 96, 'agi' => 52],
        ],
        'Bagna Grozy' => [
            ['hp' => 1440, 'atk' => 220, 'def' => 96, 'agi' => 48],
            ['hp' => 1500, 'atk' => 230, 'def' => 100, 'agi' => 52],
            ['hp' => 1360, 'atk' => 200, 'def' => 88, 'agi' => 56],
            ['hp' => 1680, 'atk' => 248, 'def' => 120, 'agi' => 40],
            ['hp' => 1600, 'atk' => 260, 'def' => 95, 'agi' => 70],
            ['hp' => 1920, 'atk' => 280, 'def' => 112, 'agi' => 64],
        ],
        'Góry Cienia' => [
            ['hp' => 1680, 'atk' => 272, 'def' => 104, 'agi' => 88],
            ['hp' => 1750, 'atk' => 280, 'def' => 110, 'agi' => 92],
            ['hp' => 2080, 'atk' => 296, 'def' => 160, 'agi' => 32],
            ['hp' => 1640, 'atk' => 264, 'def' => 88, 'agi' => 96],
            ['hp' => 1850, 'atk' => 290, 'def' => 130, 'agi' => 85],
            ['hp' => 1800, 'atk' => 240, 'def' => 96, 'agi' => 64],
        ],
        'Wieża Magów' => [
            ['hp' => 1840, 'atk' => 248, 'def' => 104, 'agi' => 64],
            ['hp' => 1900, 'atk' => 260, 'def' => 125, 'agi' => 60],
            ['hp' => 2160, 'atk' => 280, 'def' => 136, 'agi' => 56],
            ['hp' => 2000, 'atk' => 312, 'def' => 112, 'agi' => 72],
            ['hp' => 2200, 'atk' => 290, 'def' => 150, 'agi' => 50],
            ['hp' => 2080, 'atk' => 272, 'def' => 104, 'agi' => 80],
        ],
        'Skażone Miasto' => [
            ['hp' => 2400, 'atk' => 328, 'def' => 136, 'agi' => 72],
            ['hp' => 2500, 'atk' => 340, 'def' => 145, 'agi' => 75],
            ['hp' => 2320, 'atk' => 304, 'def' => 120, 'agi' => 80],
            ['hp' => 2700, 'atk' => 355, 'def' => 165, 'agi' => 65],
            ['hp' => 2240, 'atk' => 336, 'def' => 112, 'agi' => 112],
            ['hp' => 2800, 'atk' => 368, 'def' => 176, 'agi' => 88],
        ],
    ];

    /** Wszystkie potwory (regular+boss+worldboss) z MonsterSeeder.php, RAW (przed x1.35). */
    private array $allMonstersRaw = [
        'Mroczny Las' => [
            ['name' => 'Wilk Leśny', 'rank' => 'regular', 'stats' => ['hp' => 112, 'atk' => 20, 'def' => 6, 'agi' => 17]],
            ['name' => 'Nietoperz Jaskiniowy', 'rank' => 'regular', 'stats' => ['hp' => 90, 'atk' => 17, 'def' => 3, 'agi' => 22]],
            ['name' => 'Pająk Leśny', 'rank' => 'regular', 'stats' => ['hp' => 125, 'atk' => 22, 'def' => 7, 'agi' => 19]],
            ['name' => 'Suchodrzew', 'rank' => 'regular', 'stats' => ['hp' => 162, 'atk' => 25, 'def' => 11, 'agi' => 8]],
            ['name' => 'Zdziczały Dzik', 'rank' => 'regular', 'stats' => ['hp' => 150, 'atk' => 26, 'def' => 10, 'agi' => 12]],
            ['name' => 'Goblin Zwiadowca', 'rank' => 'regular', 'stats' => ['hp' => 140, 'atk' => 28, 'def' => 8, 'agi' => 25]],
            ['name' => 'Strażnik Puszczy', 'rank' => 'boss', 'stats' => ['hp' => 2200, 'atk' => 38, 'def' => 18, 'agi' => 12]],
            ['name' => 'Król Lasu', 'rank' => 'worldboss', 'stats' => ['hp' => 14000, 'atk' => 42, 'def' => 22, 'agi' => 14]],
        ],
        'Stare Ruiny' => [
            ['name' => 'Szkielet Wojownik', 'rank' => 'regular', 'stats' => ['hp' => 440, 'atk' => 72, 'def' => 36, 'agi' => 32]],
            ['name' => 'Mroczny Kultysta', 'rank' => 'regular', 'stats' => ['hp' => 400, 'atk' => 68, 'def' => 28, 'agi' => 36]],
            ['name' => 'Duch Strażnik', 'rank' => 'regular', 'stats' => ['hp' => 380, 'atk' => 64, 'def' => 24, 'agi' => 44]],
            ['name' => 'Ghul', 'rank' => 'regular', 'stats' => ['hp' => 520, 'atk' => 88, 'def' => 40, 'agi' => 32]],
            ['name' => 'Upiorny Łucznik', 'rank' => 'regular', 'stats' => ['hp' => 420, 'atk' => 96, 'def' => 32, 'agi' => 56]],
            ['name' => 'Kamienny Gargulec', 'rank' => 'regular', 'stats' => ['hp' => 480, 'atk' => 84, 'def' => 48, 'agi' => 26]],
            ['name' => 'Władca Krypty', 'rank' => 'boss', 'stats' => ['hp' => 7500, 'atk' => 115, 'def' => 52, 'agi' => 35]],
            ['name' => 'Licz Cieni', 'rank' => 'worldboss', 'stats' => ['hp' => 60000, 'atk' => 140, 'def' => 60, 'agi' => 40]],
        ],
        'Jaskinia Trolli' => [
            ['name' => 'Troll Paskudnik', 'rank' => 'regular', 'stats' => ['hp' => 880, 'atk' => 120, 'def' => 72, 'agi' => 24]],
            ['name' => 'Pełzacz Jaskiniowy', 'rank' => 'regular', 'stats' => ['hp' => 780, 'atk' => 110, 'def' => 60, 'agi' => 38]],
            ['name' => 'Troll Szaman', 'rank' => 'regular', 'stats' => ['hp' => 800, 'atk' => 104, 'def' => 56, 'agi' => 28]],
            ['name' => 'Ogr Rozłupywacz', 'rank' => 'regular', 'stats' => ['hp' => 1040, 'atk' => 144, 'def' => 80, 'agi' => 24]],
            ['name' => 'Jaskiniowy Nietoperz Alfa', 'rank' => 'regular', 'stats' => ['hp' => 720, 'atk' => 112, 'def' => 40, 'agi' => 64]],
            ['name' => 'Troll Scalony', 'rank' => 'regular', 'stats' => ['hp' => 960, 'atk' => 135, 'def' => 76, 'agi' => 22]],
            ['name' => 'Starożytny Ogr', 'rank' => 'boss', 'stats' => ['hp' => 18000, 'atk' => 175, 'def' => 95, 'agi' => 25]],
            ['name' => 'Król Trolli', 'rank' => 'worldboss', 'stats' => ['hp' => 140000, 'atk' => 200, 'def' => 120, 'agi' => 20]],
        ],
        'Pustkowia Orków' => [
            ['name' => 'Orczy Zwiad', 'rank' => 'regular', 'stats' => ['hp' => 1040, 'atk' => 160, 'def' => 72, 'agi' => 56]],
            ['name' => 'Pustynny Skorpion', 'rank' => 'regular', 'stats' => ['hp' => 1150, 'atk' => 180, 'def' => 76, 'agi' => 50]],
            ['name' => 'Ork Berserker', 'rank' => 'regular', 'stats' => ['hp' => 1280, 'atk' => 208, 'def' => 80, 'agi' => 48]],
            ['name' => 'Ork Topornik', 'rank' => 'regular', 'stats' => ['hp' => 1300, 'atk' => 215, 'def' => 88, 'agi' => 42]],
            ['name' => 'Szaman Krwi', 'rank' => 'regular', 'stats' => ['hp' => 1160, 'atk' => 180, 'def' => 64, 'agi' => 40]],
            ['name' => 'Dowódca Watahy', 'rank' => 'regular', 'stats' => ['hp' => 1400, 'atk' => 232, 'def' => 96, 'agi' => 52]],
            ['name' => 'Niszczyciel Pustkowi', 'rank' => 'boss', 'stats' => ['hp' => 35000, 'atk' => 280, 'def' => 120, 'agi' => 55]],
            ['name' => 'Wódz Orków', 'rank' => 'worldboss', 'stats' => ['hp' => 240000, 'atk' => 320, 'def' => 140, 'agi' => 60]],
        ],
        'Bagna Grozy' => [
            ['name' => 'Topielec', 'rank' => 'regular', 'stats' => ['hp' => 1440, 'atk' => 220, 'def' => 96, 'agi' => 48]],
            ['name' => 'Błotny Bazyliszek', 'rank' => 'regular', 'stats' => ['hp' => 1500, 'atk' => 230, 'def' => 100, 'agi' => 52]],
            ['name' => 'Wiedźmia Straż', 'rank' => 'regular', 'stats' => ['hp' => 1360, 'atk' => 200, 'def' => 88, 'agi' => 56]],
            ['name' => 'Drzewiec Plugawy', 'rank' => 'regular', 'stats' => ['hp' => 1680, 'atk' => 248, 'def' => 120, 'agi' => 40]],
            ['name' => 'Widmo Bagien', 'rank' => 'regular', 'stats' => ['hp' => 1600, 'atk' => 260, 'def' => 95, 'agi' => 70]],
            ['name' => 'Hydra Bagienna', 'rank' => 'regular', 'stats' => ['hp' => 1920, 'atk' => 280, 'def' => 112, 'agi' => 64]],
            ['name' => 'Królowa Wiedźm', 'rank' => 'boss', 'stats' => ['hp' => 65000, 'atk' => 380, 'def' => 150, 'agi' => 60]],
            ['name' => 'Moczarowy Behemot', 'rank' => 'worldboss', 'stats' => ['hp' => 400000, 'atk' => 440, 'def' => 180, 'agi' => 40]],
        ],
        'Góry Cienia' => [
            ['name' => 'Wilk Cienia', 'rank' => 'regular', 'stats' => ['hp' => 1680, 'atk' => 272, 'def' => 104, 'agi' => 88]],
            ['name' => 'Mroczny Gryf', 'rank' => 'regular', 'stats' => ['hp' => 1750, 'atk' => 280, 'def' => 110, 'agi' => 92]],
            ['name' => 'Golem Bazaltowy', 'rank' => 'regular', 'stats' => ['hp' => 2080, 'atk' => 296, 'def' => 160, 'agi' => 32]],
            ['name' => 'Harpia', 'rank' => 'regular', 'stats' => ['hp' => 1640, 'atk' => 264, 'def' => 88, 'agi' => 96]],
            ['name' => 'Cieniowy Gargulec', 'rank' => 'regular', 'stats' => ['hp' => 1850, 'atk' => 290, 'def' => 130, 'agi' => 85]],
            ['name' => 'Wędrowny Czarownik', 'rank' => 'regular', 'stats' => ['hp' => 1800, 'atk' => 240, 'def' => 96, 'agi' => 64]],
            ['name' => 'Władca Cieni', 'rank' => 'boss', 'stats' => ['hp' => 120000, 'atk' => 490, 'def' => 200, 'agi' => 95]],
            ['name' => 'Smok Cienia', 'rank' => 'worldboss', 'stats' => ['hp' => 720000, 'atk' => 600, 'def' => 240, 'agi' => 100]],
        ],
        'Wieża Magów' => [
            ['name' => 'Adepci Run', 'rank' => 'regular', 'stats' => ['hp' => 1840, 'atk' => 248, 'def' => 104, 'agi' => 64]],
            ['name' => 'Żywiołak Lodu', 'rank' => 'regular', 'stats' => ['hp' => 1900, 'atk' => 260, 'def' => 125, 'agi' => 60]],
            ['name' => 'Strażnik Arkanów', 'rank' => 'regular', 'stats' => ['hp' => 2160, 'atk' => 280, 'def' => 136, 'agi' => 56]],
            ['name' => 'Żywiołak Płomieni', 'rank' => 'regular', 'stats' => ['hp' => 2000, 'atk' => 312, 'def' => 112, 'agi' => 72]],
            ['name' => 'Runiczny Konstrukt', 'rank' => 'regular', 'stats' => ['hp' => 2200, 'atk' => 290, 'def' => 150, 'agi' => 50]],
            ['name' => 'Mistrz Iluzji', 'rank' => 'regular', 'stats' => ['hp' => 2080, 'atk' => 272, 'def' => 104, 'agi' => 80]],
            ['name' => 'Wielki Inkwizytor', 'rank' => 'boss', 'stats' => ['hp' => 220000, 'atk' => 430, 'def' => 180, 'agi' => 90]],
            ['name' => 'Arcymag', 'rank' => 'worldboss', 'stats' => ['hp' => 1000000, 'atk' => 480, 'def' => 200, 'agi' => 120]],
        ],
        'Skażone Miasto' => [
            ['name' => 'Zmutowany Nieumarły', 'rank' => 'regular', 'stats' => ['hp' => 2400, 'atk' => 328, 'def' => 136, 'agi' => 72]],
            ['name' => 'Plagowy Kat', 'rank' => 'regular', 'stats' => ['hp' => 2500, 'atk' => 340, 'def' => 145, 'agi' => 75]],
            ['name' => 'Czarownica Zgnilizny', 'rank' => 'regular', 'stats' => ['hp' => 2320, 'atk' => 304, 'def' => 120, 'agi' => 80]],
            ['name' => 'Zbezczeszczony Golem', 'rank' => 'regular', 'stats' => ['hp' => 2700, 'atk' => 355, 'def' => 165, 'agi' => 65]],
            ['name' => 'Pająk Plagi', 'rank' => 'regular', 'stats' => ['hp' => 2240, 'atk' => 336, 'def' => 112, 'agi' => 112]],
            ['name' => 'Rycerz Skazy', 'rank' => 'regular', 'stats' => ['hp' => 2800, 'atk' => 368, 'def' => 176, 'agi' => 88]],
            ['name' => 'Książę Zniszczenia', 'rank' => 'boss', 'stats' => ['hp' => 450000, 'atk' => 780, 'def' => 320, 'agi' => 130]],
            ['name' => 'Pan Zniszczenia', 'rank' => 'worldboss', 'stats' => ['hp' => 2000000, 'atk' => 1000, 'def' => 400, 'agi' => 160]],
        ],
    ];

    public function handle(): int
    {
        $tuneIterations = (int) $this->option('tune-iterations');
        $tuneSims = (int) $this->option('sims');
        $verifySims = (int) $this->option('verify-sims');
        $rank = $this->option('rank');

        if (!in_array($rank, ['regular', 'boss'], true)) {
            $this->error("Nieobsługiwana ranga '{$rank}' - dozwolone: regular, boss (worldboss celowo pominięty, patrz opis komendy).");
            return self::FAILURE;
        }

        $targetWinrate = $rank === 'boss' ? self::TARGET_WINRATE_BOSS : self::TARGET_WINRATE;
        $targetHitsMid = $rank === 'boss' ? self::TARGET_HITS_MID_BOSS : self::TARGET_HITS_MID;
        $targetDmgFraction = $rank === 'boss' ? self::TARGET_DMG_TAKEN_FRACTION_BOSS : self::TARGET_DMG_TAKEN_FRACTION;

        $summary = [];

        foreach ($this->maps as $map) {
            $charLevel = $map['levelMin'] + 1;
            $armorTierIndex = $this->pickMerchantTierIndex($charLevel);
            $armorTier = $this->shopTiers[$armorTierIndex];

            // Broń: sklep (wszystkie 6 typów) tylko dla najniższego poziomu (craft
            // zaczyna się od poziomu 5) - patrz komentarz przy $shopWeaponProtos.
            if ($charLevel < $this->craftTiers[0]['level']) {
                $weaponSource = 'shop';
                $weaponTierIndex = 0;
                $weaponTier = $this->shopTiers[0];
            } else {
                $weaponSource = 'craft';
                $weaponTierIndex = $this->pickCraftTierIndex($charLevel);
                $weaponTier = $this->craftTiers[$weaponTierIndex];
            }

            $this->line('');
            $this->line("<fg=cyan;options=bold>=== {$map['name']} (level_min={$map['levelMin']}, postać ref. lvl {$charLevel}, zbroja lvl {$armorTier['level']} x{$armorTier['scale']}, broń [{$weaponSource}] lvl {$weaponTier['level']} x{$weaponTier['scale']}) ===</>");

            $weaponProtos = $weaponSource === 'shop' ? $this->shopWeaponProtos : $this->craftWeaponProtos;

            $archetypes = [];
            foreach (array_keys($weaponProtos) as $weaponType) {
                $archetypes[$weaponType] = $this->buildArchetype(
                    $weaponType,
                    $charLevel,
                    $weaponProtos[$weaponType],
                    $weaponTier['scale'],
                    $weaponTierIndex,
                    $weaponSource === 'craft' ? 50 : 40,
                    $weaponSource === 'craft' ? 70 : 60,
                    $armorTier['scale'],
                    $armorTierIndex
                );
            }

            $this->table(
                ['Broń', 'STR', 'INT', 'AGI', 'VIT', 'Atk min-max', 'Crit%', 'MaxHP'],
                array_map(fn ($a, $w) => [
                    $w, $a['str'], $a['int'], $a['agi'], $a['vit'],
                    round($a['weaponAtkMin'] + $a['baseFlat'], 1) . '-' . round($a['weaponAtkMax'] + $a['baseFlat'], 1),
                    round($a['critGearPct'], 1),
                    $a['maxHp'],
                ], $archetypes, array_keys($archetypes))
            );

            [$monster, $verification] = $this->solveMonster($archetypes, $tuneIterations, $tuneSims, $verifySims, $targetWinrate, $targetHitsMid, $targetDmgFraction);

            $this->line("<fg=yellow>Dobrane staty potwora '{$rank}': HP={$monster['hp']} ATK={$monster['atk']} DEF={$monster['def']} AGI={$monster['agi']}</>");

            $this->table(
                ['Broń', 'Winrate%', 'Śr. trafień do zabicia', 'Śr. % HP straconego'],
                array_map(fn ($v, $w) => [
                    $w,
                    round($v['winRate'] * 100, 1),
                    round($v['avgHits'], 2),
                    round($v['avgDmgFraction'] * 100, 1),
                ], $verification, array_keys($verification))
            );

            $oldAvg = $rank === 'boss' ? $this->currentEffectiveForRank($map, 'boss') : $this->currentEffectiveAverage($map);
            $this->line("<fg=gray>Stara śr. efektywna ({$rank}, po x1.35 jeśli dotyczy): HP={$oldAvg['hp']} ATK={$oldAvg['atk']} DEF={$oldAvg['def']} AGI={$oldAvg['agi']}</>");

            $kHp = $monster['hp'] / max(1, $oldAvg['hp']);
            $kAtk = $monster['atk'] / max(1, $oldAvg['atk']);
            $kDef = $oldAvg['def'] > 0 ? $monster['def'] / $oldAvg['def'] : 1.0;
            $kAgi = $oldAvg['agi'] > 0 ? $monster['agi'] / $oldAvg['agi'] : 1.0;

            $this->line("<fg=gray>Współczynniki skalowania k: hp={$this->fmt($kHp)} atk={$this->fmt($kAtk)} def={$this->fmt($kDef)} agi={$this->fmt($kAgi)}</>");

            $multiplier = $map['mapCount'] >= 3 ? 1.35 : 1.0;
            foreach ($this->allMonstersRaw[$map['name']] as $m) {
                if ($m['rank'] !== $rank) {
                    continue; // tylko ranga wybrana przez --rank jest tu przeliczana
                }
                $eff = [
                    'hp' => (int) round($m['stats']['hp'] * $multiplier),
                    'atk' => (int) round($m['stats']['atk'] * $multiplier),
                    'def' => (int) round($m['stats']['def'] * $multiplier),
                    'agi' => (int) round($m['stats']['agi'] * $multiplier),
                ];
                $new = [
                    'hp' => (int) round($eff['hp'] * $kHp),
                    'atk' => (int) round($eff['atk'] * $kAtk),
                    'def' => (int) round($eff['def'] * $kDef),
                    'agi' => (int) round($eff['agi'] * $kAgi),
                ];
                // Wartość RAW do wklejenia w MonsterSeeder.php (PRZED mnożnikiem x1.35 pętli seedera)
                $raw = [
                    'hp' => (int) round($new['hp'] / $multiplier),
                    'atk' => (int) round($new['atk'] / $multiplier),
                    'def' => (int) round($new['def'] / $multiplier),
                    'agi' => (int) round($new['agi'] / $multiplier),
                ];
                $this->line(sprintf(
                    "  RAW %-24s ['hp' => %d, 'atk' => %d, 'def' => %d, 'agi' => %d],  // było hp%d atk%d def%d agi%d (efektywne)",
                    $m['name'], $raw['hp'], $raw['atk'], $raw['def'], $raw['agi'], $eff['hp'], $eff['atk'], $eff['def'], $eff['agi']
                ));
            }

            $summary[$map['name']] = [
                'monster' => $monster,
                'k' => ['hp' => $kHp, 'atk' => $kAtk, 'def' => $kDef, 'agi' => $kAgi],
                'oldAvg' => $oldAvg,
            ];
        }

        $this->line('');
        $this->line('<fg=green;options=bold>=== PODSUMOWANIE (do zastosowania w MonsterSeeder.php) ===</>');
        foreach ($summary as $mapName => $s) {
            $this->line(sprintf(
                '%-18s HP=%-6d ATK=%-5d DEF=%-4d AGI=%-4d | k_hp=%s k_atk=%s k_def=%s k_agi=%s',
                $mapName,
                $s['monster']['hp'],
                $s['monster']['atk'],
                $s['monster']['def'],
                $s['monster']['agi'],
                $this->fmt($s['k']['hp']),
                $this->fmt($s['k']['atk']),
                $this->fmt($s['k']['def']),
                $this->fmt($s['k']['agi'])
            ));
        }

        return self::SUCCESS;
    }

    private function fmt(float $v): string
    {
        return number_format($v, 3);
    }

    private function pickMerchantTierIndex(int $charLevel): int
    {
        $best = 0;
        foreach ($this->shopTiers as $idx => $tier) {
            if ($tier['level'] <= $charLevel && $tier['level'] >= $this->shopTiers[$best]['level']) {
                $best = $idx;
            }
        }
        return $best;
    }

    private function pickCraftTierIndex(int $charLevel): int
    {
        $best = 0;
        foreach ($this->craftTiers as $idx => $tier) {
            if ($tier['level'] <= $charLevel && $tier['level'] >= $this->craftTiers[$best]['level']) {
                $best = $idx;
            }
        }
        return $best;
    }

    /**
     * Buduje jedną postać referencyjną (typ broni) dla danego poziomu. Broń i zbroja
     * mogą pochodzić z RÓŻNYCH źródeł/tierów (patrz komentarz przy $shopWeaponProtos) -
     * dlatego mają osobne skale i osobne capy crit_chance/magic_burst_chance (sklep:
     * 40/60, craft: 50/70). Odtwarza: Character::syncMissingPoints (pula punktów),
     * split 40/30/30, Character::getEquipmentStats (suma statów z eq),
     * Character::getAttributeAttackBonus, Character::getMaxHp, oraz bazowe dane do
     * EncounterService::calculateDamage.
     */
    private function buildArchetype(
        string $weaponType,
        int $level,
        array $weaponProto,
        float $weaponScale,
        int $weaponTierIndex,
        float $weaponCritCap,
        float $weaponBurstCap,
        float $armorScale,
        int $armorTierIndex
    ): array {
        // Suma statystyk ekwipunku (waga + zbroja + biżuteria) - jak Character::getEquipmentStats()
        $eq = [
            'attack_min' => 0, 'attack_max' => 0,
            'magic_attack_min' => 0, 'magic_attack_max' => 0,
            'defense' => 0, 'hp_bonus' => 0, 'mana_bonus' => 0,
            'crit_chance' => 0, 'magic_burst_chance' => 0,
            'magic_burst_min' => 0, 'magic_burst_max' => 0,
        ];

        foreach ($weaponProto as $stat => $base) {
            $eq[$stat] += $this->scaleStat($stat, $base, $weaponScale, $weaponTierIndex, $weaponCritCap, $weaponBurstCap);
        }
        foreach (['armor', 'helmet', 'boots', 'amulet', 'ring'] as $piece) {
            foreach ($this->armorProtos[$piece] as $stat => $base) {
                // Zbroja/biżuteria sklepowa zawsze capuje crit_chance na 40 (ShopEquipmentSeeder).
                $eq[$stat] += $this->scaleStat($stat, $base, $armorScale, $armorTierIndex, 40, 60);
            }
        }

        $attrPoints = 10 + max(0, ($level - 1) * 3);
        if ($weaponType === 'wand') {
            $int = (int) round($attrPoints * 0.4);
            $agi = (int) round($attrPoints * 0.3);
            $str = 0;
        } else {
            $str = (int) round($attrPoints * 0.4);
            $agi = (int) round($attrPoints * 0.3);
            $int = 0;
        }
        $vit = max(0, $attrPoints - $str - $agi - $int);

        $rawStatBonus = match ($weaponType) {
            'bow', 'sword', 'dagger' => $str + $agi,
            'bell' => $str + $int,
            'wand' => $int * 2,
            'axe' => $str * 2,
            default => $str * 2,
        };
        $statBonus = (int) round($rawStatBonus * self::ATTRIBUTE_DAMAGE_MULTIPLIER);

        $weaponAtkMin = $eq['attack_min'] + $eq['magic_attack_min'];
        $weaponAtkMax = $eq['attack_max'] + $eq['magic_attack_max'];

        $baseFlat = 10 + $statBonus + $level; // "10 + statBonus + level" część wspólna wzoru
        $baseDamageMin = $baseFlat + $weaponAtkMin;
        $baseDamageMax = max($baseDamageMin, $baseFlat + $weaponAtkMax);

        $maxHp = 100 + $vit * self::ATTRIBUTE_HP_MULTIPLIER + $level * 5 + $eq['hp_bonus'];
        $playerDefBase = $vit + ($level / 2) + $eq['defense'];

        return [
            'weaponType' => $weaponType,
            'str' => $str, 'int' => $int, 'agi' => $agi, 'vit' => $vit,
            'baseDamageMin' => $baseDamageMin,
            'baseDamageMax' => $baseDamageMax,
            'weaponAtkMin' => $weaponAtkMin,
            'weaponAtkMax' => $weaponAtkMax,
            'baseFlat' => $baseFlat,
            'maxHp' => (int) round($maxHp),
            'playerDefBase' => $playerDefBase,
            'critGearPct' => $eq['crit_chance'],
            'magicBurstChance' => $eq['magic_burst_chance'],
            'magicBurstMin' => $eq['magic_burst_min'],
            'magicBurstMax' => $eq['magic_burst_max'],
        ];
    }

    private function scaleStat(string $stat, float $base, float $scale, int $tierIndex, float $critCap, float $burstCap): float
    {
        if ($stat === 'crit_chance') {
            return min($critCap, $base + $tierIndex * 2);
        }
        if ($stat === 'magic_burst_chance') {
            return min($burstCap, $base + $tierIndex * 2);
        }
        return $base * $scale;
    }

    /** Symuluje JEDNĄ walkę (bez skilli/pasywek - "goła" postać referencyjna). */
    private function simulateFight(array $a, array $monster): array
    {
        $playerHp = $a['maxHp'];
        $monsterHp = $monster['hp'];
        $playerAgi = $a['agi'];
        $monsterAgi = $monster['agi'];
        $playerFirst = $playerAgi >= $monsterAgi;

        $landedHits = 0;
        $dmgTaken = 0;
        $turn = 0;

        while ($playerHp > 0 && $monsterHp > 0 && $turn < 50) {
            $isPlayerTurn = $playerFirst ? ($turn % 2 === 0) : ($turn % 2 === 1);

            if ($isPlayerTurn) {
                $roll = mt_rand((int) round($a['baseDamageMin'] * 100), (int) round($a['baseDamageMax'] * 100)) / 100;
                $baseDmg = max(1, $roll - $monster['def'] * 0.2);

                $magicBurstDmg = 0;
                if ($a['magicBurstChance'] > 0 && (mt_rand(1, 10000) / 100) <= $a['magicBurstChance']) {
                    $burstRoll = $a['magicBurstMax'] > $a['magicBurstMin']
                        ? mt_rand((int) round($a['magicBurstMin'] * 100), (int) round($a['magicBurstMax'] * 100)) / 100
                        : $a['magicBurstMin'];
                    $magicBurstDmg = max(1, round($burstRoll - $monster['def'] * 0.2));
                }

                $totalDmg = $baseDmg + $magicBurstDmg;

                $critChance = max(0.03, (0.05 + $playerAgi * 0.004 + $a['critGearPct'] / 100) - max(0, ($monsterAgi - $playerAgi) * 0.0008));
                $isCrit = (mt_rand(1, 1000000) / 1000000) < $critChance;

                $missChance = 0.03 + max(0, ($monsterAgi - $playerAgi)) * 0.0015;
                $isMiss = (mt_rand(1, 1000000) / 1000000) < $missChance;

                if ($isMiss) {
                    $dmgDealt = 0;
                } else {
                    if ($isCrit) {
                        $totalDmg = (int) ($totalDmg * 1.5);
                    }
                    $dmgDealt = (int) $totalDmg;
                    $landedHits++;
                }

                $monsterHp = max(0, $monsterHp - $dmgDealt);
            } else {
                $rawDmg = $monster['atk'];
                $dmg = max(1, $rawDmg - $a['playerDefBase'] * 0.2);

                $monsterCrit = max(0.02, min(0.30, 0.03 + $monsterAgi * 0.003 - max(0, ($playerAgi - $monsterAgi) * 0.0008)));
                $isCrit = (mt_rand(1, 1000000) / 1000000) < $monsterCrit;

                $monsterMiss = 0.03 + max(0, ($playerAgi - $monsterAgi)) * 0.0015;
                $isMiss = (mt_rand(1, 1000000) / 1000000) < $monsterMiss;

                if ($isMiss) {
                    $dmgDealt = 0;
                } else {
                    if ($isCrit) {
                        $dmg = (int) ($dmg * 1.5);
                    }
                    $dmgDealt = (int) $dmg;
                }

                $playerHp = max(0, $playerHp - $dmgDealt);
                $dmgTaken += $dmgDealt;
            }

            $turn++;
        }

        return [
            'win' => $monsterHp <= 0 && $playerHp > 0,
            'hits' => $landedHits,
            'dmgTaken' => $dmgTaken,
            'maxHp' => $a['maxHp'],
        ];
    }

    private function monteCarlo(array $a, array $monster, int $n): array
    {
        $wins = 0;
        $hitsSum = 0;
        $dmgFractionSum = 0;

        for ($i = 0; $i < $n; $i++) {
            $r = $this->simulateFight($a, $monster);
            if ($r['win']) {
                $wins++;
            }
            $hitsSum += $r['hits'];
            $dmgFractionSum += $r['dmgTaken'] / max(1, $r['maxHp']);
        }

        return [
            'winRate' => $wins / $n,
            'avgHits' => $hitsSum / $n,
            'avgDmgFraction' => $dmgFractionSum / $n,
        ];
    }

    /**
     * Dobiera HP/ATK/DEF/AGI potwora tak, by ŚREDNIA po 6 archetypach broni trafiała
     * w cele (winrate/hity/frakcja HP). AGI potwora jest ustalone heurystycznie
     * (0.75x średniego AGI gracza), żeby bohater zawsze atakował pierwszy i uniki/
     * krytyki nie wprowadzały nadmiernej wariancji ponad to, co wynika z samych celów.
     */
    private function solveMonster(
        array $archetypes,
        int $iterations,
        int $tuneSims,
        int $verifySims,
        float $targetWinrate = self::TARGET_WINRATE,
        float $targetHitsMid = self::TARGET_HITS_MID,
        float $targetDmgFraction = self::TARGET_DMG_TAKEN_FRACTION
    ): array {
        $avgPlayerAgi = array_sum(array_column($archetypes, 'agi')) / count($archetypes);
        $avgRawDmg = array_sum(array_map(fn ($a) => ($a['baseDamageMin'] + $a['baseDamageMax']) / 2, $archetypes)) / count($archetypes);
        $avgMaxHp = array_sum(array_column($archetypes, 'maxHp')) / count($archetypes);
        $avgPlayerDef = array_sum(array_column($archetypes, 'playerDefBase')) / count($archetypes);

        $monster = [
            'agi' => max(1, (int) round($avgPlayerAgi * 0.75)),
            'def' => max(0, (int) round($avgRawDmg * 0.15)),
        ];
        $monster['hp'] = max(10, (int) round(max(1, $avgRawDmg - $monster['def'] * 0.2) * $targetHitsMid));

        $perHitDmgTarget = ($targetDmgFraction * $avgMaxHp) / $targetHitsMid;
        $monster['atk'] = max(1, (int) round($perHitDmgTarget + $avgPlayerDef * 0.2));

        for ($iter = 0; $iter < $iterations; $iter++) {
            $agg = ['winRate' => 0, 'avgHits' => 0, 'avgDmgFraction' => 0];
            foreach ($archetypes as $a) {
                $r = $this->monteCarlo($a, $monster, $tuneSims);
                $agg['winRate'] += $r['winRate'];
                $agg['avgHits'] += $r['avgHits'];
                $agg['avgDmgFraction'] += $r['avgDmgFraction'];
            }
            $n = count($archetypes);
            $agg['winRate'] /= $n;
            $agg['avgHits'] /= $n;
            $agg['avgDmgFraction'] /= $n;

            $hitsRatio = $targetHitsMid / max(0.5, $agg['avgHits']);
            $monster['hp'] = max(5, (int) round($monster['hp'] * pow($hitsRatio, 0.6)));

            $dmgRatio = $targetDmgFraction / max(0.05, $agg['avgDmgFraction']);
            $monster['atk'] = max(1, (int) round($monster['atk'] * pow($dmgRatio, 0.6)));

            if ($agg['winRate'] < $targetWinrate) {
                // Zbyt trudno - osłabiamy lekko obie strony ryzyka (ATK bardziej niż HP,
                // bo to głównie ryzyko śmierci gracza psuje winrate przy krótkich walkach).
                $monster['atk'] = max(1, (int) round($monster['atk'] * 0.96));
                $monster['hp'] = max(5, (int) round($monster['hp'] * 0.99));
            }
        }

        // Finalna weryfikacja z dużą liczbą symulacji, per archetyp.
        $verification = [];
        foreach ($archetypes as $weaponType => $a) {
            $verification[$weaponType] = $this->monteCarlo($a, $monster, $verifySims);
        }

        return [$monster, $verification];
    }

    private function currentEffectiveAverage(array $map): array
    {
        $rows = $this->currentRegularStatsRaw[$map['name']];
        $multiplier = $map['mapCount'] >= 3 ? 1.35 : 1.0;

        $sum = ['hp' => 0, 'atk' => 0, 'def' => 0, 'agi' => 0];
        foreach ($rows as $row) {
            $sum['hp'] += (int) round($row['hp'] * $multiplier);
            $sum['atk'] += (int) round($row['atk'] * $multiplier);
            $sum['def'] += (int) round($row['def'] * $multiplier);
            $sum['agi'] += (int) round($row['agi'] * $multiplier);
        }
        $n = count($rows);

        return [
            'hp' => (int) round($sum['hp'] / $n),
            'atk' => (int) round($sum['atk'] / $n),
            'def' => (int) round($sum['def'] / $n),
            'agi' => (int) round($sum['agi'] / $n),
        ];
    }

    /** Jak currentEffectiveAverage(), ale dla dowolnej rangi z $allMonstersRaw (np. 'boss' - zwykle 1 wpis na mapę). */
    private function currentEffectiveForRank(array $map, string $rank): array
    {
        $rows = array_values(array_filter(
            $this->allMonstersRaw[$map['name']],
            fn ($m) => $m['rank'] === $rank
        ));
        $multiplier = $map['mapCount'] >= 3 ? 1.35 : 1.0;

        $sum = ['hp' => 0, 'atk' => 0, 'def' => 0, 'agi' => 0];
        foreach ($rows as $row) {
            $sum['hp'] += (int) round($row['stats']['hp'] * $multiplier);
            $sum['atk'] += (int) round($row['stats']['atk'] * $multiplier);
            $sum['def'] += (int) round($row['stats']['def'] * $multiplier);
            $sum['agi'] += (int) round($row['stats']['agi'] * $multiplier);
        }
        $n = max(1, count($rows));

        return [
            'hp' => (int) round($sum['hp'] / $n),
            'atk' => (int) round($sum['atk'] / $n),
            'def' => (int) round($sum['def'] / $n),
            'agi' => (int) round($sum['agi'] / $n),
        ];
    }
}
