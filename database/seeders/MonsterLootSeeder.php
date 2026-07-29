<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;

class MonsterLootSeeder extends Seeder
{
    public function run(): void
    {
        $mapsData = [
            'Mroczny Las' => [
                'general' => ['Mroczne Zioło', 'Magiczny Mech', 'Słaby Kryształ Many'],
                'boss_general' => ['Kawałek Poroża'],
                'monsters' => [
                    'Wilk Leśny' => [
                        'materials' => ['Wilczy Kieł'],
                        'items' => ['Sztylety z Kości Wilka', 'Pancerz z Wilczej Skóry']
                    ],
                    'Nietoperz Jaskiniowy' => [
                        'materials' => ['Błona Skrzydła'],
                        'items' => ['Maska z Czaszki Wilka', 'Miękkie Mokasyny']
                    ],
                    'Pająk Leśny' => [
                        'materials' => ['Mroczne Zioło'],
                        'items' => ['Płaszcz Liściastego Skrytobójcy', 'Dzwon Leśnego Szamana']
                    ],
                    'Suchodrzew' => [
                        'materials' => ['Prastara Kora'],
                        'items' => ['Łuk z Pnia Suchodrzewu', 'Kostur z Serca Suchodrzewu', 'Zbroja z Twardej Kory']
                    ],
                    'Zdziczały Dzik' => [
                        'materials' => ['Wilczy Kieł'],
                        'items' => ['Hełm Leśnego Strażnika', 'Buty Tropiącego']
                    ],
                    'Goblin Zwiadowca' => [
                        'materials' => ['Gobliński Sztylet'],
                        'items' => ['Miecz Leśnego Goblina', 'Zatrute Sztylety Goblina']
                    ],
                    // 'Strażnik Puszczy' to zwykły killable boss (rank 'boss') tej mapy - to na niego
                    // przeniesiono unikalne dropy, które wcześniej wisiały na 'Król Lasu' (patrz niżej).
                    'Strażnik Puszczy' => [
                        'materials' => ['Prastara Kora'],
                        'items' => ['Topór Drwala z Mrocznego Lasu', 'Wzmocniony Hełm Strażnika', 'Ostrze Króla Lasu', 'Amulet Prastarego Dębu', 'Pierścień Wędrowca']
                    ],
                    // UWAGA (fix 2026-07-28): 'Król Lasu' ma rank='worldboss'. Starcia ze
                    // światowymi bossami w EncounterService::simulate() są na sztywno rozstrzygane
                    // jako zwycięstwo przeciwnika ($winner = 'enemy'; "Worldboss always wins/survives"),
                    // więc DropService NIGDY nie jest wywoływany dla tych walk - żaden wpis w
                    // LootTable przypisanej bezpośrednio do potwora rangi worldboss nie może
                    // realnie wypaść. Unikalne materiały/przedmioty zostały więc przeniesione na
                    // 'Strażnika Puszczy' (zwykłego, zabijalnego bossa tej samej mapy). Nagrody za
                    // faktyczne pokonanie world bossa (klucze do lochów) idą osobnym torem -
                    // patrz `App\Jobs\WorldBossRewardJob` i `docs/modules/world_boss.md`.
                    'Król Lasu' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Stare Ruiny' => [
                'general' => ['Pył Grobowy', 'Zardzewiała Moneta', 'Odłamek Ruin'],
                'boss_general' => ['Przeklęty Onyks'],
                'monsters' => [
                    'Szkielet Wojownik' => [
                        'materials' => ['Strzaskana Kość'],
                        'items' => ['Zardzewiały Miecz Szkieletu', 'Zardzewiały Hełm Rycerza']
                    ],
                    'Mroczny Kultysta' => [
                        'materials' => ['Pył Grobowy'],
                        'items' => ['Różdżka Potępionych Dusz', 'Kaptur Zjaw']
                    ],
                    'Duch Strażnik' => [
                        'materials' => ['Ektoplazma'],
                        'items' => ['Kolczuga Strażnika Ruin', 'Maska Beztwarzowego Ducha']
                    ],
                    'Ghul' => [
                        'materials' => ['Strzaskana Kość'],
                        'items' => ['Skórznia Z Grobowca', 'Cmentarne Buty']
                    ],
                    'Upiorny Łucznik' => [
                        'materials' => ['Zardzewiały Grot'],
                        'items' => ['Łuk z Kości Zjaw', 'Buty Mgły']
                    ],
                    'Kamienny Gargulec' => [
                        'materials' => ['Odłamek Ruin'],
                        'items' => ['Ząbkowany Topór Upiora', 'Żelazne Sabatony']
                    ],
                    // 'Władca Krypty' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Licz Cieni' (rank='worldboss', patrz uwaga niżej), w tym
                    // 'Fragment Całunu', który wcześniej NIE MIAŁ żadnego innego źródła w grze.
                    'Władca Krypty' => [
                        'materials' => ['Strzaskana Kość', 'Fragment Całunu'],
                        'items' => ['Dzwon Pokutny', 'Naszyjnik z Zimnej Stali', 'Zbutwiała Szata Licza', 'Pierścień Wiecznego Żalu', 'Sztylety Skrytobójcy Dusz']
                    ],
                    // UWAGA (fix 2026-07-28): tak jak 'Król Lasu' - 'Licz Cieni' ma
                    // rank='worldboss', więc jego własna LootTable nigdy nie jest losowana
                    // (DropService nie jest wywoływany dla walk ze światowym bossem). Materiał
                    // 'Fragment Całunu' był tu JEDYNYM źródłem w całej grze - bez przeniesienia
                    // przedmiot "Zbutwiała Szata Licza" byłby całkowicie nieosiągalny (nie do
                    // zdobycia z dropu ani do wytworzenia, bo receptura wymaga tego materiału).
                    'Licz Cieni' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Jaskinia Trolli' => [
                'general' => ['Ruda Żelaza', 'Błyszczący Grzyb', 'Śluz Jaskiniowy'],
                'boss_general' => ['Odłamek Skarbu'],
                'monsters' => [
                    'Troll Paskudnik' => [
                        'materials' => ['Gruba Skóra Trolla'],
                        'items' => ['Gruboskórny Pancerz Trolla', 'Masywne Buciska']
                    ],
                    'Pełzacz Jaskiniowy' => [
                        'materials' => ['Śluz Jaskiniowy'],
                        'items' => ['Sztylety z Zębów Nietoperza', 'Buty Cichego Kroku']
                    ],
                    'Troll Szaman' => [
                        'materials' => ['Gruba Skóra Trolla', 'Szamański Koralik'],
                        'items' => ['Dzwon Szamana Trolli', 'Szamański Kaptur Trolli']
                    ],
                    'Ogr Rozłupywacz' => [
                        'materials' => ['Ogrzy Pazur'],
                        'items' => ['Maczuga Ogra', 'Rozłupywacz Czaszek']
                    ],
                    'Jaskiniowy Nietoperz Alfa' => [
                        'materials' => ['Krew Jaskiniowca'],
                        'items' => ['Szata z Futer Nietoperzy', 'Maska Łowcy Ogrów']
                    ],
                    'Troll Scalony' => [
                        'materials' => ['Gruba Skóra Trolla'],
                        'items' => ['Różdżka Ziemnej Magii', 'Płaszcz Skalnego Cienia']
                    ],
                    // 'Starożytny Ogr' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Król Trolli' (rank='worldboss', patrz uwaga niżej).
                    'Starożytny Ogr' => [
                        'materials' => ['Ogrzy Pazur', 'Gruba Skóra Trolla'],
                        'items' => ['Hełm z Czaszki Ogra', 'Łuk z Kości Jaskiniowca', 'Amulet Skalnego Trolla', 'Kamienny Pierścień']
                    ],
                    // UWAGA (fix 2026-07-28): 'Król Trolli' ma rank='worldboss' - patrz
                    // wyjaśnienie przy 'Król Lasu' powyżej (DropService nigdy nie jest
                    // wywoływany dla walk ze światowym bossem, więc jego własna LootTable
                    // nigdy się nie losuje).
                    'Król Trolli' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Pustkowia Orków' => [
                'general' => ['Skóra Pustynna', 'Wyschnięty Krzew', 'Kamień Szlifierski'],
                'boss_general' => ['Szczątki Pancerza'],
                'monsters' => [
                    'Orczy Zwiad' => [
                        'materials' => ['Złamany Kieł Orka'],
                        'items' => ['Łuk Krwawego Zwiadu', 'Buty Burzy Piaskowej']
                    ],
                    'Pustynny Skorpion' => [
                        'materials' => ['Skóra Pustynna'],
                        'items' => ['Maska Pustynnego Wiatru', 'Sztylety Pustkowi']
                    ],
                    'Ork Berserker' => [
                        'materials' => ['Złamany Kieł Orka'],
                        'items' => ['Topór Berserkera Orków', 'Pierścień Berserkera']
                    ],
                    'Ork Topornik' => [
                        'materials' => ['Złamany Kieł Orka'],
                        'items' => ['Pancerz z Hartowanej Stali', 'Buty Orkowego Wojownika']
                    ],
                    'Szaman Krwi' => [
                        'materials' => ['Skrwawiony Totem'],
                        'items' => ['Kostur Szamana Krwi', 'Kaptur Szamana Krwi', 'Szata Nasączona Krwią']
                    ],
                    'Dowódca Watahy' => [
                        'materials' => ['Twarde Rzemienie'],
                        'items' => ['Dzwon Krwawego Rytuału', 'Skórznia Orkowego Zabójcy']
                    ],
                    // 'Niszczyciel Pustkowi' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Wódz Orków' (rank='worldboss', patrz uwaga przy 'Król Lasu' na górze pliku -
                    // DropService nigdy nie jest wywoływany dla walk ze światowym bossem).
                    'Niszczyciel Pustkowi' => [
                        'materials' => ['Twarde Rzemienie', 'Symbol Wodza'],
                        'items' => ['Hełm Wodza Orków', 'Trzewiki Rytualne', 'Glewia Wodza Orków', 'Naszyjnik Orkowego Wodza']
                    ],
                    'Wódz Orków' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Bagna Grozy' => [
                'general' => ['Bagienne Zioło', 'Mętna Woda', 'Toksyczny Śluz'],
                'boss_general' => ['Skamieniały Torf'],
                'monsters' => [
                    'Topielec' => [
                        'materials' => ['Zgniłe Mięso'],
                        'items' => ['Zbutwiały Topór Topielca', 'Mokre Buty Bagienne']
                    ],
                    'Błotny Bazyliszek' => [
                        'materials' => ['Toksyczny Śluz'],
                        'items' => ['Skórznia Żmijowa', 'Maska z Błota']
                    ],
                    'Wiedźmia Straż' => [
                        'materials' => ['Wiedźmi Amulet'],
                        'items' => ['Różdżka Wiedźmiej Straży', 'Kaptur Wiedźmy Zgnilizny']
                    ],
                    'Drzewiec Plugawy' => [
                        'materials' => ['Błotnisty Korzeń'],
                        'items' => ['Łuk z Wierzby Płaczącej', 'Szata Tkana z Zielska']
                    ],
                    'Widmo Bagien' => [
                        'materials' => ['Zgniłe Mięso'],
                        'items' => ['Dzwon Utopców', 'Buty Bagiennej Mgły']
                    ],
                    'Hydra Bagienna' => [
                        'materials' => ['Łuska Hydry'],
                        'items' => ['Ostrze z Zęba Hydry', 'Pancerz z Łusek Hydry', 'Zatrute Kły Hydry']
                    ],
                    // 'Królowa Wiedźm' to zwykły killable boss (rank 'boss') tej mapy - przejęła
                    // dropy 'Moczarowy Behemot' (rank='worldboss', patrz uwaga przy 'Król Lasu'
                    // na górze pliku).
                    'Królowa Wiedźm' => [
                        'materials' => ['Wiedźmi Amulet', 'Łuska Hydry'],
                        'items' => ['Pierścień Zgniłego Mchu', 'Podeszwy Bezdźwięku', 'Zardzewiały Hełm z Głębin', 'Naszyjnik z Oka Hydry']
                    ],
                    'Moczarowy Behemot' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Góry Cienia' => [
                'general' => ['Kryształ Cienia', 'Górska Ruda Miedzi'],
                'boss_general' => ['Popiół Wulkaniczny'],
                'monsters' => [
                    'Wilk Cienia' => [
                        'materials' => ['Mroczne Futro'],
                        'items' => ['Płaszcz Górskiego Cienia', 'Maska Nocnego Drapieżnika']
                    ],
                    'Mroczny Gryf' => [
                        'materials' => ['Pióro Harpii'],
                        'items' => ['Łuk z Piór Harpii', 'Buty Sokolnika']
                    ],
                    'Golem Bazaltowy' => [
                        'materials' => ['Odłamek Bazaltu'],
                        'items' => ['Miecz Wykuty z Bazaltu', 'Pancerz Skalnego Golema', 'Ciężkie Kamienne Buty']
                    ],
                    'Harpia' => [
                        'materials' => ['Pióro Harpii'],
                        'items' => ['Szata z Piór Harpii', 'Trzewiki Górskiego Wiatru']
                    ],
                    'Cieniowy Gargulec' => [
                        'materials' => ['Odłamek Bazaltu'],
                        'items' => ['Topór Kamiennego Golema', 'Hełm z Czarnego Bazaltu']
                    ],
                    'Wędrowny Czarownik' => [
                        'materials' => ['Zniszczona Księga Magii'],
                        'items' => ['Różdżka z Górskiego Kryształu', 'Dzwon Górskiego Echa', 'Kaptur Burzowych Chmur']
                    ],
                    // 'Władca Cieni' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Smok Cienia' (rank='worldboss', patrz uwaga przy 'Król Lasu' na górze pliku).
                    'Władca Cieni' => [
                        'materials' => ['Kryształ Cienia', 'Łuska Smoka Cienia'],
                        'items' => ['Sztylety Skalnego Kła', 'Pierścień Czarnego Kryształu', 'Piekielny Miecz Smoka', 'Topór Smoczego Gniewu', 'Smoczy Łuk', 'Pancerz ze Smoczych Łusek', 'Amulet Smoczego Oka']
                    ],
                    'Smok Cienia' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Wieża Magów' => [
                'general' => ['Eteryczny Pył', 'Czysta Mana', 'Czysty Pergamin'],
                'boss_general' => ['Odłamek Kostura Arcymaga'],
                'monsters' => [
                    'Adepci Run' => [
                        'materials' => ['Runiczny Kamień'],
                        'items' => ['Kaptur Arcymaga', 'Buty Lewitacji']
                    ],
                    'Żywiołak Lodu' => [
                        'materials' => ['Eteryczny Pył'],
                        'items' => ['Miecz Runicznego Gwardzisty', 'Buty Żywiołaka Płomieni']
                    ],
                    'Strażnik Arkanów' => [
                        'materials' => ['Magiczny Rdzeń'],
                        'items' => ['Hełm Strażnika Arkanów', 'Zbroja Runiczna']
                    ],
                    'Żywiołak Płomieni' => [
                        'materials' => ['Żar Płomieni'],
                        'items' => ['Topór Magicznego Płomienia', 'Dzwon Oddechu Smoka']
                    ],
                    'Runiczny Konstrukt' => [
                        'materials' => ['Runiczny Kamień'],
                        'items' => ['Sztylety z Czystej Energii', 'Skórznia Nasączona Magią']
                    ],
                    'Mistrz Iluzji' => [
                        'materials' => ['Szkło Iluzji'],
                        'items' => ['Dzwon Mistrza Iluzji', 'Szata Mistrza Iluzji', 'Maska Niewidzialności']
                    ],
                    // 'Wielki Inkwizytor' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Arcymag' (rank='worldboss', patrz uwaga przy 'Król Lasu' na górze pliku).
                    'Wielki Inkwizytor' => [
                        'materials' => ['Czysta Mana'],
                        'items' => ['Łuk z Eterycznej Energii', 'Naszyjnik Runicznej Energii', 'Kostur Arcymaga', 'Różdżka Smoczej Łuski', 'Pierścień Absolutu']
                    ],
                    'Arcymag' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ],
            'Skażone Miasto' => [
                'general' => ['Skażony Metal', 'Popioły Miasta'],
                'boss_general' => ['Czarny Kamień Dusz'],
                'monsters' => [
                    'Zmutowany Nieumarły' => [
                        'materials' => ['Skażona Kość'],
                        'items' => ['Hełm Rycerza Skazy', 'Buty Zgnilizny']
                    ],
                    'Plagowy Kat' => [
                        'materials' => ['Przeklęta Stal'],
                        'items' => ['Ostrze Skażonego Rycerza', 'Pancerz Skażonej Stali']
                    ],
                    'Czarownica Zgnilizny' => [
                        'materials' => ['Fiolka Zgnilizny'],
                        'items' => ['Topór Czarownicy Zgnilizny', 'Kaptur Pająka Plagi']
                    ],
                    'Zbezczeszczony Golem' => [
                        'materials' => ['Skażony Metal'],
                        'items' => ['Rozdzieracz Światów', 'Pancerz Absolutnego Chaosu']
                    ],
                    'Pająk Plagi' => [
                        'materials' => ['Jad Pająka Plagi'],
                        'items' => ['Łuk Tkany z Pajęczyny Plagi', 'Sztylety Jadu Pająka Plagi']
                    ],
                    'Rycerz Skazy' => [
                        'materials' => ['Przeklęta Stal'],
                        'items' => ['Miecz Pana Zniszczenia', 'Korona Pana Zniszczenia']
                    ],
                    // 'Książę Zniszczenia' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Pan Zniszczenia' (rank='worldboss', patrz uwaga przy 'Król Lasu' na
                    // górze pliku). 'Esencja Zniszczenia' była już wspólnym materiałem obu wpisów,
                    // więc nie duplikujemy jej na liście materiałów.
                    'Książę Zniszczenia' => [
                        'materials' => ['Esencja Zniszczenia'],
                        'items' => ['Dzwon Ostatniego Tchnienia', 'Szata Mrocznej Pustki', 'Łuk Apokalipsy', 'Serce Pana Zniszczenia', 'Sygnet Apokalipsy', 'Sztylety Ostatecznego Zniszczenia']
                    ],
                    'Pan Zniszczenia' => [
                        'materials' => [],
                        'items' => []
                    ]
                ]
            ]
        ];

        // Pobierz wszystkie przedmioty dla szybkiego wyszukiwania
        $itemTemplates = ItemTemplate::all()->keyBy('name');

        // Pierwsze 3 mapy (wg. progresji level_min) mają podwojoną szansę na drop ekwipunku (x2 wagi),
        // aby ułatwić start nowym graczom.
        $doubleItemDropChanceMaps = ['Mroczny Las', 'Stare Ruiny', 'Jaskinia Trolli'];

        foreach ($mapsData as $mapName => $mapConfig) {
            $itemWeightMultiplier = in_array($mapName, $doubleItemDropChanceMaps, true) ? 2 : 1;

            foreach ($mapConfig['monsters'] as $monsterName => $dropConfig) {
                // Znajdź potwora
                $monster = Monster::where('name', $monsterName)->first();

                if (!$monster) {
                    $this->command->warn("Nie znaleziono potwora {$monsterName}");
                    continue;
                }

                // Stwórz lub pobierz przypisaną tabelę łupów dla potwora
                $tableName = "Loot for " . $monster->name;
                $lootTable = LootTable::firstOrCreate(
                    ['name' => $tableName],
                    ['description' => "Loot table for {$monster->name}."]
                );

                // Zaktualizuj potwora o ID tabeli
                $monster->update(['loot_table_id' => $lootTable->id]);

                // Wyczyść stare wpisy w tabeli łupów dla zachowania świeżych referencji ULID
                LootTableEntry::where('loot_table_id', $lootTable->id)->delete();

                $specificDrops = is_array($dropConfig) && isset($dropConfig['materials']) ? $dropConfig['materials'] : (is_array($dropConfig) ? $dropConfig : []);
                $specificEquipment = is_array($dropConfig) && isset($dropConfig['items']) ? $dropConfig['items'] : [];

                // Skompletuj wszystkie materiały, które mogą spaść
                $possibleMaterialDrops = array_merge([], $mapConfig['general'], $specificDrops);

                $monsterRank = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
                $isBoss = in_array($monsterRank, ['boss', 'worldboss']);

                if ($isBoss && isset($mapConfig['boss_general'])) {
                    $possibleMaterialDrops = array_merge($possibleMaterialDrops, $mapConfig['boss_general']);
                }

                // 1. Dodanie Złota (Waga: 300 dla zachowania bardzo niskiej szansy na ekwipunek)
                LootTableEntry::firstOrCreate([
                    'loot_table_id' => $lootTable->id,
                    'reward_type' => 'gold',
                ], [
                    'weight' => 300,
                    'min_qty' => $monster->level * 2,
                    'max_qty' => max($monster->level * 5, 10)
                ]);

                // 2. Przypisz wpisy LootTableEntries dla materiałów
                foreach ($possibleMaterialDrops as $dropName) {
                    $template = $itemTemplates->get($dropName);

                    if (!$template) {
                        $this->command->warn("Nie znaleziono materiału {$dropName} w bazie.");
                        continue;
                    }

                    LootTableEntry::firstOrCreate([
                        'loot_table_id' => $lootTable->id,
                        'reward_type' => 'material',
                        'ref_ulid' => $template->id,
                    ], [
                        'weight' => in_array($dropName, $specificDrops) ? 5 : (in_array($dropName, $mapConfig['boss_general'] ?? []) ? 2 : 2),
                        'min_qty' => 1,
                        'max_qty' => $isBoss ? 3 : 1
                    ]);
                }

                // 3. Przypisz BARDZO RADKIE wpisy ekwipunku przypisane DOKŁADNIE do tego potwora (Bardzo mała szansa: waga 1)
                foreach ($specificEquipment as $itemName) {
                    $template = $itemTemplates->get($itemName);

                    if (!$template) {
                        $this->command->warn("Nie znaleziono przedmiotu ekwipunku {$itemName} w bazie.");
                        continue;
                    }

                    LootTableEntry::firstOrCreate([
                        'loot_table_id' => $lootTable->id,
                        'reward_type' => 'item',
                        'ref_ulid' => $template->id,
                    ], [
                        // Bardzo mała szansa (waga 1/320~0.3% dla zwykłych, 2/320~0.6% dla bossów),
                        // podwojona (x{$itemWeightMultiplier}) na pierwszych 3 mapach.
                        'weight' => ($isBoss ? 2 : 1) * $itemWeightMultiplier,
                        'min_qty' => 1,
                        'max_qty' => 1
                    ]);
                }
            }
        }

        $this->command->info('MonsterLootSeeder completed.');
    }
}
