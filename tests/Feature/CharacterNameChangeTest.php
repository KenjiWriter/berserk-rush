<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CharacterNameChangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_name_change_is_free()
    {
        $user = User::factory()->create(['gems' => 0]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'OldNick123',
            'level' => 1,
            'xp' => 0,
            'gold' => 100,
            'attributes' => ['str' => 5, 'int' => 5, 'agi' => 5, 'vit' => 5],
            'proficiencies' => [],
            'name_changes_count' => 0,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $character])
            ->set('newName', 'NewFreeNick')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('NewFreeNick', $character->fresh()->name);
        $this->assertEquals(1, $character->fresh()->name_changes_count);
        $this->assertEquals(0, $user->fresh()->gems);
    }

    public function test_second_name_change_requires_200_gems()
    {
        $user = User::factory()->create(['gems' => 100]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'FirstNick',
            'level' => 10,
            'xp' => 0,
            'gold' => 100,
            'attributes' => ['str' => 5, 'int' => 5, 'agi' => 5, 'vit' => 5],
            'proficiencies' => [],
            'name_changes_count' => 1,
        ]);

        $this->actingAs($user);

        // Fails with 100 gems
        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $character])
            ->set('newName', 'SecondNick')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'error')
            ->assertDispatched('not-enough-gems');

        $this->assertEquals('FirstNick', $character->fresh()->name);

        // Add 100 more gems (total 200)
        $user->update(['gems' => 200]);

        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $character])
            ->set('newName', 'SecondNick')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('SecondNick', $character->fresh()->name);
        $this->assertEquals(2, $character->fresh()->name_changes_count);
        $this->assertEquals(0, $user->fresh()->gems);
    }

    public function test_name_change_validates_uniqueness_and_length()
    {
        $user1 = User::factory()->create();
        $char1 = Character::create([
            'user_id' => $user1->id,
            'name' => 'ExistingNick',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'agi' => 5, 'vit' => 5],
        ]);

        $user2 = User::factory()->create();
        $char2 = Character::create([
            'user_id' => $user2->id,
            'name' => 'PlayerTwo',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'agi' => 5, 'vit' => 5],
        ]);

        $this->actingAs($user2);

        // Test name already taken
        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $char2])
            ->set('newName', 'ExistingNick')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'error');

        // Test name too short
        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $char2])
            ->set('newName', 'Ab')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'error');

        // Test name too long (> 16 chars)
        Livewire::test(\App\Livewire\City\Profile::class, ['character' => $char2])
            ->set('newName', 'ThisNameIsWayTooLong17')
            ->call('changeCharacterName')
            ->assertDispatched('notify', type: 'error');
    }
}
