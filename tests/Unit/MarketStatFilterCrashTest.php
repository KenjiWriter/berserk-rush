<?php

use App\Application\Economy\Queries\GetMarketListingsQuery;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\MarketListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('stats filter does not crash when template base_stats stores a [min,max] range', function () {
    $user = User::factory()->create();
    $seller = Character::create([
        'user_id' => $user->id,
        'name' => 'Seller',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Zardzewialy Helm',
        'type' => 'armor',
        'slot' => 'head',
        'level_requirement' => 1,
        'is_tradeable' => true,
        'base_stats' => ['defense' => [1, 3], 'hp_bonus' => [33, 57]],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $seller->id,
        'location' => 'market',
        'stack_size' => 1,
        'rarity' => 'common',
        'upgrade_level' => 0,
        'roll_stats' => ['hp_bonus' => 40],
    ]);

    MarketListing::create([
        'id' => (string) Str::ulid(),
        'item_instance_id' => $item->id,
        'seller_character_id' => $seller->id,
        'price' => 100,
        'currency' => 'gold',
        'status' => 'active',
        'expires_at' => now()->addDay(),
    ]);

    $query = new GetMarketListingsQuery();

    $listings = $query->execute(['stats' => ['hp_bonus']]);

    expect($listings->total())->toBe(1);
});
