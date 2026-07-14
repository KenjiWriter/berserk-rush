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
                    'Wilk Leśny' => ['Wilczy Kieł'],
                    'Nietoperz Jaskiniowy' => ['Błona Skrzydła'],
                    'Suchodrzew' => ['Prastara Kora'],
                    'Goblin Zwiadowca' => ['Gobliński Sztylet'],
                    'Król Lasu' => ['Prastara Kora']
                ]
            ],
            'Stare Ruiny' => [
                'general' => ['Pył Grobowy', 'Zardzewiała Moneta', 'Odłamek Ruin'],
                'boss_general' => ['Przeklęty Onyks'],
                'monsters' => [
                    'Szkielet Wojownik' => ['Strzaskana Kość'],
                    'Duch Strażnik' => ['Ektoplazma'],
                    'Ghul' => ['Strzaskana Kość'],
                    'Upiorny Łucznik' => ['Zardzewiały Grot'],
                    'Licz Cieni' => ['Fragment Całunu']
                ]
            ],
            'Jaskinia Trolli' => [
                'general' => ['Ruda Żelaza', 'Błyszczący Grzyb', 'Śluz Jaskiniowy'],
                'boss_general' => ['Odłamek Skarbu'],
                'monsters' => [
                    'Troll Paskudnik' => ['Gruba Skóra Trolla'],
                    'Troll Szaman' => ['Gruba Skóra Trolla', 'Szamański Koralik'],
                    'Ogr Rozłupywacz' => ['Ogrzy Pazur'],
                    'Jaskiniowy Nietoperz Alfa' => ['Krew Jaskiniowca'],
                    'Król Trolli' => ['Gruba Skóra Trolla']
                ]
            ],
            'Pustkowia Orków' => [
                'general' => ['Skóra Pustynna', 'Wyschnięty Krzew', 'Kamień Szlifierski'],
                'boss_general' => ['Szczątki Pancerza'],
                'monsters' => [
                    'Orczy Zwiad' => ['Złamany Kieł Orka'],
                    'Ork Berserker' => ['Złamany Kieł Orka'],
                    'Szaman Krwi' => ['Skrwawiony Totem'],
                    'Dowódca Watahy' => ['Twarde Rzemienie'],
                    'Wódz Orków' => ['Symbol Wodza']
                ]
            ],
            'Bagna Grozy' => [
                'general' => ['Bagienne Zioło', 'Mętna Woda', 'Toksyczny Śluz'],
                'boss_general' => ['Skamieniały Torf'],
                'monsters' => [
                    'Topielec' => ['Zgniłe Mięso'],
                    'Wiedźmia Straż' => ['Wiedźmi Amulet'],
                    'Drzewiec Plugawy' => ['Błotnisty Korzeń'],
                    'Hydra Bagienna' => ['Łuska Hydry'],
                    'Moczarowy Behemot' => ['Łuska Hydry']
                ]
            ],
            'Góry Cienia' => [
                'general' => ['Kryształ Cienia', 'Górska Ruda Miedzi'],
                'boss_general' => ['Popiół Wulkaniczny'],
                'monsters' => [
                    'Wilk Cienia' => ['Mroczne Futro'],
                    'Golem Bazaltowy' => ['Odłamek Bazaltu'],
                    'Harpia' => ['Pióro Harpii'],
                    'Wędrowny Czarownik' => ['Zniszczona Księga Magii'],
                    'Smok Cienia' => ['Łuska Smoka Cienia']
                ]
            ],
            'Wieża Magów' => [
                'general' => ['Eteryczny Pył', 'Czysta Mana', 'Czysty Pergamin'],
                'boss_general' => ['Odłamek Kostura Arcymaga'],
                'monsters' => [
                    'Adepci Run' => ['Runiczny Kamień'],
                    'Strażnik Arkanów' => ['Magiczny Rdzeń'],
                    'Żywiołak Płomieni' => ['Żar Płomieni'],
                    'Mistrz Iluzji' => ['Szkło Iluzji'],
                    'Arcymag' => []
                ]
            ],
            'Skażone Miasto' => [
                'general' => ['Skażony Metal', 'Popioły Miasta'],
                'boss_general' => ['Czarny Kamień Dusz'],
                'monsters' => [
                    'Zmutowany Nieumarły' => ['Skażona Kość'],
                    'Czarownica Zgnilizny' => ['Fiolka Zgnilizny'],
                    'Pająk Plagi' => ['Jad Pająka Plagi'],
                    'Rycerz Skazy' => ['Przeklęta Stal'],
                    'Pan Zniszczenia' => ['Esencja Zniszczenia']
                ]
            ]
        ];

        // Pobierz szablony materiałów (ItemTemplate) dla szybkiego wyszukiwania
        $itemTemplates = ItemTemplate::where('type', 'material')->get()->keyBy('name');

        foreach ($mapsData as $mapName => $mapConfig) {
            foreach ($mapConfig['monsters'] as $monsterName => $specificDrops) {
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

                // Skompletuj wszystkie przedmioty, które mogą spaść
                $possibleDrops = array_merge([], $mapConfig['general'], $specificDrops);

                if ($monster->rank === 'worldboss' && isset($mapConfig['boss_general'])) {
                    $possibleDrops = array_merge($possibleDrops, $mapConfig['boss_general']);
                }

                // Przypisz wpisy LootTableEntries dla każdego przedmiotu
                foreach ($possibleDrops as $dropName) {
                    $template = $itemTemplates->get($dropName);

                    if (!$template) {
                        $this->command->warn("Nie znaleziono przedmiotu {$dropName} w bazie.");
                        continue;
                    }

                    // Sprawdź czy już istnieje wpis w tej tabeli, jeśli nie, dodaj
                    LootTableEntry::firstOrCreate([
                        'loot_table_id' => $lootTable->id,
                        'reward_type' => 'material',
                        'ref_ulid' => $template->id,
                    ], [
                        'weight' => in_array($dropName, $specificDrops) ? 20 : (in_array($dropName, $mapConfig['boss_general'] ?? []) ? 5 : 10),
                        'min_qty' => 1,
                        'max_qty' => $monster->rank === 'worldboss' ? 3 : 1
                    ]);
                }
            }
        }

        $this->command->info('MonsterLootSeeder completed.');
    }
}
