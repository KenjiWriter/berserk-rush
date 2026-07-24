<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Infrastructure\Persistence\LootTable;
use App\Infrastructure\Persistence\LootTableEntry;
use App\Infrastructure\Persistence\Monster;

class LootTableSeeder extends Seeder
{
    public function run(): void
    {
        // Pobieramy materiały i uzbrojenie z bazy
        $materials = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'material')->get();
        $weapons = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'weapon')->get();
        $armors = \App\Infrastructure\Persistence\ItemTemplate::where('type', 'armor')->get();

        $fang = $materials->where('name', 'Wilczy Kieł')->first();
        $herb = $materials->where('name', 'Mroczne Zioło')->first();
        $bone = $materials->where('name', 'Strzaskana Kość')->first();
        $crystal = $materials->where('name', 'Kryształ Cienia')->first();

        $rustySword = $weapons->where('name', 'Zardzewiały Miecz')->first();
        $leatherArmor = $armors->where('name', 'Skórzana Zbroja')->first();

        // Tabela ogólna dla Lasu
        $forestLoot = LootTable::firstOrCreate(['name' => 'forest_common']);
        LootTableEntry::where('loot_table_id', $forestLoot->id)->delete();

        LootTableEntry::create([
            'loot_table_id' => $forestLoot->id,
            'reward_type' => 'gold',
            'weight' => 50,
            'min_qty' => 5,
            'max_qty' => 20
        ]);

        if ($fang) {
            LootTableEntry::create([
                'loot_table_id' => $forestLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $fang->id,
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

        // Tabela ogólna dla Ruin
        $ruinsLoot = LootTable::firstOrCreate(['name' => 'ruins_common']);
        LootTableEntry::where('loot_table_id', $ruinsLoot->id)->delete();

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

        if ($leatherArmor) {
            LootTableEntry::create([
                'loot_table_id' => $ruinsLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $leatherArmor->id,
                'weight' => 10,
                'min_qty' => 1,
                'max_qty' => 1
            ]);
        }

        // Tabela ogólna dla Pustyni
        $desertLoot = LootTable::firstOrCreate(['name' => 'desert_common']);
        LootTableEntry::where('loot_table_id', $desertLoot->id)->delete();

        LootTableEntry::create([
            'loot_table_id' => $desertLoot->id,
            'reward_type' => 'gold',
            'weight' => 40,
            'min_qty' => 12,
            'max_qty' => 30
        ]);

        if ($crystal) {
            LootTableEntry::create([
                'loot_table_id' => $desertLoot->id,
                'reward_type' => 'item',
                'ref_ulid' => $crystal->id,
                'weight' => 25,
                'min_qty' => 1,
                'max_qty' => 2
            ]);
        }

        // Przypisanie domyślne do potworów wg przedziału poziomów
        Monster::where('level', '<=', 15)->update(['loot_table_id' => $forestLoot->id]);
        Monster::whereBetween('level', [16, 35])->update(['loot_table_id' => $ruinsLoot->id]);
        Monster::where('level', '>=', 36)->update(['loot_table_id' => $desertLoot->id]);

        $this->command->info('LootTableSeeder completed.');
    }
}
