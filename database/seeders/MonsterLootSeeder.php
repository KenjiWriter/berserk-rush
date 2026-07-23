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
                    'Pająk Leśny' => ['Mroczne Zioło'],
                    'Suchodrzew' => ['Prastara Kora'],
                    'Zdziczały Dzik' => ['Wilczy Kieł'],
                    'Goblin Zwiadowca' => ['Gobliński Sztylet'],
                    'Strażnik Puszczy' => ['Prastara Kora'],
                    'Król Lasu' => ['Prastara Kora']
                ]
            ],
            'Stare Ruiny' => [
                'general' => ['Pył Grobowy', 'Zardzewiała Moneta', 'Odłamek Ruin'],
                'boss_general' => ['Przeklęty Onyks'],
                'monsters' => [
                    'Szkielet Wojownik' => ['Strzaskana Kość'],
                    'Mroczny Kultysta' => ['Pył Grobowy'],
                    'Duch Strażnik' => ['Ektoplazma'],
                    'Ghul' => ['Strzaskana Kość'],
                    'Upiorny Łucznik' => ['Zardzewiały Grot'],
                    'Kamienny Gargulec' => ['Odłamek Ruin'],
                    'Władca Krypty' => ['Strzaskana Kość'],
                    'Licz Cieni' => ['Fragment Całunu']
                ]
            ],
            'Jaskinia Trolli' => [
                'general' => ['Ruda Żelaza', 'Błyszczący Grzyb', 'Śluz Jaskiniowy'],
                'boss_general' => ['Odłamek Skarbu'],
                'monsters' => [
                    'Troll Paskudnik' => ['Gruba Skóra Trolla'],
                    'Pełzacz Jaskiniowy' => ['Śluz Jaskiniowy'],
                    'Troll Szaman' => ['Gruba Skóra Trolla', 'Szamański Koralik'],
                    'Ogr Rozłupywacz' => ['Ogrzy Pazur'],
                    'Jaskiniowy Nietoperz Alfa' => ['Krew Jaskiniowca'],
                    'Troll Scalony' => ['Gruba Skóra Trolla'],
                    'Starożytny Ogr' => ['Ogrzy Pazur'],
                    'Król Trolli' => ['Gruba Skóra Trolla']
                ]
            ],
            'Pustkowia Orków' => [
                'general' => ['Skóra Pustynna', 'Wyschnięty Krzew', 'Kamień Szlifierski'],
                'boss_general' => ['Szczątki Pancerza'],
                'monsters' => [
                    'Orczy Zwiad' => ['Złamany Kieł Orka'],
                    'Pustynny Skorpion' => ['Skóra Pustynna'],
                    'Ork Berserker' => ['Złamany Kieł Orka'],
                    'Ork Topornik' => ['Złamany Kieł Orka'],
                    'Szaman Krwi' => ['Skrwawiony Totem'],
                    'Dowódca Watahy' => ['Twarde Rzemienie'],
                    'Niszczyciel Pustkowi' => ['Twarde Rzemienie'],
                    'Wódz Orków' => ['Symbol Wodza']
                ]
            ],
            'Bagna Grozy' => [
                'general' => ['Bagienne Zioło', 'Mętna Woda', 'Toksyczny Śluz'],
                'boss_general' => ['Skamieniały Torf'],
                'monsters' => [
                    'Topielec' => ['Zgniłe Mięso'],
                    'Błotny Bazyliszek' => ['Toksyczny Śluz'],
                    'Wiedźmia Straż' => ['Wiedźmi Amulet'],
                    'Drzewiec Plugawy' => ['Błotnisty Korzeń'],
                    'Widmo Bagien' => ['Zgniłe Mięso'],
                    'Hydra Bagienna' => ['Łuska Hydry'],
                    'Królowa Wiedźm' => ['Wiedźmi Amulet'],
                    'Moczarowy Behemot' => ['Łuska Hydry']
                ]
            ],
            'Góry Cienia' => [
                'general' => ['Kryształ Cienia', 'Górska Ruda Miedzi'],
                'boss_general' => ['Popiół Wulkaniczny'],
                'monsters' => [
                    'Wilk Cienia' => ['Mroczne Futro'],
                    'Mroczny Gryf' => ['Pióro Harpii'],
                    'Golem Bazaltowy' => ['Odłamek Bazaltu'],
                    'Harpia' => ['Pióro Harpii'],
                    'Cieniowy Gargulec' => ['Odłamek Bazaltu'],
                    'Wędrowny Czarownik' => ['Zniszczona Księga Magii'],
                    'Władca Cieni' => ['Kryształ Cienia'],
                    'Smok Cienia' => ['Łuska Smoka Cienia']
                ]
            ],
            'Wieża Magów' => [
                'general' => ['Eteryczny Pył', 'Czysta Mana', 'Czysty Pergamin'],
                'boss_general' => ['Odłamek Kostura Arcymaga'],
                'monsters' => [
                    'Adepci Run' => ['Runiczny Kamień'],
                    'Żywiołak Lodu' => ['Eteryczny Pył'],
                    'Strażnik Arkanów' => ['Magiczny Rdzeń'],
                    'Żywiołak Płomieni' => ['Żar Płomieni'],
                    'Runiczny Konstrukt' => ['Runiczny Kamień'],
                    'Mistrz Iluzji' => ['Szkło Iluzji'],
                    'Wielki Inkwizytor' => ['Czysta Mana'],
                    'Arcymag' => []
                ]
            ],
            'Skażone Miasto' => [
                'general' => ['Skażony Metal', 'Popioły Miasta'],
                'boss_general' => ['Czarny Kamień Dusz'],
                'monsters' => [
                    'Zmutowany Nieumarły' => ['Skażona Kość'],
                    'Plagowy Kat' => ['Przeklęta Stal'],
                    'Czarownica Zgnilizny' => ['Fiolka Zgnilizny'],
                    'Zbezczeszczony Golem' => ['Skażony Metal'],
                    'Pająk Plagi' => ['Jad Pająka Plagi'],
                    'Rycerz Skazy' => ['Przeklęta Stal'],
                    'Książę Zniszczenia' => ['Esencja Zniszczenia'],
                    'Pan Zniszczenia' => ['Esencja Zniszczenia']
                ]
            ]
        ];

        // Pobierz wszystkie przedmioty dla szybkiego wyszukiwania
        $itemTemplates = ItemTemplate::all();
        $materialsByName = $itemTemplates->where('type', 'material')->keyBy('name');
        $equipments = $itemTemplates->whereIn('type', ['weapon', 'armor', 'accessory']);
        $consumables = $itemTemplates->where('type', 'consumable');

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

                $monsterRank = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
                if (in_array($monsterRank, ['boss', 'worldboss']) && isset($mapConfig['boss_general'])) {
                    $possibleDrops = array_merge($possibleDrops, $mapConfig['boss_general']);
                }

                // Dodanie Złota
                LootTableEntry::firstOrCreate([
                    'loot_table_id' => $lootTable->id,
                    'reward_type' => 'gold',
                ], [
                    'weight' => 50,
                    'min_qty' => $monster->level * 2,
                    'max_qty' => max($monster->level * 5, 10)
                ]);

                // Przypisz wpisy LootTableEntries dla każdego materiału
                foreach ($possibleDrops as $dropName) {
                    $template = $materialsByName->get($dropName);

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
                        'max_qty' => in_array($monsterRank, ['boss', 'worldboss']) ? 3 : 1
                    ]);
                }

                // Dodanie Sprzętu z odpowiedniego Tieru (Equipments)
                $bestTierLevel = $equipments->where('level_requirement', '<=', $monster->level)->max('level_requirement');
                if ($bestTierLevel) {
                    $tierEquipments = $equipments->where('level_requirement', $bestTierLevel);
                    foreach($tierEquipments as $equip) {
                        LootTableEntry::firstOrCreate([
                            'loot_table_id' => $lootTable->id,
                            'reward_type' => 'item',
                            'ref_ulid' => $equip->id,
                        ], [
                            'weight' => in_array($monsterRank, ['boss', 'worldboss']) ? 3 : 1,
                            'min_qty' => 1,
                            'max_qty' => 1
                        ]);
                    }
                }

                // Dodanie Mikstur (Consumables)
                $availableConsumables = $consumables->where('level_requirement', '<=', $monster->level);
                foreach($availableConsumables as $cons) {
                    LootTableEntry::firstOrCreate([
                        'loot_table_id' => $lootTable->id,
                        'reward_type' => 'item',
                        'ref_ulid' => $cons->id,
                    ], [
                        'weight' => 5,
                        'min_qty' => 1,
                        'max_qty' => 2
                    ]);
                }
            }
        }

        $this->command->info('MonsterLootSeeder completed.');
    }
}
