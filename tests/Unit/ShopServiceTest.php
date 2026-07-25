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
