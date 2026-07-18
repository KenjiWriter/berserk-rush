<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemRecipe;
use Illuminate\Support\Str;

class EquipmentRecipeSeeder extends Seeder
{
    public function run(): void
    {
        // Najpierw pobierzmy wszystkie materiały z bazy
        $materials = ItemTemplate::where('type', 'material')->get()->keyBy('name');

        if ($materials->isEmpty()) {
            $this->command->warn('Brakuje materiałów. Uruchom najpierw MaterialItemSeeder.');
            return;
        }

        // Czyścimy stare przepisy dla ekwipunku, aby uniknąć duplikatów przy ponownym uruchomieniu
        $equipmentIds = ItemTemplate::whereIn('type', ['weapon', 'armor', 'accessory'])->pluck('id');
        ItemRecipe::whereIn('result_item_template_id', $equipmentIds)->delete();

        // Grupowanie prototypów dla łatwiejszego przypisywania materiałów
        $groups = [
            'melee' => ['Miecz', 'Topór'],
            'ranged' => ['Łuk', 'Sztylety'],
            'magic' => ['Magiczny Dzwon', 'Różdżka', 'Kaptur Maga', 'Szata Maga', 'Buty Maga'],
            'heavy' => ['Hełm Wojownika', 'Zbroja Wojownika', 'Buty Wojownika'],
            'light' => ['Maska Zabójcy', 'Skórznia Zabójcy', 'Buty Zabójcy'],
            'jewelry' => ['Naszyjnik Życia', 'Naszyjnik Mocy', 'Pierścień Siły', 'Pierścień Zwinności', 'Pierścień Mądrości']
        ];

        // Definicja zapotrzebowania z uwzględnieniem podgrup dla każdego tieru
        $tiers = [
            5 => [ // Mroczny Las (Wilki, Nietoperze, Enty, Gobliny)
                'gold' => 100,
                'melee' => ['Gobliński Sztylet' => 3, 'Słaby Kryształ Many' => 1],
                'ranged' => ['Prastara Kora' => 3, 'Błona Skrzydła' => 2],
                'magic' => ['Magiczny Mech' => 4, 'Słaby Kryształ Many' => 2],
                'heavy' => ['Wilczy Kieł' => 4, 'Prastara Kora' => 2],
                'light' => ['Błona Skrzydła' => 4, 'Wilczy Kieł' => 2],
                'jewelry' => ['Kawałek Poroża' => 1, 'Mroczne Zioło' => 3]
            ],
            15 => [ // Głęboki Las / Mix
                'gold' => 450,
                'melee' => ['Kawałek Poroża' => 2, 'Gobliński Sztylet' => 5],
                'ranged' => ['Prastara Kora' => 5, 'Błona Skrzydła' => 5],
                'magic' => ['Słaby Kryształ Many' => 5, 'Magiczny Mech' => 4],
                'heavy' => ['Prastara Kora' => 5, 'Wilczy Kieł' => 5],
                'light' => ['Błona Skrzydła' => 5, 'Gobliński Sztylet' => 3],
                'jewelry' => ['Kawałek Poroża' => 2, 'Słaby Kryształ Many' => 3]
            ],
            25 => [ // Stare Ruiny (Szkielety, Duchy, Licz)
                'gold' => 1200,
                'melee' => ['Strzaskana Kość' => 5, 'Odłamek Ruin' => 2],
                'ranged' => ['Zardzewiały Grot' => 5, 'Zardzewiała Moneta' => 3],
                'magic' => ['Ektoplazma' => 4, 'Fragment Całunu' => 1, 'Pył Grobowy' => 2],
                'heavy' => ['Strzaskana Kość' => 6, 'Zardzewiała Moneta' => 2],
                'light' => ['Zardzewiały Grot' => 4, 'Ektoplazma' => 3],
                'jewelry' => ['Przeklęty Onyks' => 1, 'Pył Grobowy' => 4]
            ],
            35 => [ // Jaskinia Trolli (Trolle, Ogry, Nietoperze)
                'gold' => 2800,
                'melee' => ['Ogrzy Pazur' => 4, 'Ruda Żelaza' => 5],
                'ranged' => ['Gruba Skóra Trolla' => 4, 'Krew Jaskiniowca' => 3],
                'magic' => ['Szamański Koralik' => 3, 'Błyszczący Grzyb' => 4],
                'heavy' => ['Gruba Skóra Trolla' => 6, 'Ruda Żelaza' => 4],
                'light' => ['Krew Jaskiniowca' => 5, 'Śluz Jaskiniowy' => 3],
                'jewelry' => ['Odłamek Skarbu' => 1, 'Błyszczący Grzyb' => 5]
            ],
            45 => [ // Pustkowia Orków (Orkowie)
                'gold' => 6000,
                'melee' => ['Złamany Kieł Orka' => 5, 'Kamień Szlifierski' => 3],
                'ranged' => ['Twarde Rzemienie' => 5, 'Wyschnięty Krzew' => 3],
                'magic' => ['Skrwawiony Totem' => 3, 'Skóra Pustynna' => 4],
                'heavy' => ['Szczątki Pancerza' => 3, 'Złamany Kieł Orka' => 5],
                'light' => ['Twarde Rzemienie' => 4, 'Skóra Pustynna' => 5],
                'jewelry' => ['Symbol Wodza' => 1, 'Kamień Szlifierski' => 4]
            ],
            55 => [ // Bagna Grozy (Topielce, Wiedźmy, Hydry)
                'gold' => 15000,
                'melee' => ['Zgniłe Mięso' => 6, 'Toksyczny Śluz' => 3],
                'ranged' => ['Błotnisty Korzeń' => 5, 'Mętna Woda' => 4],
                'magic' => ['Wiedźmi Amulet' => 3, 'Bagienne Zioło' => 5],
                'heavy' => ['Łuska Hydry' => 3, 'Skamieniały Torf' => 4],
                'light' => ['Zgniłe Mięso' => 4, 'Łuska Hydry' => 2],
                'jewelry' => ['Skamieniały Torf' => 3, 'Wiedźmi Amulet' => 2]
            ],
            65 => [ // Góry Cienia (Wilki, Golemy, Harpie)
                'gold' => 35000,
                'melee' => ['Odłamek Bazaltu' => 5, 'Górska Ruda Miedzi' => 5],
                'ranged' => ['Pióro Harpii' => 5, 'Mroczne Futro' => 3],
                'magic' => ['Zniszczona Księga Magii' => 3, 'Kryształ Cienia' => 2],
                'heavy' => ['Odłamek Bazaltu' => 6, 'Mroczne Futro' => 4],
                'light' => ['Pióro Harpii' => 4, 'Kryształ Cienia' => 2],
                'jewelry' => ['Popiół Wulkaniczny' => 4, 'Łuska Smoka Cienia' => 1]
            ],
            75 => [ // Leże Smoka Cienia (Smoki)
                'gold' => 80000,
                'melee' => ['Łuska Smoka Cienia' => 3, 'Górska Ruda Miedzi' => 10],
                'ranged' => ['Łuska Smoka Cienia' => 2, 'Pióro Harpii' => 6],
                'magic' => ['Kryształ Cienia' => 5, 'Zniszczona Księga Magii' => 5],
                'heavy' => ['Łuska Smoka Cienia' => 4, 'Odłamek Bazaltu' => 5],
                'light' => ['Łuska Smoka Cienia' => 2, 'Kryształ Cienia' => 3],
                'jewelry' => ['Łuska Smoka Cienia' => 2, 'Popiół Wulkaniczny' => 6]
            ],
            85 => [ // Wieża Magów (Żywiołaki, Magowie)
                'gold' => 120000,
                'melee' => ['Żar Płomieni' => 5, 'Runiczny Kamień' => 4],
                'ranged' => ['Szkło Iluzji' => 5, 'Eteryczny Pył' => 3],
                'magic' => ['Magiczny Rdzeń' => 4, 'Czysta Mana' => 5],
                'heavy' => ['Runiczny Kamień' => 6, 'Czysty Pergamin' => 4],
                'light' => ['Szkło Iluzji' => 4, 'Czysty Pergamin' => 3],
                'jewelry' => ['Odłamek Kostura Arcymaga' => 1, 'Czysta Mana' => 5]
            ],
            95 => [ // Skażone Miasto (Zgnilizna, Nieumarli)
                'gold' => 250000,
                'melee' => ['Przeklęta Stal' => 5, 'Skażony Metal' => 5],
                'ranged' => ['Jad Pająka Plagi' => 5, 'Skażona Kość' => 3],
                'magic' => ['Fiolka Zgnilizny' => 4, 'Popioły Miasta' => 5],
                'heavy' => ['Przeklęta Stal' => 6, 'Skażona Kość' => 4],
                'light' => ['Jad Pająka Plagi' => 4, 'Skażony Metal' => 3],
                'jewelry' => ['Czarny Kamień Dusz' => 1, 'Esencja Zniszczenia' => 1]
            ],
            99 => [ // Domena Zniszczenia (Apokalipsa)
                'gold' => 500000,
                'melee' => ['Esencja Zniszczenia' => 3, 'Przeklęta Stal' => 10],
                'ranged' => ['Esencja Zniszczenia' => 2, 'Jad Pająka Plagi' => 10],
                'magic' => ['Czarny Kamień Dusz' => 3, 'Fiolka Zgnilizny' => 10],
                'heavy' => ['Esencja Zniszczenia' => 2, 'Skażony Metal' => 15],
                'light' => ['Czarny Kamień Dusz' => 2, 'Skażona Kość' => 15],
                'jewelry' => ['Esencja Zniszczenia' => 2, 'Czarny Kamień Dusz' => 2, 'Popioły Miasta' => 20]
            ]
        ];

        $generatedCount = 0;

        foreach ($tiers as $level => $config) {
            $equipments = ItemTemplate::where('level_requirement', $level)
                ->whereIn('type', ['weapon', 'armor', 'accessory'])
                ->get();

            foreach ($equipments as $item) {
                
                // Znajdź grupę, do której należy dany przedmiot na podstawie nazwy bazy
                // Wiemy, że w ItemTemplateSeeder nazwy są w formacie "Prefiks Baza" np. "Prymitywny Miecz", ale tutaj użyliśmy pełnych unikalnych nazw (np. "Miecz Pana Zniszczenia")
                // Najprościej przyporządkować po słowach kluczach lub typach.
                
                $groupName = 'melee'; // domyślnie
                $nameLower = strtolower($item->name);

                if (str_contains($nameLower, 'łuk') || str_contains($nameLower, 'sztylet')) {
                    $groupName = 'ranged';
                } elseif (str_contains($nameLower, 'dzwon') || str_contains($nameLower, 'różdż') || str_contains($nameLower, 'kostur') || str_contains($nameLower, 'kaptur') || str_contains($nameLower, 'szata') || str_contains($nameLower, 'mokasyny') || str_contains($nameLower, 'trzewiki') || str_contains($nameLower, 'lewita') || str_contains($nameLower, 'eteru')) {
                    $groupName = 'magic';
                } elseif (str_contains($nameLower, 'hełm') || str_contains($nameLower, 'zbroja') || str_contains($nameLower, 'pancerz') || str_contains($nameLower, 'korona') || str_contains($nameLower, 'kolczuga') || str_contains($nameLower, 'buciska') || str_contains($nameLower, 'sabatony') || str_contains($nameLower, 'deptania') || str_contains($nameLower, 'ciężkie')) {
                    $groupName = 'heavy';
                } elseif (str_contains($nameLower, 'maska') || str_contains($nameLower, 'skórznia') || str_contains($nameLower, 'płaszcz') || str_contains($nameLower, 'ciche') || str_contains($nameLower, 'skoku') || str_contains($nameLower, 'trucizny')) {
                    $groupName = 'light';
                } elseif ($item->type === 'accessory') {
                    $groupName = 'jewelry';
                }

                $requirements = $config[$groupName] ?? [];
                
                $ingredients = [];
                $hasMissingMaterials = false;

                foreach ($requirements as $matName => $quantity) {
                    if (!isset($materials[$matName])) {
                        $this->command->warn("Brakuje materiału w bazie: {$matName} (dla {$item->name})");
                        $hasMissingMaterials = true;
                        continue;
                    }

                    $ingredients[] = [
                        'template_id' => $materials[$matName]->id,
                        'quantity' => $quantity
                    ];
                }

                if (!$hasMissingMaterials && !empty($ingredients)) {
                    ItemRecipe::create([
                        'id' => (string) Str::ulid(),
                        'result_item_template_id' => $item->id,
                        'ingredients' => $ingredients,
                        'gold_cost' => $config['gold']
                    ]);
                    $generatedCount++;
                }
            }
        }

        $this->command->info("Wygenerowano {$generatedCount} unikalnych, różnorodnych receptur dla rzemiosła!");
    }
}

