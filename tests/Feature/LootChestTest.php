<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\Monster;
use App\Application\Items\Actions\OpenLootChestAction;
use Database\Seeders\LootChestSeeder;
use Database\Seeders\MaterialItemSeeder;
use Database\Seeders\MonsterSeeder;
use Database\Seeders\MonsterLootSeeder;
use Illuminate\Support\Str;

class LootChestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MaterialItemSeeder::class);
        $this->seed(LootChestSeeder::class);
    }

    public function test_eight_chests_seeded_successfully(): void
    {
        $chests = ItemTemplate::where('type', 'consumable')->where('sub_type', 'chest')->get();
        $this->assertCount(8, $chests);

        $chestNames = $chests->pluck('name')->toArray();
        $this->assertContains('Skrzynia Mrocznego Lasu', $chestNames);
        $this->assertContains('Skrzynia Starych Ruin', $chestNames);
        $this->assertContains('Skrzynia Jaskini Trolli', $chestNames);
        $this->assertContains('Skrzynia Pustkowi Orków', $chestNames);
        $this->assertContains('Skrzynia Bagien Grozy', $chestNames);
        $this->assertContains('Skrzynia Gór Cienia', $chestNames);
        $this->assertContains('Skrzynia Wieży Magów', $chestNames);
        $this->assertContains('Skrzynia Skażonego Miasta', $chestNames);
    }

    public function test_open_loot_chest_action_grants_material_and_returns_roulette_payload(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Bohater Testowy',
            'level' => 50,
            'attributes' => ['str' => 50, 'int' => 10, 'vit' => 50, 'agi' => 30],
        ]);

        $chestTemplate = ItemTemplate::where('name', 'Skrzynia Mrocznego Lasu')->first();
        $this->assertNotNull($chestTemplate);

        $chestInstance = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'owner_character_id' => $character->id,
            'template_id' => $chestTemplate->id,
            'location' => 'inventory',
            'stack_size' => 2,
        ]);

        $action = app(OpenLootChestAction::class);
        $result = $action->execute($character, $chestInstance->id);

        $this->assertTrue($result->isOk());

        $payload = $result->getPayload();
        $this->assertNotNull($payload['winning_item']);
        $this->assertEquals(28, $payload['winning_index']);
        $this->assertCount(40, $payload['roulette_items']);
        $this->assertEquals(1, $payload['remaining_chests']);

        // Check chest stack size decremented in DB
        $chestInstance->refresh();
        $this->assertEquals(1, $chestInstance->stack_size);

        // Check winning material granted to material_stash
        $winningMaterial = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $payload['winning_item']['id'])
            ->first();
        $this->assertNotNull($winningMaterial);

        // Check ItemLedger logged open_chest action
        $ledger = ItemLedger::where('character_id', $character->id)
            ->where('action', 'open_chest')
            ->first();
        $this->assertNotNull($ledger);
    }

    public function test_bosses_have_chest_drops_in_monster_loot(): void
    {
        $this->seed(\Database\Seeders\MapSeeder::class);
        $this->seed(MonsterSeeder::class);
        $this->seed(MonsterLootSeeder::class);

        $boss = Monster::where('name', 'Strażnik Puszczy')->first();
        $this->assertNotNull($boss);
        $this->assertNotNull($boss->lootTable);

        $chestTemplate = ItemTemplate::where('name', 'Skrzynia Mrocznego Lasu')->first();
        $this->assertNotNull($chestTemplate);

        $entry = $boss->lootTable->entries()
            ->where('ref_ulid', $chestTemplate->id)
            ->first();

        $this->assertNotNull($entry);
        $this->assertEquals(15, $entry->weight);
    }
}
