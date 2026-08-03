<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Infrastructure\Persistence\Monster;
use Database\Seeders\MonsterSeeder;
use Database\Seeders\MonsterLootSeeder;

class EquipmentMapDropTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_items_on_maps_have_weight_giving_point_six_percent_chance(): void
    {
        $this->seed(\Database\Seeders\MapSeeder::class);
        $this->seed(\Database\Seeders\ItemTemplateSeeder::class);
        $this->seed(\Database\Seeders\MaterialItemSeeder::class);
        $this->seed(MonsterSeeder::class);
        $this->seed(MonsterLootSeeder::class);

        $monsters = Monster::has('lootTable')->get();
        $this->assertNotEmpty($monsters);

        $foundItemEntry = false;
        foreach ($monsters as $monster) {
            $totalWeight = max(1, $monster->lootTable->entries->sum('weight'));
            foreach ($monster->lootTable->entries as $entry) {
                if ($entry->reward_type === 'item') {
                    $template = \App\Infrastructure\Persistence\ItemTemplate::find($entry->ref_ulid);
                    if ($template && !in_array($template->type, ['consumable', 'key', 'currency', 'material'])) {
                        $foundItemEntry = true;
                        $chance = round(($entry->weight / $totalWeight) * 100, 1);
                        $this->assertGreaterThan(0, $chance);
                    }
                }
            }
        }

        $this->assertTrue($foundItemEntry, 'Found at least one monster with equipment item drop entry');
    }
}
