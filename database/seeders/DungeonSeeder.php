<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\Map;

class DungeonSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Key Item Templates if missing
        $keysData = [
            ['id' => '01k4jpx94j70x2vv10b835key1', 'name' => 'Klucz Katakumb', 'min_lvl' => 12, 'desc' => 'Zardzewiały klucz do Zapomnianych Katakumb.'],
            ['id' => '01k4jpx94j70x2vv10b835key2', 'name' => 'Klucz Krypty', 'min_lvl' => 30, 'desc' => 'Runiczny klucz do Krypty Przeklętych.'],
            ['id' => '01k4jpx94j70x2vv10b835key3', 'name' => 'Klucz Pustkowi', 'min_lvl' => 50, 'desc' => 'Ciężki żelazny klucz do Pustkowi Zarazy.'],
            ['id' => '01k4jpx94j70x2vv10b835key4', 'name' => 'Klucz Cytadeli', 'min_lvl' => 70, 'desc' => 'Magiczny klucz ze stopu cienia do Cytadeli Cienia.'],
            ['id' => '01k4jpx94j70x2vv10b835key5', 'name' => 'Klucz Otchłani', 'min_lvl' => 88, 'desc' => 'Smocze godło otwierające bramy Otchłani Zniszczenia.'],
        ];

        foreach ($keysData as $kd) {
            ItemTemplate::updateOrCreate(
                ['id' => $kd['id']],
                [
                    'name' => $kd['name'],
                    'type' => 'material',
                    'sub_type' => 'key',
                    'level_requirement' => $kd['min_lvl'],
                    'description' => $kd['desc'],
                    'icon' => Str::slug($kd['name']) . '.png',
                    'rarity_weights' => ['common' => 0, 'uncommon' => 70, 'rare' => 30],
                ]
            );
        }

        // Helper to find item ID by name
        $getItemUlid = fn(string $name) => ItemTemplate::where('name', $name)->first()?->id;

        // Clear existing dungeons and their stage records
        DungeonStage::query()->delete();
        Dungeon::query()->delete();

        if (Map::count() === 0) {
            $this->call(MapSeeder::class);
        }
        if (ItemTemplate::where('type', 'egg')->count() === 0) {
            $this->call(PetSeeder::class);
        }
        if (ItemTemplate::where('type', 'material')->where('sub_type', '!=', 'key')->count() === 0) {
            $this->call(MaterialItemSeeder::class);
        }

        $defaultMapId = Map::first()?->id ?? 1;

        // Configuration of 5 Dungeons
        $dungeonsConfig = [
            [
                'name' => 'Zapomniane Katakumby',
                'min_level' => 12,
                'key_id' => '01k4jpx94j70x2vv10b835key1',
                'boss_loot' => [
                    // Skill Books Chest
                    ['reward_type' => 'item', 'ref_name' => 'Skrzynia Ksiąg Umiejętności', 'min_qty' => 1, 'max_qty' => 2, 'weight' => 180],
                    // Scrolls (consumables)
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Umiejętności', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 20],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Atrybutów', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 20],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Areny Walki', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 30],
                    // Upgrade materials (increased drop quantities)
                    ['reward_type' => 'material', 'ref_name' => 'Wilczy Kieł', 'min_qty' => 3, 'max_qty' => 8, 'weight' => 200],
                    ['reward_type' => 'material', 'ref_name' => 'Prastara Kora', 'min_qty' => 2, 'max_qty' => 5, 'weight' => 150],
                    ['reward_type' => 'material', 'ref_name' => 'Pył Grobowy', 'min_qty' => 3, 'max_qty' => 7, 'weight' => 150],
                    ['reward_type' => 'material', 'ref_name' => 'Strzaskana Kość', 'min_qty' => 3, 'max_qty' => 9, 'weight' => 100],
                ],
                'stages' => [
                    [
                        'order' => 1, 'type' => 'single_mob', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Szkielet Katakumb', 'level' => 12, 'rank' => 'regular', 'stats' => ['hp' => 350, 'atk' => 50, 'def' => 10, 'agi' => 8], 'avatar' => 'katakumby-szkielet.png']
                    ],
                    [
                        'order' => 2, 'type' => 'group_mob', 'count' => 2, 'max_turns' => 50,
                        'monster' => ['name' => 'Kultysta Cienia', 'level' => 12, 'rank' => 'regular', 'stats' => ['hp' => 300, 'atk' => 44, 'def' => 9, 'agi' => 9], 'avatar' => 'kultysta-cienia.png']
                    ],
                    [
                        'order' => 3, 'type' => 'gate', 'count' => 1, 'max_turns' => 10,
                        'monster' => ['name' => 'Runiczne Wrota Katakumb', 'level' => 12, 'rank' => 'regular', 'stats' => ['hp' => 700, 'atk' => 0, 'def' => 15, 'agi' => 0], 'avatar' => 'katakumby-wrota.png']
                    ],
                    [
                        'order' => 4, 'type' => 'boss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Strażnik Cienia', 'level' => 15, 'rank' => 'boss', 'stats' => ['hp' => 1700, 'atk' => 105, 'def' => 25, 'agi' => 18], 'avatar' => 'straznik-cienia.png']
                    ],
                ]
            ],
            [
                'name' => 'Krypta Przeklętych',
                'min_level' => 30,
                'key_id' => '01k4jpx94j70x2vv10b835key2',
                'boss_loot' => [
                    // Skill Books Chest
                    ['reward_type' => 'item', 'ref_name' => 'Skrzynia Ksiąg Umiejętności', 'min_qty' => 1, 'max_qty' => 2, 'weight' => 200],
                    // Scrolls (consumables)
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Umiejętności', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 40],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Atrybutów', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 40],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Pełnego Resetu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 15],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Areny Walki', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 50],
                    // Upgrade materials (increased quantities)
                    ['reward_type' => 'material', 'ref_name' => 'Ektoplazma', 'min_qty' => 3, 'max_qty' => 8, 'weight' => 200],
                    ['reward_type' => 'material', 'ref_name' => 'Gruba Skóra Trolla', 'min_qty' => 3, 'max_qty' => 7, 'weight' => 180],
                    ['reward_type' => 'material', 'ref_name' => 'Ogrzy Pazur', 'min_qty' => 2, 'max_qty' => 6, 'weight' => 120],
                    ['reward_type' => 'material', 'ref_name' => 'Ruda Żelaza', 'min_qty' => 5, 'max_qty' => 12, 'weight' => 80],
                ],
                'stages' => [
                    [
                        'order' => 1, 'type' => 'single_mob', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Strażnik Krypty', 'level' => 28, 'rank' => 'regular', 'stats' => ['hp' => 950, 'atk' => 130, 'def' => 28, 'agi' => 16], 'avatar' => 'straznik-krypty.png']
                    ],
                    [
                        'order' => 2, 'type' => 'gate', 'count' => 1, 'max_turns' => 10,
                        'monster' => ['name' => 'Kamienna Brama Przeklętych', 'level' => 30, 'rank' => 'regular', 'stats' => ['hp' => 2500, 'atk' => 0, 'def' => 32, 'agi' => 0], 'avatar' => 'krypta-brama.png']
                    ],
                    [
                        'order' => 3, 'type' => 'group_mob', 'count' => 2, 'max_turns' => 50,
                        'monster' => ['name' => 'Upiorny Rycerz', 'level' => 30, 'rank' => 'regular', 'stats' => ['hp' => 850, 'atk' => 140, 'def' => 28, 'agi' => 18], 'avatar' => 'upiorny-rycerz.png']
                    ],
                    [
                        'order' => 4, 'type' => 'boss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Władca Krypty', 'level' => 33, 'rank' => 'boss', 'stats' => ['hp' => 3711, 'atk' => 285, 'def' => 51, 'agi' => 40], 'avatar' => 'wladca-krypty.png']
                    ],
                ]
            ],
            [
                'name' => 'Pustkowia Zarazy',
                'min_level' => 50,
                'key_id' => '01k4jpx94j70x2vv10b835key3',
                'boss_loot' => [
                    // Skill Books & Soul Stones (D3+)
                    ['reward_type' => 'item', 'ref_name' => 'Skrzynia Ksiąg Umiejętności', 'min_qty' => 1, 'max_qty' => 3, 'weight' => 220],
                    ['reward_type' => 'material', 'ref_name' => 'Kamień Duchowy', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 120],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Egzorcyzmu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 30],
                    ['reward_type' => 'material', 'ref_name' => 'Zaczarowany Magiczny Metal', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 30],
                    // Wejściowy loch z lootem chowańców (50 lvl+): T3-T5 jajka + podstawowa obroża.
                    ['reward_type' => 'item', 'ref_name' => 'Nietypowe Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 220],
                    ['reward_type' => 'item', 'ref_name' => 'Rzadkie Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 200],
                    ['reward_type' => 'item', 'ref_name' => 'Epickie Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 60],
                    ['reward_type' => 'item', 'ref_name' => 'Skórzana Obroża', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 20],
                    // Scrolls (consumables)
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Umiejętności', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 80],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Atrybutów', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 80],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Pełnego Resetu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 40],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Areny Walki', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 90],
                    // Upgrade materials (increased quantities)
                    ['reward_type' => 'material', 'ref_name' => 'Skrwawiony Totem', 'min_qty' => 3, 'max_qty' => 8, 'weight' => 180],
                    ['reward_type' => 'material', 'ref_name' => 'Łuska Hydry', 'min_qty' => 3, 'max_qty' => 7, 'weight' => 150],
                    ['reward_type' => 'material', 'ref_name' => 'Błotnisty Korzeń', 'min_qty' => 4, 'max_qty' => 10, 'weight' => 100],
                    ['reward_type' => 'material', 'ref_name' => 'Toksyczny Śluz', 'min_qty' => 3, 'max_qty' => 8, 'weight' => 70],
                ],
                'stages' => [
                    [
                        'order' => 1, 'type' => 'single_mob', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Zainfekowany Ork', 'level' => 48, 'rank' => 'regular', 'stats' => ['hp' => 1900, 'atk' => 220, 'def' => 50, 'agi' => 22], 'avatar' => 'zainfekowany-ork.png']
                    ],
                    [
                        'order' => 2, 'type' => 'group_mob', 'count' => 2, 'max_turns' => 50,
                        'monster' => ['name' => 'Wściekły Ogr', 'level' => 50, 'rank' => 'regular', 'stats' => ['hp' => 1700, 'atk' => 200, 'def' => 45, 'agi' => 20], 'avatar' => 'wsciekly-ogr.png']
                    ],
                    [
                        'order' => 3, 'type' => 'gate', 'count' => 1, 'max_turns' => 12,
                        'monster' => ['name' => 'Żelazna Kratownica Zarazy', 'level' => 50, 'rank' => 'regular', 'stats' => ['hp' => 5000, 'atk' => 0, 'def' => 55, 'agi' => 0], 'avatar' => 'zaraza-kratownica.png']
                    ],
                    [
                        'order' => 4, 'type' => 'miniboss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Szaman Plagi', 'level' => 52, 'rank' => 'boss', 'stats' => ['hp' => 3200, 'atk' => 280, 'def' => 55, 'agi' => 26], 'avatar' => 'szaman-plagi.png']
                    ],
                    [
                        'order' => 5, 'type' => 'boss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Wódz Zarazy', 'level' => 55, 'rank' => 'boss', 'stats' => ['hp' => 7064, 'atk' => 472, 'def' => 91, 'agi' => 77], 'avatar' => 'wodz-zarazy.png']
                    ],
                ]
            ],
            [
                'name' => 'Cytadela Cienia',
                'min_level' => 70,
                'key_id' => '01k4jpx94j70x2vv10b835key4',
                'boss_loot' => [
                    // Skill Books & Soul Stones (D4+)
                    ['reward_type' => 'item', 'ref_name' => 'Skrzynia Ksiąg Umiejętności', 'min_qty' => 1, 'max_qty' => 3, 'weight' => 250],
                    ['reward_type' => 'material', 'ref_name' => 'Kamień Duchowy', 'min_qty' => 1, 'max_qty' => 2, 'weight' => 160],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Egzorcyzmu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 45],
                    ['reward_type' => 'material', 'ref_name' => 'Zaczarowany Magiczny Metal', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 45],
                    // Loch środkowego poziomu: T4-T6 jajka (mała szansa na Legendarne!) + lepszy ekwipunek peta.
                    ['reward_type' => 'item', 'ref_name' => 'Rzadkie Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 300],
                    ['reward_type' => 'item', 'ref_name' => 'Epickie Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 250],
                    ['reward_type' => 'item', 'ref_name' => 'Legendarne Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 30],
                    ['reward_type' => 'item', 'ref_name' => 'Posrebrzana Obroża', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 30],
                    ['reward_type' => 'item', 'ref_name' => 'Amulet Feralnej Mocy', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 20],
                    // Scrolls (consumables)
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Umiejętności', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 140],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Atrybutów', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 140],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Pełnego Resetu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 90],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Areny Walki', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 150],
                    // Upgrade materials (increased quantities)
                    ['reward_type' => 'material', 'ref_name' => 'Kryształ Cienia', 'min_qty' => 3, 'max_qty' => 9, 'weight' => 180],
                    ['reward_type' => 'material', 'ref_name' => 'Pióro Harpii', 'min_qty' => 4, 'max_qty' => 12, 'weight' => 120],
                    ['reward_type' => 'material', 'ref_name' => 'Runiczny Kamień', 'min_qty' => 3, 'max_qty' => 8, 'weight' => 80],
                    ['reward_type' => 'material', 'ref_name' => 'Magiczny Rdzeń', 'min_qty' => 2, 'max_qty' => 5, 'weight' => 40],
                ],
                'stages' => [
                    [
                        'order' => 1, 'type' => 'single_mob', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Cieniowy Golem', 'level' => 68, 'rank' => 'regular', 'stats' => ['hp' => 2600, 'atk' => 300, 'def' => 75, 'agi' => 30], 'avatar' => 'cieniowy-golem.png']
                    ],
                    [
                        'order' => 2, 'type' => 'group_mob', 'count' => 2, 'max_turns' => 50,
                        'monster' => ['name' => 'Mroczna Harpia', 'level' => 70, 'rank' => 'regular', 'stats' => ['hp' => 2300, 'atk' => 270, 'def' => 65, 'agi' => 40], 'avatar' => 'mroczna-harpia.png']
                    ],
                    [
                        'order' => 3, 'type' => 'gate', 'count' => 1, 'max_turns' => 12,
                        'monster' => ['name' => 'Magiczna Bariera Cytadeli', 'level' => 70, 'rank' => 'regular', 'stats' => ['hp' => 7000, 'atk' => 0, 'def' => 90, 'agi' => 0], 'avatar' => 'cytadela-bariera.png']
                    ],
                    [
                        'order' => 4, 'type' => 'miniboss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Arcymag Cienia', 'level' => 72, 'rank' => 'boss', 'stats' => ['hp' => 5200, 'atk' => 420, 'def' => 85, 'agi' => 48], 'avatar' => 'arcymag-cienia.png']
                    ],
                    [
                        'order' => 5, 'type' => 'boss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Władca Cytadeli', 'level' => 75, 'rank' => 'boss', 'stats' => ['hp' => 9640, 'atk' => 550, 'def' => 121, 'agi' => 99], 'avatar' => 'wladca-cytadeli.png']
                    ],
                ]
            ],
            [
                'name' => 'Otchłań Zniszczenia',
                'min_level' => 88,
                'key_id' => '01k4jpx94j70x2vv10b835key5',
                'boss_loot' => [
                    // Skill Books & Soul Stones (D5+)
                    ['reward_type' => 'item', 'ref_name' => 'Skrzynia Ksiąg Umiejętności', 'min_qty' => 2, 'max_qty' => 4, 'weight' => 280],
                    ['reward_type' => 'material', 'ref_name' => 'Kamień Duchowy', 'min_qty' => 1, 'max_qty' => 3, 'weight' => 200],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Egzorcyzmu', 'min_qty' => 1, 'max_qty' => 2, 'weight' => 60],
                    ['reward_type' => 'material', 'ref_name' => 'Zaczarowany Magiczny Metal', 'min_qty' => 1, 'max_qty' => 2, 'weight' => 60],
                    // Najlepszy loot chowańców w grze: głównie T5, spora szansa na T6 Legendarne + rzadki ekwipunek.
                    ['reward_type' => 'item', 'ref_name' => 'Epickie Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 350],
                    ['reward_type' => 'item', 'ref_name' => 'Legendarne Jajko Chowańca', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 200],
                    ['reward_type' => 'item', 'ref_name' => 'Amulet Feralnej Mocy', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 70],
                    ['reward_type' => 'item', 'ref_name' => 'Sakwa Chowańców', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 50],
                    // Scrolls (consumables)
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Umiejętności', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 220],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Resetu Atrybutów', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 220],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Pełnego Resetu', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 160],
                    ['reward_type' => 'item', 'ref_name' => 'Zwój Areny Walki', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 240],
                    // Upgrade materials (increased quantities)
                    ['reward_type' => 'material', 'ref_name' => 'Skażona Kość', 'min_qty' => 4, 'max_qty' => 12, 'weight' => 150],
                    ['reward_type' => 'material', 'ref_name' => 'Przeklęta Stal', 'min_qty' => 3, 'max_qty' => 9, 'weight' => 100],
                    ['reward_type' => 'material', 'ref_name' => 'Esencja Zniszczenia', 'min_qty' => 2, 'max_qty' => 5, 'weight' => 60],
                    ['reward_type' => 'material', 'ref_name' => 'Czarny Kamień Dusz', 'min_qty' => 2, 'max_qty' => 5, 'weight' => 40],
                ],
                'stages' => [
                    [
                        'order' => 1, 'type' => 'single_mob', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Demon Otchłani', 'level' => 86, 'rank' => 'regular', 'stats' => ['hp' => 3600, 'atk' => 420, 'def' => 115, 'agi' => 48], 'avatar' => 'demon-otchlani.png']
                    ],
                    [
                        'order' => 2, 'type' => 'group_mob', 'count' => 2, 'max_turns' => 50,
                        'monster' => ['name' => 'Kat Otchłani', 'level' => 88, 'rank' => 'regular', 'stats' => ['hp' => 3200, 'atk' => 380, 'def' => 100, 'agi' => 45], 'avatar' => 'plagowy-kat-dung.png']
                    ],
                    [
                        'order' => 3, 'type' => 'gate', 'count' => 1, 'max_turns' => 12,
                        'monster' => ['name' => 'Wrota Wulkaniczne Otchłani', 'level' => 88, 'rank' => 'regular', 'stats' => ['hp' => 9500, 'atk' => 0, 'def' => 130, 'agi' => 0], 'avatar' => 'otchlan-wrota.png']
                    ],
                    [
                        'order' => 4, 'type' => 'miniboss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Książę Otchłani', 'level' => 90, 'rank' => 'boss', 'stats' => ['hp' => 6800, 'atk' => 600, 'def' => 135, 'agi' => 58], 'avatar' => 'ksiaze-skazy.png']
                    ],
                    [
                        'order' => 5, 'type' => 'boss', 'count' => 1, 'max_turns' => 50,
                        'monster' => ['name' => 'Pan Zniszczenia (Loch)', 'level' => 95, 'rank' => 'boss', 'stats' => ['hp' => 13192, 'atk' => 698, 'def' => 159, 'agi' => 127], 'avatar' => 'pan-zniszczenia-dung.png']
                    ],
                ]
            ]
        ];

        $mapIdMap = [
            'Zapomniane Katakumby' => Map::where('name', 'Stare Ruiny')->first()?->id ?? 2,
            'Krypta Przeklętych' => Map::where('name', 'Jaskinia Trolli')->first()?->id ?? 3,
            'Pustkowia Zarazy' => Map::where('name', 'Bagna Grozy')->first()?->id ?? 5,
            'Cytadela Cienia' => Map::where('name', 'Góry Cienia')->first()?->id ?? 6,
            'Otchłań Zniszczenia' => Map::where('name', 'Skażone Miasto')->first()?->id ?? 8,
        ];

        foreach ($dungeonsConfig as $dConfig) {
            // 1. Create Loot Table for the Boss
            $bossLootTable = LootTable::firstOrCreate([
                'name' => 'boss_' . Str::slug($dConfig['name']) . '_loot',
            ], [
                'description' => 'Drop z Bossa lochu ' . $dConfig['name']
            ]);
            LootTableEntry::where('loot_table_id', $bossLootTable->id)->delete();

            foreach ($dConfig['boss_loot'] as $lootItem) {
                $refUlid = $getItemUlid($lootItem['ref_name']);
                if (!$refUlid) {
                    $this->command->warn("Loot item '{$lootItem['ref_name']}' not found, skipping.");
                    continue;
                }

                LootTableEntry::create([
                    'loot_table_id' => $bossLootTable->id,
                    'reward_type' => $lootItem['reward_type'],
                    'ref_ulid' => $refUlid,
                    'min_qty' => $lootItem['min_qty'],
                    'max_qty' => $lootItem['max_qty'],
                    'weight' => $lootItem['weight'],
                ]);
            }

            // 2. Create Dungeon Record
            $dungeon = Dungeon::create([
                'name' => $dConfig['name'],
                'min_level' => $dConfig['min_level'],
                'entry_item_template_id' => $dConfig['key_id'],
            ]);

            // 3. Create Dungeon Stages & Monsters
            foreach ($dConfig['stages'] as $stgData) {
                $mStats = $stgData['monster'];

                $targetMapId = $mapIdMap[$dConfig['name']] ?? 2;
                $existingMonster = Monster::where('name', $mStats['name'])->first();

                $monster = Monster::updateOrCreate(
                    [
                        'name' => $mStats['name'],
                    ],
                    [
                        'map_id' => $existingMonster?->map_id ?? $targetMapId,
                        'type' => ($stgData['type'] === 'gate' ? 'mystical' : 'demon'),
                        'level' => $mStats['level'],
                        'rank' => $mStats['rank'],
                        'stats' => $mStats['stats'],
                        'abilities' => [],
                        'avatar' => $mStats['avatar'],
                        'loot_table_id' => ($stgData['type'] === 'boss' ? $bossLootTable->id : $existingMonster?->loot_table_id)
                    ]
                );

                DungeonStage::create([
                    'dungeon_id' => $dungeon->id,
                    'stage_order' => $stgData['order'],
                    'monster_id' => $monster->id,
                    'stage_type' => $stgData['type'],
                    'monster_count' => $stgData['count'],
                    'max_turns' => $stgData['max_turns'],
                ]);
            }
        }

        $this->command->info('Dungeon seeder completed. Seeded 5 dungeons with stages, gates, group fights, and boss loot tables.');
    }
}
