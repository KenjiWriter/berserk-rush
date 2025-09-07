<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Support\Str;

class LootTableSeeder extends Seeder
{
    public function run(): void
    {
        // Forest Common Loot Table (Mroczny Las)
        $forestLoot = LootTable::create([
            'name' => 'forest_common',
        ]);

        // Forest loot entries
        LootTableEntry::create([
            'loot_table_id' => $forestLoot->id,
            'reward_type' => 'gold',
            'weight' => 50,
            'min_qty' => 5,
            'max_qty' => 20
        ]);

        LootTableEntry::create([
            'loot_table_id' => $forestLoot->id,
            'reward_type' => 'material',
            'ref_ulid' => Str::ulid(), // wolf_pelt material ULID
            'weight' => 25,
            'min_qty' => 1,
            'max_qty' => 2
        ]);

        LootTableEntry::create([
            'loot_table_id' => $forestLoot->id,
            'reward_type' => 'item',
            'ref_ulid' => Str::ulid(), // rusty_dagger item template ULID
            'weight' => 10,
            'min_qty' => 1,
            'max_qty' => 1
        ]);

        LootTableEntry::create([
            'loot_table_id' => $forestLoot->id,
            'reward_type' => 'material',
            'ref_ulid' => Str::ulid(), // herb_leaf material ULID
            'weight' => 15,
            'min_qty' => 1,
            'max_qty' => 3
        ]);

        // Ruins Loot Table (Stare Ruiny)
        $ruinsLoot = LootTable::create([
            'name' => 'ruins_common',
        ]);

        LootTableEntry::create([
            'loot_table_id' => $ruinsLoot->id,
            'reward_type' => 'gold',
            'weight' => 45,
            'min_qty' => 8,
            'max_qty' => 25
        ]);

        LootTableEntry::create([
            'loot_table_id' => $ruinsLoot->id,
            'reward_type' => 'material',
            'ref_ulid' => Str::ulid(), // bone_fragment
            'weight' => 30,
            'min_qty' => 1,
            'max_qty' => 2
        ]);

        LootTableEntry::create([
            'loot_table_id' => $ruinsLoot->id,
            'reward_type' => 'gems',
            'weight' => 5,
            'min_qty' => 1,
            'max_qty' => 1
        ]);

        // Desert Loot Table (Pustynia Kości)
        $desertLoot = LootTable::create([
            'name' => 'desert_common',
        ]);

        LootTableEntry::create([
            'loot_table_id' => $desertLoot->id,
            'reward_type' => 'gold',
            'weight' => 40,
            'min_qty' => 12,
            'max_qty' => 30
        ]);

        LootTableEntry::create([
            'loot_table_id' => $desertLoot->id,
            'reward_type' => 'material',
            'ref_ulid' => Str::ulid(), // sand_crystal
            'weight' => 25,
            'min_qty' => 1,
            'max_qty' => 2
        ]);

        // Assign loot tables to monsters
        $this->assignLootTablesToMonsters($forestLoot, $ruinsLoot, $desertLoot);
    }

    private function assignLootTablesToMonsters($forestLoot, $ruinsLoot, $desertLoot): void
    {
        // Forest monsters (levels 1-15)
        Monster::where('level', '<=', 15)->update(['loot_table_id' => $forestLoot->id]);

        // Ruins monsters (levels 16-35)
        Monster::whereBetween('level', [16, 35])->update(['loot_table_id' => $ruinsLoot->id]);

        // Desert monsters (levels 36+)
        Monster::where('level', '>=', 36)->update(['loot_table_id' => $desertLoot->id]);
    }
}
