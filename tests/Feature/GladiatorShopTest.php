<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MerchantItem;
use App\Livewire\City\GladiatorShop;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GladiatorShopTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_can_buy_item_from_gladiator_shop(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'GladiatorHero',
            'level' => 10,
            'gold' => 1000,
            'arena_tokens' => 50,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $itemTemplate = ItemTemplate::create([
            'id' => '01h1234567890abcdef1234567',
            'name' => 'Miecz Gladiatora',
            'type' => 'weapon',
            'slot' => 'weapon',
            'rarity' => 'rare',
            'level_requirement' => 1,
            'base_stats' => ['attack_min' => 10, 'attack_max' => 15],
        ]);

        $merchantItem = MerchantItem::create([
            'merchant_id' => 'gladiator',
            'item_template_id' => $itemTemplate->id,
            'price' => 20,
        ]);

        Livewire::actingAs($user)
            ->test(GladiatorShop::class, ['character' => $character])
            ->call('buyItem', $merchantItem->id)
            ->assertHasNoErrors();

        $this->assertEquals(30, $character->fresh()->arena_tokens);
        $this->assertDatabaseHas('item_instances', [
            'owner_character_id' => $character->id,
            'template_id' => $itemTemplate->id,
            'location' => 'inventory',
        ]);
    }
}
