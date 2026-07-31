<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\Character;
use App\Models\User;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Dungeon\DungeonService;
use App\Infrastructure\Persistence\Monster;
use Database\Seeders\DungeonSeeder;

class DungeonExpansionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DungeonSeeder::class);
    }

    public function test_five_dungeons_seeded_successfully(): void
    {
        $this->assertEquals(5, Dungeon::count());

        $dungeons = Dungeon::orderBy('min_level')->get();
        $this->assertEquals(12, $dungeons[0]->min_level);
        $this->assertEquals(30, $dungeons[1]->min_level);
        $this->assertEquals(50, $dungeons[2]->min_level);
        $this->assertEquals(70, $dungeons[3]->min_level);
        $this->assertEquals(88, $dungeons[4]->min_level);
    }

    public function test_dungeon_stages_types_and_limits(): void
    {
        $dungeon = Dungeon::where('min_level', 12)->first();
        $stages = $dungeon->stages()->orderBy('stage_order')->get();

        $this->assertCount(4, $stages);
        $this->assertEquals('single_mob', $stages[0]->stage_type);
        $this->assertEquals('group_mob', $stages[1]->stage_type);
        $this->assertEquals(2, $stages[1]->monster_count);
        $this->assertEquals('gate', $stages[2]->stage_type);
        $this->assertEquals(10, $stages[2]->max_turns);
        $this->assertEquals('boss', $stages[3]->stage_type);
    }

    public function test_dungeon_run_execution_and_loot_accumulation(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Bohater Testowy',
            'level' => 20,
            'xp' => 0,
            'gold' => 500,
            'attributes' => ['str' => 200, 'int' => 50, 'vit' => 200, 'agi' => 100],
        ]);

        $dungeon = Dungeon::where('min_level', 12)->first();

        // Give character key
        ItemInstance::create([
            'template_id' => $dungeon->entry_item_template_id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'stack_size' => 1,
        ]);

        $service = app(DungeonService::class);
        $startResult = $service->startRun($character, $dungeon);
        $this->assertTrue($startResult->isOk());

        $run = $startResult->getPayload();
        $this->assertEquals(1, $run->current_stage);

        // Stage 1 (single_mob)
        $simResult1 = $service->simulateStage($run);
        $this->assertTrue($simResult1->isOk());
        $run->refresh();

        // Check non-boss stage accumulated loot has NO items
        $loot1 = $run->accumulated_loot;
        $this->assertGreaterThan(0, $loot1['gold']);
        $this->assertGreaterThan(0, $loot1['xp']);
        $this->assertEmpty($loot1['items']);
    }

    public function test_map_bosses_have_dungeon_key_drops_with_small_weight(): void
    {
        $this->seed(\Database\Seeders\MonsterSeeder::class);
        $this->seed(\Database\Seeders\MonsterLootSeeder::class);

        $boss = Monster::where('name', 'Strażnik Puszczy')->first();
        $this->assertNotNull($boss);
        $this->assertNotNull($boss->lootTable);

        $keyTemplate = ItemTemplate::where('name', 'Klucz Katakumb')->first();
        $this->assertNotNull($keyTemplate);

        $keyEntry = $boss->lootTable->entries()
            ->where('ref_ulid', $keyTemplate->id)
            ->first();

        $this->assertNotNull($keyEntry);
        $this->assertEquals(3, $keyEntry->weight); // Small weight (3) vs materials (25)
    }

    public function test_dungeon_completion_guarantees_1_to_3_chests(): void
    {
        $this->seed(\Database\Seeders\MaterialItemSeeder::class);
        $this->seed(\Database\Seeders\LootChestSeeder::class);

        $user = User::factory()->create();
        $character = Character::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Bohater Testowy',
            'level' => 50,
            'xp' => 0,
            'gold' => 500,
            'attributes' => ['str' => 999, 'int' => 999, 'vit' => 999, 'agi' => 999],
        ]);

        $dungeon = Dungeon::where('name', 'Zapomniane Katakumby')->first();

        // Give character key
        ItemInstance::create([
            'template_id' => $dungeon->entry_item_template_id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'stack_size' => 1,
        ]);

        $service = app(DungeonService::class);
        $startResult = $service->startRun($character, $dungeon);
        $this->assertTrue($startResult->isOk());
        $run = $startResult->getPayload();

        // Run all stages until completion
        while (!$run->is_completed && !$run->is_failed) {
            $simResult = $service->simulateStage($run);
            $this->assertTrue($simResult->isOk());
            $run->refresh();
        }

        $this->assertTrue($run->is_completed);

        // Verify accumulated loot contains the chest item with 1..3 quantity
        $loot = $run->accumulated_loot;
        $chestItems = array_filter($loot['items'], fn($it) => $it['name'] === 'Skrzynia Starych Ruin');
        $this->assertNotEmpty($chestItems);

        $chestItem = array_values($chestItems)[0];
        $this->assertGreaterThanOrEqual(1, $chestItem['quantity']);
        $this->assertLessThanOrEqual(3, $chestItem['quantity']);

        // Verify character received the chest item in inventory
        $chestInstance = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $chestItem['ref_ulid'])
            ->first();
        $this->assertNotNull($chestInstance);
        $this->assertGreaterThanOrEqual(1, $chestInstance->stack_size);
        $this->assertLessThanOrEqual(3, $chestInstance->stack_size);
    }

    public function test_dungeon_combat_uses_equipped_skills(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Bohater Skille',
            'level' => 20,
            'xp' => 0,
            'gold' => 500,
            'attributes' => ['str' => 50, 'int' => 50, 'vit' => 100, 'agi' => 50],
        ]);

        $skill = \App\Infrastructure\Persistence\CombatSkill::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Potężny Cios Test',
            'type' => 'active',
            'effect_type' => 'direct_dmg',
            'required_weapon_type' => 'all',
            'base_cooldown' => 2,
            'base_value' => 2.5,
            'scaling_value' => 0.5,
            'base_duration' => 0,
            'is_magic' => false,
            'is_aoe' => false,
            'unlock_level' => 1,
            'cost_sp' => 1,
        ]);

        \App\Infrastructure\Persistence\CharacterCombatSkill::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 1,
            'is_equipped' => true,
            'equip_slot' => 1,
        ]);

        $dungeon = Dungeon::where('min_level', 12)->first();

        ItemInstance::create([
            'template_id' => $dungeon->entry_item_template_id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'stack_size' => 1,
        ]);

        $service = app(DungeonService::class);
        $startResult = $service->startRun($character, $dungeon);
        $this->assertTrue($startResult->isOk());
        $run = $startResult->getPayload();

        $simResult = $service->simulateStage($run);
        $this->assertTrue($simResult->isOk());
        $payload = $simResult->getPayload();

        $usedSkillsInTurns = array_filter($payload['turns'], fn($t) => ($t['type'] ?? '') === 'skill');
        $this->assertNotEmpty($usedSkillsInTurns);
    }

    public function test_chests_are_not_usable_as_potions_in_dungeon(): void
    {
        $this->seed(\Database\Seeders\LootChestSeeder::class);

        $user = User::factory()->create();
        $character = Character::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Bohater Skrzynia',
            'level' => 20,
            'xp' => 0,
            'gold' => 500,
            'attributes' => ['str' => 50, 'int' => 50, 'vit' => 100, 'agi' => 50],
        ]);

        $dungeon = Dungeon::where('min_level', 12)->first();
        $service = app(DungeonService::class);

        $run = \App\Infrastructure\Persistence\CharacterDungeonRun::create([
            'character_id' => $character->id,
            'dungeon_id' => $dungeon->id,
            'current_stage' => 1,
            'current_hp' => 50,
            'is_completed' => false,
            'is_failed' => false,
        ]);

        $chestTemplate = ItemTemplate::where('type', 'consumable')->where('sub_type', 'chest')->first();
        $this->assertNotNull($chestTemplate);

        $chestInstance = ItemInstance::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'template_id' => $chestTemplate->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'stack_size' => 1,
        ]);

        $useResult = $service->usePotion($run, $chestInstance->id);
        $this->assertTrue($useResult->isError());
        $this->assertEquals('NOT_CONSUMABLE', $useResult->getErrorCode());
    }
}
