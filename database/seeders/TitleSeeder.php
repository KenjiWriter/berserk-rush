<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\Title;

class TitleSeeder extends Seeder
{
    public function run(): void
    {
        $titles = [
            [
                'name' => 'Łowca Potworów',
                'prefix' => 'Łowca',
                'description' => 'Tytuł przyznawany za uśmiercenie setek potworów przemierzających krainy.',
                'stats_bonus' => ['atk' => 5, 'hp' => 25],
            ],
            [
                'name' => 'Pogromca Potworów',
                'prefix' => 'Pogromca',
                'description' => 'Przydzielany nielicznym wojownikom, którzy zmasakrowali tysiące potworów.',
                'stats_bonus' => ['atk' => 15, 'hp' => 100],
            ],
            [
                'name' => 'Zabójca Bossów',
                'prefix' => 'Pogromca Bossów',
                'description' => 'Wyróżnienie za zgładzenie najpotężniejszych World Bossów w krainach.',
                'stats_bonus' => ['crit' => 0.03, 'atk' => 20],
            ],
            [
                'name' => 'Zagłada Bossów',
                'prefix' => 'Zagłada',
                'description' => 'Legendarny tytuł dla niepowstrzymanego bohatera, który rzucił bossów na kolana.',
                'stats_bonus' => ['crit' => 0.05, 'atk' => 50, 'hp' => 250],
            ],
            [
                'name' => 'Egzorcysta',
                'prefix' => 'Egzorcysta',
                'description' => 'Nadawany śmiałkom odsyłającym potwory zza grobu z powrotem w zaświaty.',
                'stats_bonus' => ['def' => 10, 'hp' => 50],
            ],
            [
                'name' => 'Świetlisty Strażnik',
                'prefix' => 'Świetlisty',
                'description' => 'Tytuł dla obrońców jasności zmagających się z mrocznymi legionami nieumarłych.',
                'stats_bonus' => ['def' => 25, 'hp' => 150, 'int' => 10],
            ],
            [
                'name' => 'Władca Bestii',
                'prefix' => 'Władca Bestii',
                'description' => 'Przyznawany za polowanie na najdziksze potwory i zwierzęta w kniejach.',
                'stats_bonus' => ['agi' => 10, 'dodge' => 0.02],
            ],
            [
                'name' => 'Niszczyciel Orków',
                'prefix' => 'Niszczyciel',
                'description' => 'Dla wojownika, który wybił orcze i trollowe plemiona z pustkowi.',
                'stats_bonus' => ['str' => 12, 'atk' => 15],
            ],
            [
                'name' => 'Łowca Demonów',
                'prefix' => 'Łowca Demonów',
                'description' => 'Świadectwo odwagi w starciu ze stworami wyłaniającymi się z czeluści piekielnych.',
                'stats_bonus' => ['str' => 15, 'int' => 15],
            ],
            [
                'name' => 'Pogromca Smoków',
                'prefix' => 'Smokobójca',
                'description' => 'Legendarny tytuł przyznawany jedynie nieustraszonym pogromcom smoczego pomiotu.',
                'stats_bonus' => ['atk' => 40, 'crit' => 0.05, 'hp' => 200],
            ],
            [
                'name' => 'Kolekcjoner',
                'prefix' => 'Kolekcjoner',
                'description' => 'Dla wędrowca o niezaspokojonej ciekawości i bogatym ekwipunku.',
                'stats_bonus' => ['hp' => 50],
            ],
            [
                'name' => 'Poszukiwacz Skarbów',
                'prefix' => 'Poszukiwacz',
                'description' => 'Dla wytrawnego zbieracza unikalnych reliktów i rzadkich artefaktów.',
                'stats_bonus' => ['hp' => 100, 'agi' => 5],
            ],
            [
                'name' => 'Obrońca Mrocznego Lasu',
                'prefix' => 'Obrońca Lasu',
                'description' => 'Przyznawany za zabezpieczenie pierwszej kniei przed inwazją potworów.',
                'stats_bonus' => ['def' => 5, 'hp' => 30],
            ],
            [
                'name' => 'Mistrz Arkanów',
                'prefix' => 'Czarnoksiężnik',
                'description' => 'Dla czarodzieja, który rzucił wyzwanie adeptom i magom w tajemniczej wieży.',
                'stats_bonus' => ['int' => 15, 'hp' => 50],
            ],
            [
                'name' => 'Oswobodziciel Skażonego Miasta',
                'prefix' => 'Oswobodziciel',
                'description' => 'Zwieńczenie walki z najwyższym stopniem zagrożenia w zrujnowanej metropolii.',
                'stats_bonus' => ['atk' => 25, 'def' => 15],
            ],
        ];

        foreach ($titles as $titleData) {
            Title::updateOrCreate(
                ['name' => $titleData['name']],
                [
                    'prefix' => $titleData['prefix'],
                    'description' => $titleData['description'],
                    'stats_bonus' => $titleData['stats_bonus'],
                    'is_temporary' => $titleData['is_temporary'] ?? false,
                ]
            );
        }

        $weeklyTitles = [
            // Pokonane Potwory
            ['name' => 'Top 1 Łowca', 'prefix' => 'Top 1 Łowca', 'description' => 'Tytuł tygodniowy za 1. miejsce w liczbie pokonanych potworów.', 'stats_bonus' => ['strong_vs_monsters' => 5, 'attack' => 20], 'is_temporary' => true],
            ['name' => 'Top 2 Łowca', 'prefix' => 'Top 2 Łowca', 'description' => 'Tytuł tygodniowy za 2. miejsce w liczbie pokonanych potworów.', 'stats_bonus' => ['strong_vs_monsters' => 3, 'attack' => 12], 'is_temporary' => true],
            ['name' => 'Top 3 Łowca', 'prefix' => 'Top 3 Łowca', 'description' => 'Tytuł tygodniowy za 3. miejsce w liczbie pokonanych potworów.', 'stats_bonus' => ['strong_vs_monsters' => 1, 'attack' => 6], 'is_temporary' => true],

            // DMG World Bossa
            ['name' => 'Top 1 Pogromca Bossów', 'prefix' => 'Top 1 Pogromca Bossów', 'description' => 'Tytuł tygodniowy za 1. miejsce w obrażeniach World Bossa.', 'stats_bonus' => ['strong_vs_bosses' => 5, 'crit_chance' => 2], 'is_temporary' => true],
            ['name' => 'Top 2 Pogromca Bossów', 'prefix' => 'Top 2 Pogromca Bossów', 'description' => 'Tytuł tygodniowy za 2. miejsce w obrażeniach World Bossa.', 'stats_bonus' => ['strong_vs_bosses' => 3, 'crit_chance' => 1], 'is_temporary' => true],
            ['name' => 'Top 3 Pogromca Bossów', 'prefix' => 'Top 3 Pogromca Bossów', 'description' => 'Tytuł tygodniowy za 3. miejsce w obrażeniach World Bossa.', 'stats_bonus' => ['strong_vs_bosses' => 1, 'crit_chance' => 0.5], 'is_temporary' => true],

            // Ukończone Lochy
            ['name' => 'Top 1 Zdobywca Lochów', 'prefix' => 'Top 1 Zdobywca Lochów', 'description' => 'Tytuł tygodniowy za 1. miejsce w liczbie ukończonych lochów.', 'stats_bonus' => ['armor_pen_pct' => 5, 'defense' => 15], 'is_temporary' => true],
            ['name' => 'Top 2 Zdobywca Lochów', 'prefix' => 'Top 2 Zdobywca Lochów', 'description' => 'Tytuł tygodniowy za 2. miejsce w liczbie ukończonych lochów.', 'stats_bonus' => ['armor_pen_pct' => 3, 'defense' => 10], 'is_temporary' => true],
            ['name' => 'Top 3 Zdobywca Lochów', 'prefix' => 'Top 3 Zdobywca Lochów', 'description' => 'Tytuł tygodniowy za 3. miejsce w liczbie ukończonych lochów.', 'stats_bonus' => ['armor_pen_pct' => 1, 'defense' => 5], 'is_temporary' => true],

            // Wbite Poziomy
            ['name' => 'Top 1 Mistrz Doświadczenia', 'prefix' => 'Top 1 Mistrz Doświadczenia', 'description' => 'Tytuł tygodniowy za 1. miejsce w wbitych poziomach.', 'stats_bonus' => ['exp_bonus' => 5, 'all_attributes' => 10], 'is_temporary' => true],
            ['name' => 'Top 2 Mistrz Doświadczenia', 'prefix' => 'Top 2 Mistrz Doświadczenia', 'description' => 'Tytuł tygodniowy za 2. miejsce w wbitych poziomach.', 'stats_bonus' => ['exp_bonus' => 3, 'all_attributes' => 6], 'is_temporary' => true],
            ['name' => 'Top 3 Mistrz Doświadczenia', 'prefix' => 'Top 3 Mistrz Doświadczenia', 'description' => 'Tytuł tygodniowy za 3. miejsce w wbitych poziomach.', 'stats_bonus' => ['exp_bonus' => 1, 'all_attributes' => 3], 'is_temporary' => true],

            // Bossowie na Mapach
            ['name' => 'Top 1 Łowca Czempionów', 'prefix' => 'Top 1 Łowca Czempionów', 'description' => 'Tytuł tygodniowy za 1. miejsce w pokonanych bossach map.', 'stats_bonus' => ['double_drop_chance' => 5, 'attack' => 15], 'is_temporary' => true],
            ['name' => 'Top 2 Łowca Czempionów', 'prefix' => 'Top 2 Łowca Czempionów', 'description' => 'Tytuł tygodniowy za 2. miejsce w pokonanych bossach map.', 'stats_bonus' => ['double_drop_chance' => 3, 'attack' => 10], 'is_temporary' => true],
            ['name' => 'Top 3 Łowca Czempionów', 'prefix' => 'Top 3 Łowca Czempionów', 'description' => 'Tytuł tygodniowy za 3. miejsce w pokonanych bossach map.', 'stats_bonus' => ['double_drop_chance' => 1, 'attack' => 5], 'is_temporary' => true],

            // Zwycięstwa na Arenie
            ['name' => 'Top 1 Gladiator', 'prefix' => 'Top 1 Gladiator', 'description' => 'Tytuł tygodniowy za 1. miejsce w wygranych na Arenie.', 'stats_bonus' => ['strong_vs_hero' => 5, 'dodge_chance' => 2], 'is_temporary' => true],
            ['name' => 'Top 2 Gladiator', 'prefix' => 'Top 2 Gladiator', 'description' => 'Tytuł tygodniowy za 2. miejsce w wygranych na Arenie.', 'stats_bonus' => ['strong_vs_hero' => 3, 'dodge_chance' => 1], 'is_temporary' => true],
            ['name' => 'Top 3 Gladiator', 'prefix' => 'Top 3 Gladiator', 'description' => 'Tytuł tygodniowy za 3. miejsce w wygranych na Arenie.', 'stats_bonus' => ['strong_vs_hero' => 1, 'dodge_chance' => 0.5], 'is_temporary' => true],
        ];

        foreach ($weeklyTitles as $wtData) {
            Title::updateOrCreate(
                ['name' => $wtData['name']],
                [
                    'prefix' => $wtData['prefix'],
                    'description' => $wtData['description'],
                    'stats_bonus' => $wtData['stats_bonus'],
                    'is_temporary' => true,
                ]
            );
        }

        // Strip any existing brackets from existing titles in DB
        \Illuminate\Support\Facades\DB::statement("UPDATE titles SET prefix = REPLACE(REPLACE(prefix, '[', ''), ']', '')");

        $this->command->info('Title seeder completed - created ' . count($titles) . ' titles.');
    }
}
