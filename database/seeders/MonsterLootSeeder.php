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
                    'Widmowy Leśny Niedźwiedź' => [
                        'materials' => ["Mroczne Zioło","Magiczny Mech","Słaby Kryształ Many","Kawałek Poroża","Wilczy Kieł","Błona Skrzydła","Prastara Kora","Gobliński Sztylet"],
                        'items' => ["Sztylety z Kości Wilka","Pancerz z Wilczej Skóry","Maska z Czaszki Wilka","Miękkie Mokasyny","Płaszcz Liściastego Skrytobójcy","Dzwon Leśnego Szamana","Łuk z Pnia Suchodrzewu","Kostur z Serca Suchodrzewu","Zbroja z Twardej Kory","Hełm Leśnego Strażnika","Buty Tropiącego","Miecz Leśnego Goblina","Zatrute Sztylety Goblina","Topór Drwala z Mrocznego Lasu","Wzmocniony Hełm Strażnika","Ostrze Króla Lasu","Amulet Prastarego Dębu","Pierścień Wędrowca"]
                    ],
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
                    'Starożytny Golem Kamienny' => [
                        'materials' => ["Pył Grobowy","Zardzewiała Moneta","Odłamek Ruin","Przeklęty Onyks","Strzaskana Kość","Ektoplazma","Zardzewiały Grot","Fragment Całunu"],
                        'items' => ["Zardzewiały Miecz Szkieletu","Zardzewiały Hełm Rycerza","Różdżka Potępionych Dusz","Kaptur Zjaw","Kolczuga Strażnika Ruin","Maska Beztwarzowego Ducha","Skórznia Z Grobowca","Cmentarne Buty","Łuk z Kości Zjaw","Buty Mgły","Ząbkowany Topór Upiora","Żelazne Sabatony","Dzwon Pokutny","Naszyjnik z Zimnej Stali","Zbutwiała Szata Licza","Pierścień Wiecznego Żalu","Sztylety Skrytobójcy Dusz"]
                    ],
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
                    // 'Rycerz Ruin' to zwykły killable boss (rank 'boss') tej mapy - przejął
                    // dropy 'Licz Cieni' (rank='worldboss', patrz uwaga niżej), w tym
                    // 'Fragment Całunu', który wcześniej NIE MIAŁ żadnego innego źródła w grze.
                    'Rycerz Ruin' => [
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
                    'Mroczny Władca Trolli' => [
                        'materials' => ["Gruba Skóra Trolla","Szamański Koralik","Ogrzy Pazur","Krew Jaskiniowca","Ruda Żelaza","Błyszczący Grzyb","Śluz Jaskiniowy","Odłamek Skarbu"],
                        'items' => ["Gruboskórny Pancerz Trolla","Masywne Buciska","Sztylety z Zębów Nietoperza","Buty Cichego Kroku","Dzwon Szamana Trolli","Szamański Kaptur Trolli","Maczuga Ogra","Rozłupywacz Czaszek","Szata z Futer Nietoperzy","Maska Łowcy Ogrów","Różdżka Ziemnej Magii","Płaszcz Skalnego Cienia","Hełm z Czaszki Ogra","Łuk z Kości Jaskiniowca","Amulet Skalnego Trolla","Kamienny Pierścień"]
                    ],
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
                    'Wojownik Cienia Orków' => [
                        'materials' => ["Złamany Kieł Orka","Skrwawiony Totem","Twarde Rzemienie","Symbol Wodza","Skóra Pustynna","Wyschnięty Krzew","Kamień Szlifierski","Szczątki Pancerza"],
                        'items' => ["Łuk Krwawego Zwiadu","Buty Burzy Piaskowej","Maska Pustynnego Wiatru","Sztylety Pustkowi","Topór Berserkera Orków","Pierścień Berserkera","Pancerz z Hartowanej Stali","Buty Orkowego Wojownika","Kostur Szamana Krwi","Kaptur Szamana Krwi","Szata Nasączona Krwią","Dzwon Krwawego Rytuału","Skórznia Orkowego Zabójcy","Hełm Wodza Orków","Trzewiki Rytualne","Glewia Wodza Orków","Naszyjnik Orkowego Wodza"]
                    ],
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
                    'Bagnisty Behemot Cienia' => [
                        'materials' => ["Bagienne Zioło","Mętna Woda","Toksyczny Śluz","Skamieniały Torf","Zgniłe Mięso","Wiedźmi Amulet","Błotnisty Korzeń","Łuska Hydry"],
                        'items' => ["Zbutwiały Topór Topielca","Mokre Buty Bagienne","Skórznia Żmijowa","Maska z Błota","Różdżka Wiedźmiej Straży","Kaptur Wiedźmy Zgnilizny","Łuk z Wierzby Płaczącej","Szata Tkana z Zielska","Dzwon Utopców","Buty Bagiennej Mgły","Ostrze z Zęba Hydry","Pancerz z Łusek Hydry","Zatrute Kły Hydry","Pierścień Zgniłego Mchu","Podeszwy Bezdźwięku","Zardzewiały Hełm z Głębin","Naszyjnik z Oka Hydry"]
                    ],
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
                    'Wyvern Cienistego Szczytu' => [
                        'materials' => ["Kryształ Cienia","Górska Ruda Miedzi","Popiół Wulkaniczny","Mroczne Futro","Pióro Harpii","Odłamek Bazaltu","Zniszczona Księga Magii","Łuska Smoka Cienia"],
                        'items' => ["Płaszcz Górskiego Cienia","Maska Nocnego Drapieżnika","Łuk z Piór Harpii","Buty Sokolnika","Miecz Wykuty z Bazaltu","Pancerz Skalnego Golema","Ciężkie Kamienne Buty","Szata z Piór Harpii","Trzewiki Górskiego Wiatru","Topór Kamiennego Golema","Hełm z Czarnego Bazaltu","Różdżka z Górskiego Kryształu","Dzwon Górskiego Echa","Kaptur Burzowych Chmur","Sztylety Skalnego Kła","Pierścień Czarnego Kryształu","Piekielny Miecz Smoka","Topór Smoczego Gniewu","Smoczy Łuk","Pancerz ze Smoczych Łusek","Amulet Smoczego Oka"]
                    ],
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
                    'Arcymag Pustki i Arkanów' => [
                        'materials' => ["Eteryczny Pył","Czysta Mana","Czysty Pergamin","Odłamek Kostura Arcymaga","Runiczny Kamień","Magiczny Rdzeń","Żar Płomieni","Szkło Iluzji"],
                        'items' => ["Kaptur Arcymaga","Buty Lewitacji","Miecz Runicznego Gwardzisty","Buty Żywiołaka Płomieni","Hełm Strażnika Arkanów","Zbroja Runiczna","Topór Magicznego Płomienia","Dzwon Oddechu Smoka","Sztylety z Czystej Energii","Skórznia Nasączona Magią","Dzwon Mistrza Iluzji","Szata Mistrza Iluzji","Maska Niewidzialności","Łuk z Eterycznej Energii","Naszyjnik Runicznej Energii","Kostur Arcymaga","Różdżka Smoczej Łuski","Pierścień Absolutu"]
                    ],
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
                    'Władca Skażenia i Plagi' => [
                        'materials' => ["Skażony Metal","Popioły Miasta","Czarny Kamień Dusz","Skażona Kość","Przeklęta Stal","Fiolka Zgnilizny","Jad Pająka Plagi","Esencja Zniszczenia"],
                        'items' => ["Hełm Rycerza Skazy","Buty Zgnilizny","Ostrze Skażonego Rycerza","Pancerz Skażonej Stali","Topór Czarownicy Zgnilizny","Kaptur Pająka Plagi","Rozdzieracz Światów","Pancerz Absolutnego Chaosu","Łuk Tkany z Pajęczyny Plagi","Sztylety Jadu Pająka Plagi","Miecz Pana Zniszczenia","Korona Pana Zniszczenia","Dzwon Ostatniego Tchnienia","Szata Mrocznej Pustki","Łuk Apokalipsy","Serce Pana Zniszczenia","Sygnet Apokalipsy","Sztylety Ostatecznego Zniszczenia"]
                    ],
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
            ],
            // Epicentrum Apokalipsy (2026-08-06) - loot ŚWIADOMIE pożyczony ze Skażonego
            // Miasta ('general'/'boss_general'/materiały specyficzne oraz skrzynia -
            // brak nowych surowców, zgodnie z decyzją "nie tworzę nowych przedmiotów
            // na razie"). Ekwipunek NATOMIAST to 15 gotowych szablonów z tieru lvl95/99
            // (`ItemTemplateSeeder`), które istniały w bazie, ale nie były przypisane
            // do ŻADNEGO dropu na Skażonym Mieście (różdżka obu tierów, cały zestaw
            // Skrytobójcy _a obu tierów, kilka butów/hełmów Maga) - patrz
            // scratch/verify_missing_items.php. Ta mapa jest jedynym miejscem, gdzie
            // można je teraz wylosować.
            'Epicentrum Apokalipsy' => [
                'general' => ['Skażony Metal', 'Popioły Miasta'],
                'boss_general' => ['Czarny Kamień Dusz'],
                'monsters' => [
                    'Herold Apokalipsy' => [
                        'materials' => ['Skażona Kość'],
                        'items' => ['Różdżka Zmutowanego Czarownika', 'Amulet Zmutowanego Oka']
                    ],
                    'Nieumarły Legion Popiołów' => [
                        'materials' => ['Przeklęta Stal'],
                        'items' => ['Buty Kwasu', 'Pierścień Zgnilizny']
                    ],
                    'Płonący Inkwizytor Zagłady' => [
                        'materials' => ['Fiolka Zgnilizny'],
                        'items' => ['Maska Cienia Skazy', 'Kaptur Pożeracza Dusz']
                    ],
                    'Widmo Ostatniego Dnia' => [
                        'materials' => ['Jad Pająka Plagi'],
                        'items' => ['Skórznia Upadłego Zabójcy', 'Buty Otchłani']
                    ],
                    'Pożeracz Dusz Otchłani' => [
                        'materials' => ['Esencja Zniszczenia'],
                        'items' => ['Podeszwy Trucizny', 'Maska Bezwzględnego Zniszczenia']
                    ],
                    'Nieumarły Jeździec Zagłady' => [
                        'materials' => ['Esencja Zniszczenia'],
                        'items' => ['Dzwon Sądu Ostatecznego', 'Buty Deptania Światów', 'Płaszcz Końca Czasu']
                    ],
                    'Prasmok Ostatecznej Zagłady' => [
                        'materials' => ['Esencja Zniszczenia'],
                        'items' => ['Kostur Władcy Mroku', 'Ciche Podeszwy Zmierzchu']
                    ],
                ]
            ]
        ];

        // Pobierz wszystkie przedmioty dla szybkiego wyszukiwania
        $itemTemplates = ItemTemplate::all()->keyBy('name');

        foreach ($mapsData as $mapName => $mapConfig) {
            foreach ($mapConfig['monsters'] as $monsterName => $dropConfig) {
                // Znajdź potwora
                $monster = Monster::where('name', $monsterName)->first();

                if (!$monster) {
                    $this->command->warn("Nie znaleziono potwora {$monsterName}");
                    continue;
                }

                $monsterRank = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;

                // Potwory rangi 'worldboss' NIE MOGĄ mieć własnego dropu (patrz
                // docs/modules/loot.md pkt. 4): starcia z nimi nigdy nie kończą się
                // zwycięstwem gracza, więc DropService nigdy się nie uruchamia dla tych
                // walk. Zamiast zostawiać martwe wpisy złożone z ogólnych materiałów mapy,
                // usuwamy listę łupów całkowicie - realny łup (unikalne materiały/przedmioty
                // world bossa) jest już przeniesiony na bossa lokacji tej samej mapy (patrz
                // niżej), a wspólny general/boss_general pool i tak trafia do bossa lokacji.
                if ($monsterRank === 'worldboss') {
                    // Szukamy PO NAZWIE (nie po aktualnym $monster->loot_table_id!) - w tym
                    // momencie world boss może tymczasowo wskazywać na WSPÓLNĄ tabelę
                    // (forest_common/ruins_common/desert_common), którą wcześniej przypisał mu
                    // LootTableSeeder wg. przedziału poziomów. Kasowanie po loot_table_id
                    // skasowałoby wpisy współdzielone przez inne potwory wciąż na nią wskazujące.
                    // Nazwa "Loot for {name}" jest unikalna dla tego potwora, więc bezpieczna.
                    $staleTable = LootTable::where('name', "Loot for " . $monster->name)->first();
                    if ($staleTable) {
                        LootTableEntry::where('loot_table_id', $staleTable->id)->delete();
                    }
                    $monster->update(['loot_table_id' => null]);
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

                $isBoss = $monsterRank === 'boss';

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
                        // Balans ekonomii (2026-07-29): szansa na materiały zwiększona o 400%
                        // (waga x5) względem poprzednich wartości (5 -> 25, 2 -> 10), aby
                        // ułatwić crafting po tym, jak drop przedmiotów zszedł do zera (patrz niżej).
                        'weight' => (in_array($dropName, $specificDrops) ? 5 : 2) * 5,
                        'min_qty' => 1,
                        'max_qty' => $isBoss ? 3 : 1
                    ]);
                }

                // 3. Przypisz wpisy ekwipunku przypisane DOKŁADNIE do tego potwora (waga 2 -> ~0.6% szansy na drop)
                foreach ($specificEquipment as $itemName) {
                    $template = $itemTemplates->get($itemName);

                    if (!$template) {
                        $this->command->warn("Nie znaleziono przedmiotu ekwipunku {$itemName} w bazie.");
                        continue;
                    }

                    LootTableEntry::updateOrCreate([
                        'loot_table_id' => $lootTable->id,
                        'reward_type' => 'item',
                        'ref_ulid' => $template->id,
                    ], [
                        // Balans ekonomii (2026-07-30): drop bezpośredni przedmiotów ekwipunku
                        // na mapach ustawiony na wagę 2 (~0.6% szansy na drop).
                        'weight' => 2,
                        'min_qty' => 1,
                        'max_qty' => 1
                    ]);
                }

                // 4. Drop Klucza do Lochu z Bossa mapy (Waga 25 równa materiałom)
                $bossKeyMapping = [
                    'Widmowy Leśny Niedźwiedź' => 'Klucz Katakumb',
                    'Strażnik Puszczy' => 'Klucz Katakumb',

                    'Starożytny Golem Kamienny' => 'Klucz Krypty',
                    'Rycerz Ruin' => 'Klucz Krypty',

                    'Mroczny Władca Trolli' => 'Klucz Krypty',
                    'Starożytny Ogr' => 'Klucz Krypty',

                    'Wojownik Cienia Orków' => 'Klucz Pustkowi',
                    'Niszczyciel Pustkowi' => 'Klucz Pustkowi',

                    'Bagnisty Behemot Cienia' => 'Klucz Cytadeli',
                    'Królowa Wiedźm' => 'Klucz Cytadeli',

                    'Wyvern Cienistego Szczytu' => 'Klucz Cytadeli',
                    'Władca Cieni' => 'Klucz Cytadeli',

                    'Arcymag Pustki i Arkanów' => 'Klucz Otchłani',
                    'Wielki Inkwizytor' => 'Klucz Otchłani',

                    'Władca Skażenia i Plagi' => 'Klucz Otchłani',
                    'Książę Zniszczenia' => 'Klucz Otchłani',
                ];

                if ($isBoss && isset($bossKeyMapping[$monsterName])) {
                    $keyName = $bossKeyMapping[$monsterName];
                    $template = $itemTemplates->get($keyName);

                    if ($template) {
                        LootTableEntry::updateOrCreate([
                            'loot_table_id' => $lootTable->id,
                            'ref_ulid' => $template->id,
                        ], [
                            'reward_type' => 'material',
                            'weight' => 25, // Wysoka szansa na klucz z bossa mapy
                            'min_qty' => 1,
                            'max_qty' => 1
                        ]);
                    }
                }

                // 5. Drop Skrzynek z łupami oraz Skrzyni Ksiąg Umiejętności z Bossów map
                $bossChestMapping = [
                    'Widmowy Leśny Niedźwiedź' => 'Skrzynia Mrocznego Lasu',
                    'Strażnik Puszczy' => 'Skrzynia Mrocznego Lasu',

                    'Starożytny Golem Kamienny' => 'Skrzynia Starych Ruin',
                    'Rycerz Ruin' => 'Skrzynia Starych Ruin',

                    'Mroczny Władca Trolli' => 'Skrzynia Jaskini Trolli',
                    'Starożytny Ogr' => 'Skrzynia Jaskini Trolli',

                    'Wojownik Cienia Orków' => 'Skrzynia Pustkowi Orków',
                    'Niszczyciel Pustkowi' => 'Skrzynia Pustkowi Orków',

                    'Bagnisty Behemot Cienia' => 'Skrzynia Bagien Grozy',
                    'Królowa Wiedźm' => 'Skrzynia Bagien Grozy',

                    'Wyvern Cienistego Szczytu' => 'Skrzynia Gór Cienia',
                    'Władca Cieni' => 'Skrzynia Gór Cienia',

                    'Arcymag Pustki i Arkanów' => 'Skrzynia Wieży Magów',
                    'Wielki Inkwizytor' => 'Skrzynia Wieży Magów',

                    'Władca Skażenia i Plagi' => 'Skrzynia Skażonego Miasta',
                    'Książę Zniszczenia' => 'Skrzynia Skażonego Miasta',

                    // Epicentrum Apokalipsy (2026-08-06) - skrzynia POŻYCZONA ze Skażonego
                    // Miasta (patrz uwaga przy 'general'/'boss_general' wyżej - brak nowych
                    // surowców na razie).
                    'Nieumarły Jeździec Zagłady' => 'Skrzynia Skażonego Miasta',
                    'Prasmok Ostatecznej Zagłady' => 'Skrzynia Skażonego Miasta',
                ];

                if ($isBoss) {
                    if (isset($bossChestMapping[$monsterName])) {
                        $chestName = $bossChestMapping[$monsterName];
                        $template = $itemTemplates->get($chestName);

                        if ($template) {
                            LootTableEntry::updateOrCreate([
                                'loot_table_id' => $lootTable->id,
                                'ref_ulid' => $template->id,
                            ], [
                                'reward_type' => 'item',
                                'weight' => 15,
                                'min_qty' => 1,
                                'max_qty' => 1
                            ]);
                        }
                    }

                    // Dodanie Skrzyni Ksiąg Umiejętności do WSZYSTKICH bossów na mapach
                    $skillBookChestTpl = $itemTemplates->get('Skrzynia Ksiąg Umiejętności');
                    if ($skillBookChestTpl) {
                        LootTableEntry::updateOrCreate([
                            'loot_table_id' => $lootTable->id,
                            'ref_ulid' => $skillBookChestTpl->id,
                        ], [
                            'reward_type' => 'item',
                            'weight' => 20,
                            'min_qty' => 1,
                            'max_qty' => 3
                        ]);
                    }

                    // Dodanie Kamienia Duchowego do bossów od T5 wzwyż
                    $tier5Bosses = ['Królowa Wiedźm', 'Bagnisty Behemot Cienia', 'Władca Cieni', 'Wyvern Cienistego Szczytu', 'Wielki Inkwizytor', 'Arcymag Pustki i Arkanów', 'Książę Zniszczenia', 'Władca Skażenia i Plagi'];
                    if (in_array($monsterName, $tier5Bosses, true)) {
                        $soulStoneTpl = $itemTemplates->get('Kamień Duchowy');
                        if ($soulStoneTpl) {
                            LootTableEntry::updateOrCreate([
                                'loot_table_id' => $lootTable->id,
                                'ref_ulid' => $soulStoneTpl->id,
                            ], [
                                'reward_type' => 'material',
                                'weight' => 15,
                                'min_qty' => 1,
                                'max_qty' => 1
                            ]);
                        }
                    }
                }
            }
        }

        $this->command->info('MonsterLootSeeder completed.');
    }
}
