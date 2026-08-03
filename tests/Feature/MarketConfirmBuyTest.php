<?php

namespace Tests\Feature;

use App\Application\Economy\Actions\CreateMarketListingAction;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MarketListing;
use App\Livewire\Economy\MarketComponent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class MarketConfirmBuyTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_component_confirm_buy_opens_confirmation_modal_with_listing_details(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Character::create([
            'user_id' => $sellerUser->id,
            'name' => 'SellerPlayer',
            'class' => 'warrior',
            'level' => 10,
            'experience' => 0,
            'gold' => 1000,
        ]);

        $buyerUser = User::factory()->create();
        $buyer = Character::create([
            'user_id' => $buyerUser->id,
            'name' => 'BuyerPlayer',
            'class' => 'mage',
            'level' => 10,
            'experience' => 0,
            'gold' => 5000,
        ]);

        $template = ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Smoczy Miecz',
            'type' => 'weapon',
            'slot' => 'main_hand',
            'level_requirement' => 5,
            'is_tradeable' => true,
            'base_stats' => ['attack_min' => 50, 'attack_max' => 80],
        ]);

        $item = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $template->id,
            'owner_character_id' => $seller->id,
            'location' => 'inventory',
            'stack_size' => 1,
            'rarity' => 'epic',
            'upgrade_level' => 2,
        ]);

        $createAction = new CreateMarketListingAction();
        $createAction->execute($seller, $item, 2000, 'gold', 24);

        $listing = MarketListing::where('item_instance_id', $item->id)->first();

        $this->actingAs($buyerUser);

        Livewire::test(MarketComponent::class, ['character' => $buyer])
            ->assertSet('showConfirmBuyModal', false)
            ->assertSet('selectedListingId', null)
            ->call('confirmBuy', $listing->id)
            ->assertSet('showConfirmBuyModal', true)
            ->assertSet('selectedListingId', $listing->id)
            ->assertSee('POTWIERDZENIE ZAKUPU')
            ->assertSee('Smoczy Miecz')
            ->assertSee('2,000')
            ->call('closeConfirmBuyModal')
            ->assertSet('showConfirmBuyModal', false)
            ->assertSet('selectedListingId', null);
    }

    public function test_market_component_confirms_buy_and_completes_item_purchase_from_modal(): void
    {
        $sellerUser = User::factory()->create();
        $seller = Character::create([
            'user_id' => $sellerUser->id,
            'name' => 'SellerPlayer',
            'class' => 'warrior',
            'level' => 10,
            'experience' => 0,
            'gold' => 1000,
        ]);

        $buyerUser = User::factory()->create();
        $buyer = Character::create([
            'user_id' => $buyerUser->id,
            'name' => 'BuyerPlayer',
            'class' => 'mage',
            'level' => 10,
            'experience' => 0,
            'gold' => 5000,
        ]);

        $template = ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Magię Natchniony Topór',
            'type' => 'weapon',
            'slot' => 'main_hand',
            'level_requirement' => 1,
            'is_tradeable' => true,
            'base_stats' => ['attack_min' => 10, 'attack_max' => 20],
        ]);

        $item = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $template->id,
            'owner_character_id' => $seller->id,
            'location' => 'inventory',
            'stack_size' => 1,
            'rarity' => 'uncommon',
            'upgrade_level' => 0,
        ]);

        $createAction = new CreateMarketListingAction();
        $createAction->execute($seller, $item, 1000, 'gold', 24);

        $listing = MarketListing::where('item_instance_id', $item->id)->first();

        $this->actingAs($buyerUser);

        Livewire::test(MarketComponent::class, ['character' => $buyer])
            ->call('confirmBuy', $listing->id)
            ->assertSet('showConfirmBuyModal', true)
            ->call('buyItem')
            ->assertSet('showConfirmBuyModal', false)
            ->assertSet('selectedListingId', null)
            ->assertDispatched('notify', message: 'Przedmiot został pomyślnie kupiony!', type: 'success');

        $buyer->refresh();
        $this->assertEquals(4000, $buyer->gold);

        $purchasedItem = ItemInstance::find($item->id);
        $this->assertEquals($buyer->id, $purchasedItem->owner_character_id);
        $this->assertEquals('inventory', $purchasedItem->location);
    }
}
