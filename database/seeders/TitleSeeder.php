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
                'prefix' => '[Łowca]',
                'description' => 'Tytuł przyznawany za uśmiercenie setek potworów przemierzających krainy.',
                'stats_bonus' => ['atk' => 5, 'hp' => 25],
            ],
            [
                'name' => 'Pogromca Potworów',
                'prefix' => '[Pogromca]',
                'description' => 'Przydzielany nielicznym wojownikom, którzy zmasakrowali tysiące potworów.',
                'stats_bonus' => ['atk' => 15, 'hp' => 100],
            ],
            [
                'name' => 'Zabójca Bossów',
                'prefix' => '[Pogromca Bossów]',
                'description' => 'Wyróżnienie za zgładzenie najpotężniejszych World Bossów w krainach.',
                'stats_bonus' => ['crit' => 0.03, 'atk' => 20],
            ],
            [
                'name' => 'Zagłada Bossów',
                'prefix' => '[Zagłada]',
                'description' => 'Legendarny tytuł dla niepowstrzymanego bohatera, który rzucił bossów na kolana.',
                'stats_bonus' => ['crit' => 0.05, 'atk' => 50, 'hp' => 250],
            ],
            [
                'name' => 'Egzorcysta',
                'prefix' => '[Egzorcysta]',
                'description' => 'Nadawany śmiałkom odsyłającym potwory zza grobu z powrotem w zaświaty.',
                'stats_bonus' => ['def' => 10, 'hp' => 50],
            ],
            [
                'name' => 'Świetlisty Strażnik',
                'prefix' => '[Świetlisty]',
                'description' => 'Tytuł dla obrońców jasności zmagających się z mrocznymi legionami nieumarłych.',
                'stats_bonus' => ['def' => 25, 'hp' => 150, 'int' => 10],
            ],
            [
                'name' => 'Władca Bestii',
                'prefix' => '[Władca Bestii]',
                'description' => 'Przyznawany za polowanie na najdziksze potwory i zwierzęta w kniejach.',
                'stats_bonus' => ['agi' => 10, 'dodge' => 0.02],
            ],
            [
                'name' => 'Niszczyciel Orków',
                'prefix' => '[Niszczyciel]',
                'description' => 'Dla wojownika, który wybił orcze i trollowe plemiona z pustkowi.',
                'stats_bonus' => ['str' => 12, 'atk' => 15],
            ],
            [
                'name' => 'Łowca Demonów',
                'prefix' => '[Łowca Demonów]',
                'description' => 'Świadectwo odwagi w starciu ze stworami wyłaniającymi się z czeluści piekielnych.',
                'stats_bonus' => ['str' => 15, 'int' => 15],
            ],
            [
                'name' => 'Pogromca Smoków',
                'prefix' => '[Smokobójca]',
                'description' => 'Legendarny tytuł przyznawany jedynie nieustraszonym pogromcom smoczego pomiotu.',
                'stats_bonus' => ['atk' => 40, 'crit' => 0.05, 'hp' => 200],
            ],
            [
                'name' => 'Kolekcjoner',
                'prefix' => '[Kolekcjoner]',
                'description' => 'Dla wędrowca o niezaspokojonej ciekawości i bogatym ekwipunku.',
                'stats_bonus' => ['hp' => 50],
            ],
            [
                'name' => 'Poszukiwacz Skarbów',
                'prefix' => '[Poszukiwacz]',
                'description' => 'Dla wytrawnego zbieracza unikalnych reliktów i rzadkich artefaktów.',
                'stats_bonus' => ['hp' => 100, 'agi' => 5],
            ],
            [
                'name' => 'Obrońca Mrocznego Lasu',
                'prefix' => '[Obrońca Lasu]',
                'description' => 'Przyznawany za zabezpieczenie pierwszej kniei przed inwazją potworów.',
                'stats_bonus' => ['def' => 5, 'hp' => 30],
            ],
            [
                'name' => 'Mistrz Arkanów',
                'prefix' => '[Czarnoksiężnik]',
                'description' => 'Dla czarodzieja, który rzucił wyzwanie adeptom i magom w tajemniczej wieży.',
                'stats_bonus' => ['int' => 15, 'hp' => 50],
            ],
            [
                'name' => 'Oswobodziciel Skażonego Miasta',
                'prefix' => '[Oswobodziciel]',
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
                ]
            );
        }

        $this->command->info('Title seeder completed - created ' . count($titles) . ' titles.');
    }
}
