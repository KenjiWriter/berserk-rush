<?php

use App\Infrastructure\Persistence\Character;
use App\Livewire\City\Profile;
use App\Models\User;
use Livewire\Livewire;

test('rpg profile page is displayed for user with character', function () {
    $user = User::factory()->create(['game_stage' => 35]);
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'ProfileHero',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
        'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
    ]);

    $this->actingAs($user);

    $response = $this->get(route('city.profile', $character));

    $response->assertOk();
});

test('rpg profile livewire component loads character data', function () {
    $user = User::factory()->create(['game_stage' => 35]);
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'ProfileHero',
        'class' => 'warrior',
        'level' => 10,
        'experience' => 0,
        'gold' => 1000,
        'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
    ]);

    $this->actingAs($user);

    Livewire::test(Profile::class, ['character' => $character])
        ->assertSee('ProfileHero');
});
