<?php

use App\Application\Economy\Actions\BuyMarketListingAction;
use App\Application\Economy\Actions\CreateMarketListingAction;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MarketListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('allows listing an equipped item on market and updates location to market', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'SellerHero',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Ogniste Ostrze',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 5,
        'is_tradeable' => true,
        'base_stats' => ['attack_min' => 20, 'attack_max' => 30],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'equipped',
        'stack_size' => 1,
        'rarity' => 'rare',
        'upgrade_level' => 3,
        'roll_stats' => [
            'str_bonus' => 5,
            'enchants' => ['hp_bonus' => 50],
        ],
    ]);

    $action = new CreateMarketListingAction();
    $result = $action->execute($character, $item, 500, 'gold', 24);

    expect($result->isOk())->toBeTrue();

    $freshItem = ItemInstance::find($item->id);
    expect($freshItem->location)->toBe('market')
        ->and($freshItem->owner_character_id)->toBeNull();

    $listing = MarketListing::where('item_instance_id', $item->id)->first();
    expect($listing)->not->toBeNull()
        ->and($listing->price)->toBe(500)
        ->and($listing->status)->toBe('active');
});

test('market listing preserves all item bonuses when bought by another player', function () {
    $sellerUser = User::factory()->create();
    $seller = Character::create([
        'user_id' => $sellerUser->id,
        'name' => 'Seller',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 500,
    ]);

    $buyerUser = User::factory()->create();
    $buyer = Character::create([
        'user_id' => $buyerUser->id,
        'name' => 'Buyer',
        'class' => 'mage',
        'level' => 10,
        'experience' => 0,
        'gold' => 2000,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Epicki Pancerz',
        'type' => 'armor',
        'slot' => 'chest',
        'level_requirement' => 5,
        'is_tradeable' => true,
        'base_stats' => ['defense' => 50],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $seller->id,
        'location' => 'inventory',
        'stack_size' => 1,
        'rarity' => 'epic',
        'upgrade_level' => 5,
        'roll_stats' => [
            'vit_bonus' => 12,
            'enchants' => [
                'defense' => 15,
                'hp_bonus' => 100,
            ],
        ],
    ]);

    $createAction = new CreateMarketListingAction();
    $createResult = $createAction->execute($seller, $item, 1000, 'gold', 24);
    expect($createResult->isOk())->toBeTrue();

    $listing = $createResult->getPayload()['listing'];

    $buyAction = new BuyMarketListingAction();
    $buyResult = $buyAction->execute($buyer, $listing);

    expect($buyResult->isOk())->toBeTrue();

    $boughtItem = ItemInstance::find($item->id);
    expect($boughtItem->owner_character_id)->toBe($buyer->id)
        ->and($boughtItem->location)->toBe('inventory')
        ->and($boughtItem->rarity)->toBe('epic')
        ->and($boughtItem->upgrade_level)->toBe(5)
        ->and($boughtItem->roll_stats['vit_bonus'])->toBe(12)
        ->and($boughtItem->roll_stats['enchants']['hp_bonus'])->toBe(100);
});

test('listing part of a stack from inventory (e.g. eggs) only removes the listed quantity', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'EggSeller',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Rzadkie Jajo Chowańca',
        'type' => 'egg',
        'level_requirement' => 5,
        'is_tradeable' => true,
    ]);

    $eggStack = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 4,
        'rarity' => 'rare',
    ]);

    $action = new CreateMarketListingAction();
    $result = $action->execute($character, $eggStack, 500, 'gold', 24, 2);

    expect($result->isOk())->toBeTrue();

    $remainingStack = ItemInstance::find($eggStack->id);
    expect($remainingStack->location)->toBe('inventory')
        ->and($remainingStack->stack_size)->toBe(2);

    $listing = $result->getPayload()['listing'];
    $listedItem = ItemInstance::find($listing->item_instance_id);
    expect($listedItem->id)->not->toBe($eggStack->id)
        ->and($listedItem->location)->toBe('market')
        ->and($listedItem->stack_size)->toBe(2);
});
