<?php

use App\Application\Items\ItemSorter;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('ItemSorter sorts items by category and power strength descending', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'SorterTester',
        'class' => 'warrior',
        'level' => 50,
        'experience' => 0,
        'gold' => 1000,
    ]);

    // Create templates
    $weakSwordTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Drewniany Miecz',
        'type' => 'weapon',
        'sub_type' => 'sword',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => ['attack_min' => 10, 'attack_max' => 20],
    ]);

    $strongSwordTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Miecz Zagłady',
        'type' => 'weapon',
        'sub_type' => 'sword',
        'slot' => 'main_hand',
        'level_requirement' => 50,
        'base_stats' => ['attack_min' => 150, 'attack_max' => 300],
    ]);

    $helmetTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Hełm Smoka',
        'type' => 'armor',
        'sub_type' => 'helmet',
        'slot' => 'head',
        'level_requirement' => 45,
        'base_stats' => ['defense' => 50],
    ]);

    $chestTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Zbroja Płytowa',
        'type' => 'armor',
        'sub_type' => 'armor',
        'slot' => 'chest',
        'level_requirement' => 40,
        'base_stats' => ['defense' => 80],
    ]);

    $potionTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Duża Mikstura HP',
        'type' => 'consumable',
        'sub_type' => 'potion',
        'slot' => 'consumable',
        'level_requirement' => 1,
        'base_stats' => [],
    ]);

    // Create item instances in mixed order
    $weakSword = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $weakSwordTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'rarity' => 'common',
        'upgrade_level' => 0,
    ]);

    $potion = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $potionTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'rarity' => 'common',
        'upgrade_level' => 0,
    ]);

    $helmet = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $helmetTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'rarity' => 'rare',
        'upgrade_level' => 5,
    ]);

    $strongSword = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $strongSwordTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'rarity' => 'epic',
        'upgrade_level' => 9,
    ]);

    $chest = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $chestTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'rarity' => 'rare',
        'upgrade_level' => 3,
    ]);

    $unsorted = collect([$weakSword, $potion, $helmet, $strongSword, $chest]);
    $sorted = ItemSorter::sort($unsorted);

    // Expected order:
    // 1. Strong Sword (weapon, higher power)
    // 2. Weak Sword (weapon, lower power)
    // 3. Helmet (armor head)
    // 4. Chest armor (armor chest)
    // 5. Potion (consumable)
    expect($sorted->pluck('id')->toArray())->toBe([
        $strongSword->id,
        $weakSword->id,
        $helmet->id,
        $chest->id,
        $potion->id,
    ]);
});
