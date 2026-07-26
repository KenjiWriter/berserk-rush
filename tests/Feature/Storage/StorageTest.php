<?php

namespace Tests\Feature\Storage;

use App\Application\Storage\GuildStashService;
use App\Application\Storage\PlayerStashService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StorageTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Character $character;
    private ItemTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'gems' => 200,
            'stash_slots' => 2,
        ]);

        $this->character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $this->user->id,
            'name' => 'WojownikTest',
            'level' => 10,
            'xp' => 0,
            'gold' => 1000,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
        ]);

        $this->template = ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Miecz Testowy',
            'type' => 'weapon',
            'slot' => 'main_hand',
            'level_requirement' => 1,
            'base_stats' => ['attack_min' => 10, 'attack_max' => 15],
        ]);
    }

    public function test_backpack_capacity_limit_standard_and_vip(): void
    {
        $this->assertEquals(32, $this->character->getBackpackCapacity());
        $this->assertFalse($this->character->isBackpackFull());

        // Make user VIP
        $this->user->update(['premium_until' => now()->addDays(7)]);
        $this->assertEquals(64, $this->character->refresh()->getBackpackCapacity());
    }

    public function test_player_stash_deposit_withdraw_and_expansion(): void
    {
        $stashService = app(PlayerStashService::class);

        $item1 = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $this->template->id,
            'owner_character_id' => $this->character->id,
            'location' => 'inventory',
        ]);

        $item2 = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $this->template->id,
            'owner_character_id' => $this->character->id,
            'location' => 'inventory',
        ]);

        $item3 = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $this->template->id,
            'owner_character_id' => $this->character->id,
            'location' => 'inventory',
        ]);

        // Deposit item 1 (1/2)
        $res1 = $stashService->deposit($this->character, $item1);
        $this->assertTrue($res1->isOk());
        $this->assertEquals('player_stash', $item1->fresh()->location);
        $this->assertEquals($this->user->id, $item1->fresh()->user_id);
        $this->assertNull($item1->fresh()->owner_character_id);

        // Deposit item 2 (2/2)
        $res2 = $stashService->deposit($this->character, $item2);
        $this->assertTrue($res2->isOk());

        // Deposit item 3 (3/2) -> should fail (stash full)
        $res3 = $stashService->deposit($this->character, $item3);
        $this->assertTrue($res3->isError());
        $this->assertEquals('STASH_FULL', $res3->getErrorCode());

        // Expand stash for 50 gems
        $expRes = $stashService->expandStash($this->user);
        $this->assertTrue($expRes->isOk());
        $this->assertEquals(150, $this->user->fresh()->gems);
        $this->assertEquals(3, $this->user->fresh()->stash_slots);

        // Deposit item 3 again -> now succeeds (3/3)
        $res3Retry = $stashService->deposit($this->character, $item3);
        $this->assertTrue($res3Retry->isOk());

        // Withdraw item 1 back to backpack
        $withRes = $stashService->withdraw($this->character, $item1);
        $this->assertTrue($withRes->isOk());
        $this->assertEquals('inventory', $item1->fresh()->location);
        $this->assertEquals($this->character->id, $item1->fresh()->owner_character_id);
        $this->assertNull($item1->fresh()->user_id);
    }

    public function test_guild_stash_deposit_and_withdraw(): void
    {
        $guild = Guild::create([
            'id' => (string) Str::ulid(),
            'name' => 'Gildia Testowa',
            'level' => 1,
            'min_level' => 1,
        ]);

        GuildMember::create([
            'guild_id' => $guild->id,
            'character_id' => $this->character->id,
            'role' => 'member',
        ]);

        $this->character->update(['guild_id' => $guild->id]);

        $guildStashService = app(GuildStashService::class);

        $item = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $this->template->id,
            'owner_character_id' => $this->character->id,
            'location' => 'inventory',
        ]);

        // Deposit into guild stash
        $depRes = $guildStashService->deposit($this->character, $item);
        $this->assertTrue($depRes->isOk());
        $this->assertEquals('guild_stash', $item->fresh()->location);
        $this->assertEquals($guild->id, $item->fresh()->guild_id);
        $this->assertNull($item->fresh()->owner_character_id);

        // Withdraw back from guild stash
        $withRes = $guildStashService->withdraw($this->character, $item);
        $this->assertTrue($withRes->isOk());
        $this->assertEquals('inventory', $item->fresh()->location);
        $this->assertEquals($this->character->id, $item->fresh()->owner_character_id);
        $this->assertNull($item->fresh()->guild_id);
    }
}
