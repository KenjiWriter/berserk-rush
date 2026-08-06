<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Items\DismantleService;
use App\Application\Mastery\ChampionService;
use App\Application\Characters\LevelUpService;
use App\Models\User;

class DismantleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\MaterialItemSeeder::class);
        $this->seed(\Database\Seeders\UpgradeRuleSeeder::class);
    }

    public function test_calculate_shard_yield_scales_with_level_rarity_upgrade_and_enchants(): void
    {
        $dismantleService = new DismantleService();

        $template = ItemTemplate::create([
            'id' => 'test-sword',
            'name' => 'Test Sword',
            'type' => 'weapon',
            'slot' => 'main_hand',
            'level_requirement' => 85,
            'base_stats' => ['attack_min' => 100, 'attack_max' => 120],
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'rarity' => 'epic',
            'upgrade_level' => 6,
            'roll_stats' => [
                'enchants' => ['crit_chance' => 5, 'hp_bonus' => 100],
            ],
            'location' => 'inventory',
        ]);

        $yield = $dismantleService->calculateShardYield($item);

        // Base for lvl 85: ceil(85/10) = 9
        // Rarity epic: 8.0x
        // Upgrade +6: 2.8x
        // Enchants (2): 1.0 + 0.3 = 1.3x
        // Expected: 9 * 8 * 2.8 * 1.3 = 262.08 -> 262
        $this->assertEquals(262, $yield);
    }

    public function test_dismantle_item_destroys_item_and_adds_runic_shards_to_stash(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'DismantleHero',
            'level' => 50,
            'gold' => 1000,
        ]);

        $template = ItemTemplate::create([
            'id' => 'test-armor',
            'name' => 'Test Armor',
            'type' => 'armor',
            'slot' => 'chest',
            'level_requirement' => 25,
            'base_stats' => ['defense' => 50],
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'user_id' => $user->id,
            'rarity' => 'rare',
            'upgrade_level' => 0,
            'location' => 'inventory',
        ]);

        $dismantleService = new DismantleService();
        $result = $dismantleService->dismantleItem($character, $item);

        $this->assertTrue($result['success']);
        $this->assertDatabaseMissing('item_instances', ['id' => $item->id]);

        $runicTemplate = ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        $this->assertNotNull($runicTemplate);

        $shards = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $runicTemplate->id)
            ->where('location', 'material_stash')
            ->first();

        $this->assertNotNull($shards);
        // Base 25 lvl = 3, rare = 4.0x -> 12 shards
        $this->assertEquals(12, $shards->stack_size);
    }

    public function test_champion_level_up_requires_and_consumes_runic_shards(): void
    {
        $user = User::factory()->create();
        $championService = new ChampionService();

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'ChampHero',
            'level' => LevelUpService::MAX_LEVEL,
            'champion_level' => 0,
            'xp' => $championService->xpTarget(),
        ]);

        // Attempt without shards should fail
        $result = $championService->attemptLevelUp($character);
        $this->assertFalse($result->isOk());
        $this->assertEquals('RUNIC_SHARDS_MISSING', $result->getErrorCode());

        // Give shards (1000 required for level 0->1)
        $runicTemplate = ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        ItemInstance::create([
            'template_id' => $runicTemplate->id,
            'owner_character_id' => $character->id,
            'user_id' => $user->id,
            'location' => 'material_stash',
            'stack_size' => 1500,
        ]);

        $character->champion_material_progress = [];
        $character->save();

        $result2 = $championService->attemptLevelUp($character);
        $this->assertTrue($result2->isOk());

        $character->refresh();
        $this->assertEquals(1, $character->champion_level);

        $remainingShards = ItemInstance::where('owner_character_id', $character->id)
            ->where('template_id', $runicTemplate->id)
            ->first();

        $this->assertEquals(500, $remainingShards->stack_size);
    }
}
