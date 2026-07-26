<?php

use App\Application\Items\ShopService;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('ShopService sells partial stack when selling specific quantity', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'TestHero1',
        'class' => 'warrior',
        'level' => 1,
        'experience' => 0,
        'gold' => 100,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Zioło lecznicze',
        'type' => 'material',
        'slot' => null,
        'level_requirement' => 1,
        'base_stats' => [],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 20,
    ]);

    $shopService = new ShopService();
    $unitPrice = $shopService->getSellPrice($item);

    $result = $shopService->sellItem($character, $item, 5);

    expect($result['success'])->toBeTrue()
        ->and($result['goldAdded'])->toBe($unitPrice * 5)
        ->and($character->fresh()->gold)->toBe(100 + ($unitPrice * 5));

    $freshItem = ItemInstance::find($item->id);
    expect($freshItem)->not->toBeNull()
        ->and($freshItem->stack_size)->toBe(15);
});

test('ShopService sells all items when quantity is set to all or exceeds stack', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'TestHero2',
        'class' => 'mage',
        'level' => 1,
        'experience' => 0,
        'gold' => 0,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Ruda żelaza',
        'type' => 'material',
        'slot' => null,
        'level_requirement' => 1,
        'base_stats' => [],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 8,
    ]);

    $shopService = new ShopService();
    $unitPrice = $shopService->getSellPrice($item);

    $result = $shopService->sellItem($character, $item, 'all');

    expect($result['success'])->toBeTrue()
        ->and($result['goldAdded'])->toBe($unitPrice * 8)
        ->and($character->fresh()->gold)->toBe($unitPrice * 8);

    $freshItem = ItemInstance::find($item->id);
    expect($freshItem)->toBeNull();
});

test('ShopService sells multiple items in bulk', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'TestHero3',
        'class' => 'warrior',
        'level' => 1,
        'experience' => 0,
        'gold' => 50,
    ]);

    $template1 = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Stary Miecz',
        'type' => 'weapon',
        'slot' => 'weapon',
        'level_requirement' => 1,
        'base_stats' => [],
    ]);

    $template2 = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Drewniana Tarcza',
        'type' => 'armor',
        'slot' => 'shield',
        'level_requirement' => 1,
        'base_stats' => [],
    ]);

    $item1 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template1->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
        'rarity' => 'common',
    ]);

    $item2 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template2->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 3,
        'rarity' => 'common',
    ]);

    $shopService = new ShopService();
    $price1 = $shopService->getSellPrice($item1) * 1;
    $price2 = $shopService->getSellPrice($item2) * 3;
    $expectedTotal = $price1 + $price2;

    $result = $shopService->sellMultipleItems($character, [$item1->id, $item2->id]);

    expect($result['success'])->toBeTrue()
        ->and($result['goldAdded'])->toBe($expectedTotal)
        ->and($result['soldCount'])->toBe(4)
        ->and($character->fresh()->gold)->toBe(50 + $expectedTotal);

    expect(ItemInstance::find($item1->id))->toBeNull()
        ->and(ItemInstance::find($item2->id))->toBeNull();
});

test('ShopService sellMultipleItems returns error on empty or invalid selection', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'TestHero4',
        'class' => 'mage',
        'level' => 1,
        'experience' => 0,
        'gold' => 0,
    ]);

    $shopService = new ShopService();

    $resultEmpty = $shopService->sellMultipleItems($character, []);
    expect($resultEmpty['success'])->toBeFalse()
        ->and($resultEmpty['message'])->toContain('Nie wybrano');

    $resultInvalid = $shopService->sellMultipleItems($character, [(string) Str::ulid()]);
    expect($resultInvalid['success'])->toBeFalse()
        ->and($resultInvalid['message'])->toContain('Nie znaleziono');
});
