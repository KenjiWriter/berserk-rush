<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Str;

class ItemTemplateSeeder extends Seeder
{
    public function run(): void
    {
        \App\Infrastructure\Persistence\ItemRecipe::query()->delete();
        \App\Infrastructure\Persistence\MerchantItem::query()->delete();
        ItemTemplate::query()->delete();


        $manualItems = [
            // Consumables
            ['id' => Str::ulid(), 'name' => 'Mikstura Leczenia', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 1, 'base_stats' => ['heal_amount' => 25], 'description' => 'Czerwona mikstura.', 'icon' => 'bagienne-ziolo.png', 'rarity_weights' => ['common' => 80, 'uncommon' => 15, 'rare' => 5]],
            ['id' => Str::ulid(), 'name' => 'Eliksir Many', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 2, 'base_stats' => ['mana_amount' => 30], 'description' => 'Niebieska mikstura.', 'icon' => 'czysta-mana.png', 'rarity_weights' => ['common' => 75, 'uncommon' => 20, 'rare' => 5]],
            ['id' => Str::ulid(), 'name' => 'Wielka Mikstura Leczenia', 'type' => 'consumable', 'slot' => null, 'level_requirement' => 5, 'base_stats' => ['heal_amount' => 150], 'description' => 'Potężna mikstura.', 'icon' => 'metna-woda.png', 'rarity_weights' => ['common' => 60, 'uncommon' => 30, 'rare' => 10]],

            // Materials

            
            // Keys & Tutorial / Starter Equipment
            ['id' => '01k4jpx94j70x2vv10b835key1', 'name' => 'Zardzewiały Klucz do Lochów', 'type' => 'material', 'slot' => null, 'level_requirement' => 8, 'base_stats' => [], 'description' => 'Tajemniczy stary klucz.', 'icon' => 'zardzewialy-klucz-do-lochow.png', 'rarity_weights' => ['common' => 0, 'uncommon' => 70, 'rare' => 30]],
            ['id' => '01k4jpx94j70x2vv10b835prm4', 'name' => 'Zardzewiały Miecz', 'type' => 'weapon', 'slot' => 'main_hand', 'level_requirement' => 1, 'base_stats' => ['attack_min' => 2, 'attack_max' => 4, 'str_bonus' => 1], 'description' => 'Podstawowa broń.', 'icon' => 'zardzewialy-miecz.png', 'rarity_weights' => ['common' => 100]],
            ['id' => '01k4jpx94j70x2vv10b835nov1', 'name' => 'Miecz Nowicjusza', 'type' => 'weapon', 'slot' => 'main_hand', 'level_requirement' => 1, 'base_stats' => ['attack_min' => 3, 'attack_max' => 6, 'str_bonus' => 2], 'description' => 'Solidny miecz treningowy dla nowicjuszy.', 'icon' => 'miecz-nowicjusza.png', 'rarity_weights' => ['common' => 100]],
            ['id' => '01k4jpx94j70x2vv10b835hlm1', 'name' => 'Zardzewiały Hełm', 'type' => 'armor', 'slot' => 'head', 'level_requirement' => 1, 'base_stats' => ['defense' => 2, 'hp_bonus' => 8, 'vit_bonus' => 1], 'description' => 'Podstawowy hełm ochronny podarowany przez Kapitana.', 'icon' => 'helm-rekruta.png', 'rarity_weights' => ['common' => 100]],
            ['id' => '01k4jpx94j70x2vv10b835arm1', 'name' => 'Skórzana Zbroja', 'type' => 'armor', 'slot' => 'chest', 'level_requirement' => 1, 'base_stats' => ['defense' => 4, 'hp_bonus' => 15, 'str_bonus' => 1], 'description' => 'Solidna zbroja podarowana przez Kapitana na koniec wstępnego treningu.', 'icon' => 'zbroja-rekruta.png', 'rarity_weights' => ['common' => 100]],
        ];


        foreach ($manualItems as $item) {
            ItemTemplate::create($item);
        }

        $prototypes = [
            'sword'    => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['attack_min' => 2, 'attack_max' => 5, 'str_bonus' => 2]],
            'axe'      => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1, 'attack_max' => 8, 'str_bonus' => 3]],
            'bow'      => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['attack_min' => 2, 'attack_max' => 6, 'agi_bonus' => 2]],
            'bell'     => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['magic_attack_min' => 3, 'magic_attack_max' => 6, 'int_bonus' => 2, 'mana_bonus' => 10]],
            'wand'     => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['magic_attack_min' => 4, 'magic_attack_max' => 8, 'int_bonus' => 3]],
            'dagger'   => ['type' => 'weapon', 'slot' => 'main_hand', 'stats' => ['attack_min' => 1, 'attack_max' => 4, 'agi_bonus' => 2, 'crit_chance' => 5]],
            
            'helmet_w' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 3, 'hp_bonus' => 10, 'vit_bonus' => 1, 'str_bonus' => 1]],
            'armor_w'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 5, 'hp_bonus' => 20, 'vit_bonus' => 2, 'str_bonus' => 1]],
            'boots_w'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 2, 'hp_bonus' => 5, 'vit_bonus' => 1, 'str_bonus' => 1]],
            
            'helmet_m' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 1, 'mana_bonus' => 15, 'int_bonus' => 2]],
            'armor_m'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 2, 'mana_bonus' => 30, 'int_bonus' => 3]],
            'boots_m'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 1, 'mana_bonus' => 10, 'int_bonus' => 1]],
            
            'helmet_a' => ['type' => 'armor', 'slot' => 'head', 'stats' => ['defense' => 2, 'agi_bonus' => 2, 'crit_chance' => 2]],
            'armor_a'  => ['type' => 'armor', 'slot' => 'chest', 'stats' => ['defense' => 3, 'agi_bonus' => 3, 'crit_chance' => 3]],
            'boots_a'  => ['type' => 'armor', 'slot' => 'feet', 'stats' => ['defense' => 2, 'agi_bonus' => 2, 'crit_chance' => 2]],
            
            'amulet'   => ['type' => 'accessory', 'slot' => 'neck', 'stats' => ['hp_bonus' => 25, 'vit_bonus' => 2]],
            'ring'     => ['type' => 'accessory', 'slot' => 'ring', 'stats' => ['str_bonus' => 3, 'agi_bonus' => 1, 'int_bonus' => 1]],
        ];

        $themes = [
            [
                'level' => 5, 'scale' => 2,
                'names' => [
                    'sword' => 'Miecz Leśnego Goblina', 'axe' => 'Topór Drwala z Mrocznego Lasu', 'bow' => 'Łuk z Pnia Suchodrzewu',
                    'bell' => 'Dzwon Leśnego Szamana', 'wand' => 'Różdżka z Cisa', 'dagger' => 'Sztylety z Kości Wilka',
                    'helmet_w' => 'Hełm Leśnego Strażnika', 'armor_w' => 'Pancerz z Wilczej Skóry', 'boots_w' => 'Buty Tropiącego',
                    'helmet_m' => 'Kaptur Spleciony z Mchu', 'armor_m' => 'Szata Leśnego Ducha', 'boots_m' => 'Miękkie Mokasyny',
                    'helmet_a' => 'Maska z Czaszki Wilka', 'armor_a' => 'Skórznia Nocnego Łowcy', 'boots_a' => 'Ciche Podeszwy',
                    'amulet' => 'Naszyjnik z Kłów', 'ring' => 'Pierścień Wędrowca'
                ]
            ],
            [
                'level' => 15, 'scale' => 6,
                'names' => [
                    'sword' => 'Ostrze Króla Lasu', 'axe' => 'Ciężki Topór Enta', 'bow' => 'Łuk Nocnego Myśliwego',
                    'bell' => 'Dzwon Prastarych Drzew', 'wand' => 'Kostur z Serca Suchodrzewu', 'dagger' => 'Zatrute Sztylety Goblina',
                    'helmet_w' => 'Wzmocniony Hełm Strażnika', 'armor_w' => 'Zbroja z Twardej Kory', 'boots_w' => 'Okute Buty Leśnika',
                    'helmet_m' => 'Kaptur Krwawego Mchu', 'armor_m' => 'Szata Spaczonego Lasu', 'boots_m' => 'Trzewiki Korzeni',
                    'helmet_a' => 'Maska Półcienia', 'armor_a' => 'Płaszcz Liściastego Skrytobójcy', 'boots_a' => 'Buty Skoku',
                    'amulet' => 'Amulet Prastarego Dębu', 'ring' => 'Pierścień Splecionych Korzeni'
                ]
            ],
            [
                'level' => 25, 'scale' => 15,
                'names' => [
                    'sword' => 'Zardzewiały Miecz Szkieletu', 'axe' => 'Ząbkowany Topór Upiora', 'bow' => 'Łuk z Kości Zjaw',
                    'bell' => 'Dzwon Pokutny', 'wand' => 'Różdżka Potępionych Dusz', 'dagger' => 'Sztylety Skrytobójcy Dusz',
                    'helmet_w' => 'Zardzewiały Hełm Rycerza', 'armor_w' => 'Kolczuga Strażnika Ruin', 'boots_w' => 'Żelazne Sabatony',
                    'helmet_m' => 'Kaptur Zjaw', 'armor_m' => 'Zbutwiała Szata Licza', 'boots_m' => 'Buty Mgły',
                    'helmet_a' => 'Maska Beztwarzowego Ducha', 'armor_a' => 'Skórznia Z Grobowca', 'boots_a' => 'Cmentarne Buty',
                    'amulet' => 'Naszyjnik z Zimnej Stali', 'ring' => 'Pierścień Wiecznego Żalu'
                ]
            ],
            [
                'level' => 35, 'scale' => 35,
                'names' => [
                    'sword' => 'Maczuga Ogra', 'axe' => 'Rozłupywacz Czaszek', 'bow' => 'Łuk z Kości Jaskiniowca',
                    'bell' => 'Dzwon Szamana Trolli', 'wand' => 'Różdżka Ziemnej Magii', 'dagger' => 'Sztylety z Zębów Nietoperza',
                    'helmet_w' => 'Hełm z Czaszki Ogra', 'armor_w' => 'Gruboskórny Pancerz Trolla', 'boots_w' => 'Masywne Buciska',
                    'helmet_m' => 'Szamański Kaptur Trolli', 'armor_m' => 'Szata z Futer Nietoperzy', 'boots_m' => 'Buty z Mchu Jaskiniowego',
                    'helmet_a' => 'Maska Łowcy Ogrów', 'armor_a' => 'Płaszcz Skalnego Cienia', 'boots_a' => 'Buty Cichego Kroku',
                    'amulet' => 'Amulet Skalnego Trolla', 'ring' => 'Kamienny Pierścień'
                ]
            ],
            [
                'level' => 45, 'scale' => 80,
                'names' => [
                    'sword' => 'Glewia Wodza Orków', 'axe' => 'Topór Berserkera Orków', 'bow' => 'Łuk Krwawego Zwiadu',
                    'bell' => 'Dzwon Krwawego Rytuału', 'wand' => 'Kostur Szamana Krwi', 'dagger' => 'Sztylety Pustkowi',
                    'helmet_w' => 'Hełm Wodza Orków', 'armor_w' => 'Pancerz z Hartowanej Stali', 'boots_w' => 'Buty Orkowego Wojownika',
                    'helmet_m' => 'Kaptur Szamana Krwi', 'armor_m' => 'Szata Nasączona Krwią', 'boots_m' => 'Trzewiki Rytualne',
                    'helmet_a' => 'Maska Pustynnego Wiatru', 'armor_a' => 'Skórznia Orkowego Zabójcy', 'boots_a' => 'Buty Burzy Piaskowej',
                    'amulet' => 'Naszyjnik Orkowego Wodza', 'ring' => 'Pierścień Berserkera'
                ]
            ],
            [
                'level' => 55, 'scale' => 180,
                'names' => [
                    'sword' => 'Ostrze z Zęba Hydry', 'axe' => 'Zbutwiały Topór Topielca', 'bow' => 'Łuk z Wierzby Płaczącej',
                    'bell' => 'Dzwon Utopców', 'wand' => 'Różdżka Wiedźmiej Straży', 'dagger' => 'Zatrute Kły Hydry',
                    'helmet_w' => 'Zardzewiały Hełm z Głębin', 'armor_w' => 'Pancerz z Łusek Hydry', 'boots_w' => 'Mokre Buty Bagienne',
                    'helmet_m' => 'Kaptur Wiedźmy Zgnilizny', 'armor_m' => 'Szata Tkana z Zielska', 'boots_m' => 'Buty Bagiennej Mgły',
                    'helmet_a' => 'Maska z Błota', 'armor_a' => 'Skórznia Żmijowa', 'boots_a' => 'Podeszwy Bezdźwięku',
                    'amulet' => 'Naszyjnik z Oka Hydry', 'ring' => 'Pierścień Zgniłego Mchu'
                ]
            ],
            [
                'level' => 65, 'scale' => 400,
                'names' => [
                    'sword' => 'Miecz Wykuty z Bazaltu', 'axe' => 'Topór Kamiennego Golema', 'bow' => 'Łuk z Piór Harpii',
                    'bell' => 'Dzwon Górskiego Echa', 'wand' => 'Różdżka z Górskiego Kryształu', 'dagger' => 'Sztylety Skalnego Kła',
                    'helmet_w' => 'Hełm z Czarnego Bazaltu', 'armor_w' => 'Pancerz Skalnego Golema', 'boots_w' => 'Ciężkie Kamienne Buty',
                    'helmet_m' => 'Kaptur Burzowych Chmur', 'armor_m' => 'Szata z Piór Harpii', 'boots_m' => 'Trzewiki Górskiego Wiatru',
                    'helmet_a' => 'Maska Nocnego Drapieżnika', 'armor_a' => 'Płaszcz Górskiego Cienia', 'boots_a' => 'Buty Sokolnika',
                    'amulet' => 'Naszyjnik ze Szponu Harpii', 'ring' => 'Pierścień Czarnego Kryształu'
                ]
            ],
            [
                'level' => 75, 'scale' => 1000,
                'names' => [
                    'sword' => 'Piekielny Miecz Smoka', 'axe' => 'Topór Smoczego Gniewu', 'bow' => 'Smoczy Łuk',
                    'bell' => 'Dzwon Oddechu Smoka', 'wand' => 'Różdżka Smoczej Łuski', 'dagger' => 'Sztylety z Cienia Smoka',
                    'helmet_w' => 'Hełm Smoczej Straży', 'armor_w' => 'Pancerz ze Smoczych Łusek', 'boots_w' => 'Sabatony Smoka',
                    'helmet_m' => 'Kaptur Cienia Smoka', 'armor_m' => 'Szata Smoczego Ognia', 'boots_m' => 'Buty z Popiołu',
                    'helmet_a' => 'Maska Mrocznego Zabójcy', 'armor_a' => 'Skórznia Łowcy Smoków', 'boots_a' => 'Podeszwy Smoczego Lotu',
                    'amulet' => 'Amulet Smoczego Oka', 'ring' => 'Pierścień Władcy Cienia'
                ]
            ],
            [
                'level' => 85, 'scale' => 3000,
                'names' => [
                    'sword' => 'Miecz Runicznego Gwardzisty', 'axe' => 'Topór Magicznego Płomienia', 'bow' => 'Łuk z Eterycznej Energii',
                    'bell' => 'Dzwon Mistrza Iluzji', 'wand' => 'Kostur Arcymaga', 'dagger' => 'Sztylety z Czystej Energii',
                    'helmet_w' => 'Hełm Strażnika Arkanów', 'armor_w' => 'Zbroja Runiczna', 'boots_w' => 'Buty Żywiołaka Płomieni',
                    'helmet_m' => 'Kaptur Arcymaga', 'armor_m' => 'Szata Mistrza Iluzji', 'boots_m' => 'Buty Lewitacji',
                    'helmet_a' => 'Maska Niewidzialności', 'armor_a' => 'Skórznia Nasączona Magią', 'boots_a' => 'Podeszwy z Eteru',
                    'amulet' => 'Naszyjnik Runicznej Energii', 'ring' => 'Pierścień Absolutu'
                ]
            ],
            [
                'level' => 95, 'scale' => 7000,
                'names' => [
                    'sword' => 'Ostrze Skażonego Rycerza', 'axe' => 'Topór Czarownicy Zgnilizny', 'bow' => 'Łuk Tkany z Pajęczyny Plagi',
                    'bell' => 'Dzwon Ostatniego Tchnienia', 'wand' => 'Różdżka Zmutowanego Czarownika', 'dagger' => 'Sztylety Jadu Pająka Plagi',
                    'helmet_w' => 'Hełm Rycerza Skazy', 'armor_w' => 'Pancerz Skażonej Stali', 'boots_w' => 'Buty Zgnilizny',
                    'helmet_m' => 'Kaptur Pająka Plagi', 'armor_m' => 'Szata z Przeklętego Jedwabiu', 'boots_m' => 'Buty Kwasu',
                    'helmet_a' => 'Maska Cienia Skazy', 'armor_a' => 'Skórznia Upadłego Zabójcy', 'boots_a' => 'Podeszwy Trucizny',
                    'amulet' => 'Amulet Zmutowanego Oka', 'ring' => 'Pierścień Zgnilizny'
                ]
            ],
            [
                'level' => 99, 'scale' => 15000,
                'names' => [
                    'sword' => 'Miecz Pana Zniszczenia', 'axe' => 'Rozdzieracz Światów', 'bow' => 'Łuk Apokalipsy',
                    'bell' => 'Dzwon Sądu Ostatecznego', 'wand' => 'Kostur Władcy Mroku', 'dagger' => 'Sztylety Ostatecznego Zniszczenia',
                    'helmet_w' => 'Korona Pana Zniszczenia', 'armor_w' => 'Pancerz Absolutnego Chaosu', 'boots_w' => 'Buty Deptania Światów',
                    'helmet_m' => 'Kaptur Pożeracza Dusz', 'armor_m' => 'Szata Mrocznej Pustki', 'boots_m' => 'Buty Otchłani',
                    'helmet_a' => 'Maska Bezwzględnego Zniszczenia', 'armor_a' => 'Płaszcz Końca Czasu', 'boots_a' => 'Ciche Podeszwy Zmierzchu',
                    'amulet' => 'Serce Pana Zniszczenia', 'ring' => 'Sygnet Apokalipsy'
                ]
            ],
        ];

        $generatedCount = 0;

        foreach ($themes as $index => $theme) {
            foreach ($prototypes as $protoKey => $proto) {
                
                $scaledStats = [];
                foreach ($proto['stats'] as $statName => $baseValue) {
                    if ($statName === 'crit_chance') {
                        $scaledStats[$statName] = min(50, $baseValue + ($index * 2));
                    } else {
                        $scaledStats[$statName] = (int) round($baseValue * $theme['scale']);
                    }
                }

                $name = $theme['names'][$protoKey] ?? ('Przedmiot ' . $protoKey);

                ItemTemplate::create([
                    'id' => Str::ulid(),
                    'name' => $name,
                    'type' => $proto['type'],
                    'slot' => $proto['slot'],
                    'level_requirement' => $theme['level'],
                    'base_stats' => $scaledStats,
                    'description' => "Potężny artefakt odpowiedni dla poziomu " . $theme['level'] . ".",
                    'icon' => Str::slug($name),
                    'rarity_weights' => [
                        'common' => 50,
                        'uncommon' => 30,
                        'rare' => 15,
                        'epic' => 4,
                        'legendary' => 1
                    ],
                ]);
                $generatedCount++;
            }
        }

        $this->command->info('Created ' . count($manualItems) . ' manual items and ' . $generatedCount . ' generated item templates (total: ' . (count($manualItems) + $generatedCount) . ').');
    }
}

