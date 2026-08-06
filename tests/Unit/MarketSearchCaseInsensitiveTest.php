<?php

use App\Application\Economy\Queries\GetMarketListingsQuery;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MarketListing;
use App\Infrastructure\Persistence\Pet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('market item search is case-insensitive', function () {
    $user = User::factory()->create();
    $seller = Character::create([
        'user_id' => $user->id,
        'name' => 'SellerCharacter',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Królewski Miecz Przeznaczenia',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'is_tradeable' => true,
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $seller->id,
        'location' => 'market',
        'stack_size' => 1,
        'rarity' => 'rare',
        'upgrade_level' => 0,
    ]);

    MarketListing::create([
        'id' => (string) Str::ulid(),
        'item_instance_id' => $item->id,
        'seller_character_id' => $seller->id,
        'price' => 500,
        'currency' => 'gold',
        'status' => 'active',
        'expires_at' => now()->addDay(),
    ]);

    $query = new GetMarketListingsQuery();

    // Uppercase search
    $resultUpper = $query->execute(['search' => 'KRÓLEWSKI MIECZ']);
    expect($resultUpper->total())->toBe(1);

    // Lowercase search
    $resultLower = $query->execute(['search' => 'królewski miecz']);
    expect($resultLower->total())->toBe(1);

    // Mixed case search
    $resultMixed = $query->execute(['search' => 'kRóLeWsKi MiEcZ']);
    expect($resultMixed->total())->toBe(1);

    // Partial search with mixed case
    $resultPartial = $query->execute(['search' => 'mIeCz']);
    expect($resultPartial->total())->toBe(1);
});

test('market pet search is case-insensitive', function () {
    $user = User::factory()->create();
    $seller = Character::create([
        'user_id' => $user->id,
        'name' => 'PetSellerCharacter',
        'class' => 'mage',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $pet = Pet::create([
        'id' => (string) Str::ulid(),
        'character_id' => $seller->id,
        'name' => 'Ognisty Smok',
        'tier' => 2,
        'level' => 5,
        'experience' => 100,
        'location' => 'market',
    ]);

    MarketListing::create([
        'id' => (string) Str::ulid(),
        'pet_id' => $pet->id,
        'seller_character_id' => $seller->id,
        'price' => 1000,
        'currency' => 'gold',
        'status' => 'active',
        'expires_at' => now()->addDay(),
    ]);

    $query = new GetMarketListingsQuery();

    // Uppercase search
    $resultUpper = $query->execute(['type' => 'pet', 'search' => 'OGNISTY SMOK']);
    expect($resultUpper->total())->toBe(1);

    // Lowercase search
    $resultLower = $query->execute(['type' => 'pet', 'search' => 'ognisty smok']);
    expect($resultLower->total())->toBe(1);

    // Mixed case search
    $resultMixed = $query->execute(['type' => 'pet', 'search' => 'oGnIsTy SmOk']);
    expect($resultMixed->total())->toBe(1);
});
