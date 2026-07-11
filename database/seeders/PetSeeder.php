<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\PetTemplate;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;

class PetSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Pet Templates
        $pets = [
            [
                'name' => 'Leśny Wilk',
                'rarity' => 'common',
                'icon' => '🐺',
                'base_stats' => ['str' => 2, 'agi' => 3, 'int' => 0, 'vit' => 1]
            ],
            [
                'name' => 'Skalny Golem',
                'rarity' => 'uncommon',
                'icon' => '🪨',
                'base_stats' => ['str' => 4, 'agi' => 0, 'int' => 0, 'vit' => 5]
            ],
            [
                'name' => 'Magiczna Wróżka',
                'rarity' => 'rare',
                'icon' => '🧚',
                'base_stats' => ['str' => 0, 'agi' => 2, 'int' => 6, 'vit' => 2]
            ],
            [
                'name' => 'Mroczny Smok',
                'rarity' => 'epic',
                'icon' => '🐉',
                'base_stats' => ['str' => 8, 'agi' => 5, 'int' => 8, 'vit' => 6]
            ],
        ];

        foreach ($pets as $pet) {
            PetTemplate::firstOrCreate(['name' => $pet['name']], $pet);
        }

        // 2. Create Egg Items
        $eggs = [
            [
                'id' => 'egg-common',
                'name' => 'Zwykłe Jajo Chowańca',
                'type' => 'egg',
                'level_requirement' => 1,
                'icon' => '🥚',
                'description' => 'Może z niego wykluć się zwierzak (najczęściej Common).'
            ],
            [
                'id' => 'egg-rare',
                'name' => 'Rzadkie Jajo Chowańca',
                'type' => 'egg',
                'level_requirement' => 10,
                'icon' => '🥚',
                'description' => 'Gwarantuje lepszego chowańca (większa szansa na Rare).'
            ],
            [
                'id' => 'egg-epic',
                'name' => 'Epickie Jajo Chowańca',
                'type' => 'egg',
                'level_requirement' => 20,
                'icon' => '🥚',
                'description' => 'Potężne jajo skrywające epickiego stwora.'
            ],
        ];

        foreach ($eggs as $egg) {
            ItemTemplate::firstOrCreate(['id' => $egg['id']], $egg);
        }

        // 3. Boss Loot Table
        $bossLootTable = LootTable::firstOrCreate(
            ['name' => 'boss_dungeon_loot'],
            ['description' => 'Loot z Władcy Lochów, zawiera jajka.']
        );

        $drops = [
            ['reward_type' => 'gold', 'ref_ulid' => null, 'min_qty' => 100, 'max_qty' => 500, 'weight' => 100],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-common', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 50],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-rare', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 15],
            ['reward_type' => 'item', 'ref_ulid' => 'egg-epic', 'min_qty' => 1, 'max_qty' => 1, 'weight' => 2],
        ];

        foreach ($drops as $drop) {
            LootTableEntry::firstOrCreate([
                'loot_table_id' => $bossLootTable->id,
                'reward_type' => $drop['reward_type'],
                'ref_ulid' => $drop['ref_ulid'],
            ], [
                'min_qty' => $drop['min_qty'],
                'max_qty' => $drop['max_qty'],
                'weight' => $drop['weight'],
            ]);
        }

        // 4. Update Dungeon to have a specific Boss
        $dungeon = Dungeon::first();
        if ($dungeon) {
            $bossMonster = Monster::firstOrCreate(
                ['name' => 'Władca Lochów'],
                [
                    'map_id' => Monster::first()->map_id ?? 1,
                    'type' => 'nieumarły',
                    'level' => 15,
                    'rank' => 'boss',
                    'stats' => ['hp' => 500, 'atk' => 30, 'def' => 15, 'crit' => 5],
                    'loot_table_id' => $bossLootTable->id
                ]
            );

            // Replace the last stage of the dungeon with this boss
            $lastStage = DungeonStage::where('dungeon_id', $dungeon->id)->orderByDesc('stage_order')->first();
            if ($lastStage) {
                $lastStage->update(['monster_id' => $bossMonster->id]);
            }
        }

        $this->command->info('Pet templates and eggs seeded. Dungeon Boss updated with egg drops.');
    }
}
