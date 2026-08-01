<?php

use App\Application\Pets\IncubatorService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('consolidateStackableItems merges separate egg instances into a single stack', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'EggStackTester',
        'class' => 'warrior',
        'level' => 20,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $rareEggTemplate = ItemTemplate::create([
        'id' => 'egg-rare-test',
        'name' => 'Rzadkie Jajo Chowańca',
        'type' => 'egg',
        'level_requirement' => 10,
    ]);

    // Create 3 separate egg instances
    $egg1 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $rareEggTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
        'rarity' => 'rare',
    ]);

    $egg2 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $rareEggTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 1,
        'rarity' => 'rare',
    ]);

    $egg3 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $rareEggTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 2,
        'rarity' => 'rare',
    ]);

    // Consolidate stackable items
    $character->consolidateStackableItems();

    $remainingEggs = ItemInstance::where('owner_character_id', $character->id)
        ->where('template_id', $rareEggTemplate->id)
        ->where('location', 'inventory')
        ->get();

    expect($remainingEggs->count())->toBe(1);
    expect($remainingEggs->first()->stack_size)->toBe(4);
});

test('placing egg from stack into incubator splits off 1 egg and retains remaining stack in inventory', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'IncubatorStackTester',
        'class' => 'warrior',
        'level' => 20,
        'experience' => 0,
        'gold' => 1000,
    ]);

    $rareEggTemplate = ItemTemplate::create([
        'id' => 'egg-rare-test-2',
        'name' => 'Rzadkie Jajo Chowańca',
        'type' => 'egg',
        'sub_type' => 'rare',
        'egg_tier' => 2,
        'level_requirement' => 10,
    ]);

    $eggStack = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $rareEggTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'stack_size' => 4,
        'rarity' => 'rare',
    ]);

    $service = app(IncubatorService::class);
    $result = $service->placeEgg($character, $eggStack->id);

    expect($result->isOk())->toBeTrue();

    // Re-query egg stack in inventory
    $eggStack->refresh();
    expect($eggStack->stack_size)->toBe(3);
    expect($eggStack->location)->toBe('inventory');

    // Verify egg in incubator
    $incubator = CharacterIncubator::where('character_id', $character->id)->first();
    expect($incubator)->not->toBeNull();
    expect($incubator->egg_item_instance_id)->not->toBe($eggStack->id);

    $incubatorEgg = ItemInstance::find($incubator->egg_item_instance_id);
    expect($incubatorEgg)->not->toBeNull();
    expect($incubatorEgg->location)->toBe('incubator');
    expect($incubatorEgg->stack_size)->toBe(1);
});
