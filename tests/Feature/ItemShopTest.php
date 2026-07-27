<?php

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Livewire\ItemShop\ItemShopComponent;
use Livewire\Livewire;

test('user with enough gems can reset attributes of all characters', function () {
    $user = User::factory()->create([
        'gems' => 100,
    ]);

    $character1 = Character::create([
        'user_id' => $user->id,
        'name' => 'HeroOne',
        'level' => 3,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 8, 'int' => 4, 'vit' => 2, 'agi' => 2], // total 16
        'character_points' => 0,
    ]);

    $character2 = Character::create([
        'user_id' => $user->id,
        'name' => 'HeroTwo',
        'level' => 1,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 5, 'int' => 2, 'vit' => 2, 'agi' => 1], // total 10
        'character_points' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ItemShopComponent::class)
        ->call('resetAttributes')
        ->assertDispatched('notify', message: 'Zresetowano atrybuty wszystkich postaci pomyślnie!', type: 'success');

    $user->refresh();
    expect($user->gems)->toBe(50);

    $character1->refresh();
    expect($character1->attributes)->toBe(['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0]);
    // Level 3 total points = 10 + (3 - 1) * 3 = 16
    expect($character1->character_points)->toBe(16);

    $character2->refresh();
    expect($character2->attributes)->toBe(['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0]);
    // Level 1 total points = 10 + (1 - 1) * 3 = 10
    expect($character2->character_points)->toBe(10);
});

test('user without enough gems cannot reset attributes', function () {
    $user = User::factory()->create([
        'gems' => 20,
    ]);

    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'PoorHero',
        'level' => 5,
        'xp' => 0,
        'gold' => 0,
        'attributes' => ['str' => 10, 'int' => 5, 'vit' => 5, 'agi' => 2],
        'character_points' => 0,
    ]);

    Livewire::actingAs($user)
        ->test(ItemShopComponent::class)
        ->call('resetAttributes')
        ->assertDispatched('not-enough-gems');

    $user->refresh();
    expect($user->gems)->toBe(20);

    $character->refresh();
    expect($character->attributes)->toBe(['str' => 10, 'int' => 5, 'vit' => 5, 'agi' => 2]);
});
