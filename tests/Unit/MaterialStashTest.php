<?php

use App\Application\Items\ShopService;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('Character inventoryItems excludes materials and materialStashItems returns material_stash location items', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'StashTester',
        'level' => 1,
        'gold' => 1000,
    ]);

    $equipTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Zardzewiały Miecz',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
    ]);

    $materialTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Prastara Kora',
        'type' => 'material',
        'slot' => null,
        'level_requirement' => 1,
    ]);

    ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $equipTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
    ]);

    ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $materialTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'material_stash',
        'stack_size' => 5,
    ]);

    expect($character->inventoryItems()->count())->toBe(1)
        ->and($character->materialStashItems()->count())->toBe(1)
        ->and($character->getMaterialStashCapacity())->toBe(100)
        ->and($character->getMaterialStashCount())->toBe(1)
        ->and($character->isMaterialStashFull())->toBeFalse();
});

test('ShopService buys materials into material_stash location', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'BuyerHero',
        'level' => 1,
        'gold' => 5000,
    ]);

    $matTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Magiczny Mech',
        'type' => 'material',
        'slot' => null,
        'level_requirement' => 1,
    ]);

    $shopService = app(ShopService::class);
    $result = $shopService->buyItem($character, $matTemplate, 3);

    expect($result['success'])->toBeTrue();

    $materialInstance = ItemInstance::where('owner_character_id', $character->id)
        ->where('template_id', $matTemplate->id)
        ->first();

    expect($materialInstance)->not->toBeNull()
        ->and($materialInstance->location)->toBe('material_stash')
        ->and($materialInstance->stack_size)->toBe(3);
});
