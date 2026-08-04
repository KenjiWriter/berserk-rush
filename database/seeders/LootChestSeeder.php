<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;

class LootChestSeeder extends Seeder
{
    public function run(): void
    {
        $chestsConfig = [
            [
                'name' => 'Skrzynia Ksiąg Umiejętności',
                'desc' => 'Tajemnicza skrzynia zawierająca wiedzę o sztukach walki. Po otwarciu przyznaje losową Księgę Umiejętności.',
                'icon' => 'skrzynia-ksiag-umiejetnosci.png',
                'min_level' => 1,
                'materials' => [
                    ['name' => 'Księga Walki Mieczem', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Sztuki Topora', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Łucznictwa', 'weight' => 14, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Tajemnic Różdżki', 'weight' => 14, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Magii Dzwonu', 'weight' => 14, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Mistrzostwa Sztyletów', 'weight' => 14, 'min_qty' => 1, 'max_qty' => 1],
                    ['name' => 'Księga Ogólnych Technik', 'weight' => 14, 'min_qty' => 1, 'max_qty' => 1],
                ]
            ],
            [
                'name' => 'Skrzynia Mrocznego Lasu',
                'desc' => 'Pradawna drewniana skrzynia o owiniętych pnączach. Zawiera materiały i ulepszacze z Mrocznego Lasu.',
                'icon' => 'skrzynia-lesna.png',
                'min_level' => 1,
                'materials' => [
                    ['name' => 'Wilczy Kieł', 'weight' => 20, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Błona Skrzydła', 'weight' => 20, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Prastara Kora', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Gobliński Sztylet', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Mroczne Zioło', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Magiczny Mech', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Słaby Kryształ Many', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Kawałek Poroża', 'weight' => 10, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Starych Ruin',
                'desc' => 'Masywna kuta skrzynia z zamczyskiem. Zawiera rzadkie składniki z krypt i starych ruin.',
                'icon' => 'skrzynia-ruin.png',
                'min_level' => 10,
                'materials' => [
                    ['name' => 'Strzaskana Kość', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Ektoplazma', 'weight' => 20, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Zardzewiały Grot', 'weight' => 20, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Pył Grobowy', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Zardzewiała Moneta', 'weight' => 20, 'min_qty' => 3, 'max_qty' => 8],
                    ['name' => 'Odłamek Ruin', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Przeklęty Onyks', 'weight' => 10, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Fragment Całunu', 'weight' => 8, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Jaskini Trolli',
                'desc' => 'Ciężka kamienna skrzynia trolli. Zawiera skóry, pazury i rury jaskiniowe.',
                'icon' => 'skrzynia-trolli.png',
                'min_level' => 25,
                'materials' => [
                    ['name' => 'Gruba Skóra Trolla', 'weight' => 25, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Szamański Koralik', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Ogrzy Pazur', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Krew Jaskiniowca', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Ruda Żelaza', 'weight' => 30, 'min_qty' => 3, 'max_qty' => 6],
                    ['name' => 'Błyszczący Grzyb', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Śluz Jaskiniowy', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Odłamek Skarbu', 'weight' => 12, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Pustkowi Orków',
                'desc' => 'Okuta żelazem skrzynia orkowego wojownika. Zawiera wojenne totemki i rzemienie pustkowi.',
                'icon' => 'skrzynia-orkow.png',
                'min_level' => 35,
                'materials' => [
                    ['name' => 'Złamany Kieł Orka', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Skrwawiony Totem', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Twarde Rzemienie', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Skóra Pustynna', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Wyschnięty Krzew', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Kamień Szlifierski', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Szczątki Pancerza', 'weight' => 12, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Symbol Wodza', 'weight' => 8, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Bagien Grozy',
                'desc' => 'Skrzynia porośnięta mchem i zgnilizną. Kryje jad hydry, amulety wiedźm i toksyny.',
                'icon' => 'skrzynia-bagien.png',
                'min_level' => 50,
                'materials' => [
                    ['name' => 'Zgniłe Mięso', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Toksyczny Śluz', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Wiedźmi Amulet', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Błotnisty Korzeń', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Łuska Hydry', 'weight' => 12, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Bagienne Zioło', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Mętna Woda', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Skamieniały Torf', 'weight' => 10, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Gór Cienia',
                'desc' => 'Kryształowa skrzynia wydobyta z wulkanicznych czeluści Gór Cienia. Skrywa kryształy i smocze łuski.',
                'icon' => 'skrzynia-gor.png',
                'min_level' => 65,
                'materials' => [
                    ['name' => 'Mroczne Futro', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Pióro Harpii', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Odłamek Bazaltu', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Zniszczona Księga Magii', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Kryształ Cienia', 'weight' => 18, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Górska Ruda Miedzi', 'weight' => 20, 'min_qty' => 3, 'max_qty' => 6],
                    ['name' => 'Popiół Wulkaniczny', 'weight' => 12, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Łuska Smoka Cienia', 'weight' => 8, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Wieży Magów',
                'desc' => 'Eteryczna szkatuła nasączona energią arkanów. Zawiera czystą manę, magnez i kamienie runiczne.',
                'icon' => 'skrzynia-magow.png',
                'min_level' => 75,
                'materials' => [
                    ['name' => 'Runiczny Kamień', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Eteryczny Pył', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Magiczny Rdzeń', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Żar Płomieni', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Szkło Iluzji', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Czysta Mana', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Czysty Pergamin', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Odłamek Kostura Arcymaga', 'weight' => 8, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
            [
                'name' => 'Skrzynia Skażonego Miasta',
                'desc' => 'Przeklęta kufra potępienia spowita mroczną aurą. Przepojona najrzadszymi materiałami chaosu.',
                'icon' => 'skrzynia-skazy.png',
                'min_level' => 85,
                'materials' => [
                    ['name' => 'Skażona Kość', 'weight' => 25, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Przeklęta Stal', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Fiolka Zgnilizny', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 4],
                    ['name' => 'Skażony Metal', 'weight' => 20, 'min_qty' => 3, 'max_qty' => 6],
                    ['name' => 'Jad Pająka Plagi', 'weight' => 15, 'min_qty' => 1, 'max_qty' => 3],
                    ['name' => 'Popioły Miasta', 'weight' => 20, 'min_qty' => 2, 'max_qty' => 5],
                    ['name' => 'Czarny Kamień Dusz', 'weight' => 10, 'min_qty' => 1, 'max_qty' => 2],
                    ['name' => 'Esencja Zniszczenia', 'weight' => 8, 'min_qty' => 1, 'max_qty' => 2],
                ]
            ],
        ];

        // Fetch all item templates by name for quick reference
        $allTemplates = ItemTemplate::all()->keyBy('name');

        foreach ($chestsConfig as $cConfig) {
            $lootTableName = 'chest_' . Str::slug($cConfig['name']) . '_loot';

            // 1. Create or Update ItemTemplate for the Chest
            $chestTemplate = ItemTemplate::where('name', $cConfig['name'])->first();
            if (!$chestTemplate) {
                $chestTemplate = ItemTemplate::create([
                    'id' => (string) Str::ulid(),
                    'name' => $cConfig['name'],
                    'type' => 'consumable',
                    'sub_type' => 'chest',
                    'slot' => 'consumable',
                    'icon' => $cConfig['icon'],
                    'description' => $cConfig['desc'],
                    'level_requirement' => $cConfig['min_level'],
                    'base_stats' => [
                        'loot_table' => $lootTableName
                    ]
                ]);
            } else {
                $chestTemplate->update([
                    'type' => 'consumable',
                    'sub_type' => 'chest',
                    'slot' => 'consumable',
                    'icon' => $cConfig['icon'],
                    'description' => $cConfig['desc'],
                    'level_requirement' => $cConfig['min_level'],
                    'base_stats' => [
                        'loot_table' => $lootTableName
                    ]
                ]);
            }

            // 2. Create Loot Table for Chest
            $lootTable = LootTable::firstOrCreate(
                ['name' => $lootTableName],
                ['description' => 'Drop pool for ' . $cConfig['name']]
            );

            LootTableEntry::where('loot_table_id', $lootTable->id)->delete();

            // 3. Add Material entries to Chest Loot Table
            foreach ($cConfig['materials'] as $matInfo) {
                $matTemplate = $allTemplates->get($matInfo['name']);
                if (!$matTemplate) {
                    $this->command->warn("Material '{$matInfo['name']}' not found when seeding chest {$cConfig['name']}");
                    continue;
                }

                LootTableEntry::create([
                    'loot_table_id' => $lootTable->id,
                    'reward_type' => 'material',
                    'ref_ulid' => $matTemplate->id,
                    'min_qty' => $matInfo['min_qty'],
                    'max_qty' => $matInfo['max_qty'],
                    'weight' => $matInfo['weight'],
                ]);
            }
        }

        $this->command->info('LootChestSeeder completed. Seeded 8 loot chests and their reward pools.');
    }
}
