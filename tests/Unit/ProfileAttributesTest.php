<?php

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Items\EquipItem;
use App\Application\Items\UnequipItem;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

test('character clearStatsCache clears total attributes cache', function () {
    $character = new Character();
    $character->id = 999;
    $cacheKey = $character->getCacheKey('total_attributes');

    Cache::put($cacheKey, ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10], 3600);
    expect(Cache::has($cacheKey))->toBeTrue();

    $character->clearStatsCache();
    expect(Cache::has($cacheKey))->toBeFalse();
});

test('equipping and unequipping item updates total attributes correctly without stale relation cache', function () {
    $user = \App\Models\User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'EquipTester',
        'level' => 10,
        'attributes' => ['str' => 10, 'int' => 0, 'vit' => 0, 'agi' => 0],
    ]);

    $template = ItemTemplate::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'name' => 'Zardzewiały Miecz',
        'type' => 'weapon',
        'sub_type' => 'sword',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => ['attack_min' => 2, 'attack_max' => 4, 'str_bonus' => 1],
    ]);

    $item = ItemInstance::create([
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
    ]);

    // Initial attributes without weapon
    expect($character->getTotalAttributes()['str'])->toBe(10);

    // Equip item
    $equipAction = new EquipItem();
    $equipAction->handle($character, $item);

    $character->clearStatsCache();
    $character->refresh();
    expect($character->getTotalAttributes()['str'])->toBe(11);

    // Unequip item
    $unequipAction = new UnequipItem();
    $unequipAction->handle($character, $item);

    $character->clearStatsCache();
    $character->refresh();
    expect($character->getTotalAttributes()['str'])->toBe(10);
});
