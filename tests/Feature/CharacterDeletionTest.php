<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CharacterDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_delete_character_with_matching_name_and_deletion_code(): void
    {
        $user = User::factory()->create([
            'deletion_code' => 'MojeTajne123',
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
            ->set('deleteCodeInput', 'MojeTajne123')
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', false);

        $this->assertDatabaseMissing('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_character_deletion_fails_if_deletion_code_is_too_short(): void
    {
        $user = User::factory()->create([
            'deletion_code' => 'MojeTajne123',
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
            ->set('deleteCharacterNameInput', 'BohaterImperium')
            ->set('deleteCodeInput', '123456') // 6 chars (min 7)
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', true)
            ->assertSee('Kod usunięcia postaci musi mieć co najmniej 7 znaków');

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
        ]);
    }

    public function test_character_deletion_fails_if_deletion_code_is_incorrect(): void
    {
        $user = User::factory()->create([
            'deletion_code' => 'PrawidlowyKod123',
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
            ->set('deleteCodeInput', 'BlednyKod123')
            ->call('confirmDeleteCharacter')
            ->assertSet('showDeleteModal', true)
            ->assertSee('Wprowadzono nieprawidłowy kod usunięcia');

        $this->assertDatabaseHas('characters', [
            'id' => $character->id,
        ]);
    }
}
