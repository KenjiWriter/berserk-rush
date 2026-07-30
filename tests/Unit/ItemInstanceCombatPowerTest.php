<?php

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('getCombatPower does not crash on a chest whose base_stats stores a loot_table name', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'Owner',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $chestTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Skrzynia Testowa',
        'type' => 'consumable',
        'sub_type' => 'chest',
        'slot' => 'consumable',
        'level_requirement' => 1,
        'base_stats' => ['loot_table' => 'chest_testowa_loot'],
    ]);

    $chest = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $chestTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
        'rarity' => 'common',
        'upgrade_level' => 0,
        'roll_stats' => [],
    ]);

    expect($chest->getCombatPower())->toBe(0);
    expect($chest->getResolvedBaseStats())->toBe([]);
});
