<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class CharacterDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_character_with_matching_name_and_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-password-123'),
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'WojownikTestowy',
            'level' => 5,
            'xp' => 100,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Homepage::class)
            ->call('openDeleteModal', $character->id)
            ->assertSet('showDeleteModal', true)
            ->assertSet('characterToDeleteName', 'WojownikTestowy')
            ->set('deleteCharacterNameInput', 'WojownikTestowy')
            ->set('deleteAccountPasswordInput', 'secret-password-123')
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_character_deletion_fails_if_name_does_not_match(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('secret-password-123'),
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'BohaterImperium',
            'level' => 10,
            'xp' => 200,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Homepage::class)
            ->call('openDeleteModal', $character->id)
            ->set('deleteCharacterNameInput', 'ZlaNazwa')
            ->set('deleteAccountPasswordInput', 'secret-password-123')
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', true)
            ->assertSee('Wpisana nazwa postaci jest nieprawidłowa');

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_character_deletion_fails_if_password_is_incorrect(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'MagMroku',
            'level' => 15,
            'xp' => 500,
        ]);

        $this->actingAs($user);

        Livewire::test(\App\Livewire\Homepage::class)
            ->call('openDeleteModal', $character->id)
            ->set('deleteCharacterNameInput', 'MagMroku')
            ->set('deleteAccountPasswordInput', 'wrong-password')
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', true)
            ->assertSee('Wprowadzono nieprawidłowe hasło');

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
        ]);
    }
}
