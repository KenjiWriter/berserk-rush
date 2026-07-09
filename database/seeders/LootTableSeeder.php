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
        // Get available items and materials from DB
        $materials = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'material')->get();
        $weapons = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'weapon')->get();
        $armors = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'armor')->get();
        
        $pelt = $materials->where('name', 'Wilcza Skóra')->first();
        $herb = $materials->where('name', 'Zioło Lecznika')->first();
        $bone = $materials->where('name', 'Odłamek Kości')->first();
        $gem = $materials->where('name', 'Klejnot Pustyni')->first();
        
        $rustySword = $weapons->where('name', 'Zardzewiały Miecz')->first();
        $woodenBow = $weapons->where('name', 'Drewniany Łuk')->first();
        $leatherArmor = $armors->where('name', 'Skórzana Zbroja')->first();

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

        if ($pelt) {
            LootTableEntry::create([
                'loot_table_id' => $forestLoot->id,
                'reward_type' => 'item', // 'item' is proper since it's an ItemTemplate now
                'ref_ulid' => $pelt->id,
                'weight' => 25,
                'min_qty' => 1,
                'max_qty' => 2
            ]);
        }
        
        if ($herb) {
            LootTableEntry::create([
                'loot_table_id' => $forestLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $herb->id,
                'weight' => 15,
                'min_qty' => 1,
                'max_qty' => 3
            ]);
        }

        if ($rustySword) {
            LootTableEntry::create([
                'loot_table_id' => $forestLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $rustySword->id,
                'weight' => 10,
                'min_qty' => 1,
                'max_qty' => 1
            ]);
        }

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

        if ($bone) {
            LootTableEntry::create([
                'loot_table_id' => $ruinsLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $bone->id,
                'weight' => 30,
                'min_qty' => 1,
                'max_qty' => 2
            ]);
        }
        
        if ($woodenBow) {
            LootTableEntry::create([
                'loot_table_id' => $ruinsLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $woodenBow->id,
                'weight' => 10,
                'min_qty' => 1,
                'max_qty' => 1
            ]);
        }

        if ($leatherArmor) {
            LootTableEntry::create([
                'loot_table_id' => $ruinsLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $leatherArmor->id,
                'weight' => 5,
                'min_qty' => 1,
                'max_qty' => 1
            ]);
        }

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

        if ($gem) {
            LootTableEntry::create([
                'loot_table_id' => $desertLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $gem->id,
                'weight' => 25,
                'min_qty' => 1,
                'max_qty' => 2
            ]);
        }

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
