<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Livewire\Auth\ProfileManagementModal;
use App\Livewire\Auth\Register;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_management_modal_can_be_rendered(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(ProfileManagementModal::class)
            ->assertSee('Zarządzaj profilem');
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileManagementModal::class)
            ->set('current_password', 'old-password-123')
            ->set('password', 'new-secure-password')
            ->set('password_confirmation', 'new-secure-password')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('successMessage', 'Hasło zostało pomyślnie zmienione.');

        $this->assertTrue(Hash::check('new-secure-password', $user->fresh()->password));
    }

    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileManagementModal::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'new-secure-password')
            ->set('password_confirmation', 'new-secure-password')
            ->call('updatePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));
    }

    public function test_user_can_delete_account_with_correct_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileManagementModal::class)
            ->set('delete_password', 'password123')
            ->call('deleteAccount')
            ->assertHasNoErrors()
            ->assertRedirect(route('homepage'));

        $this->assertGuest();
        $this->assertNull(User::find($user->id));
    }

    public function test_user_cannot_delete_account_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        Livewire::actingAs($user)
            ->test(ProfileManagementModal::class)
            ->set('delete_password', 'wrongpassword')
            ->call('deleteAccount')
            ->assertHasErrors(['delete_password']);

        $this->assertNotNull(User::find($user->id));
    }

    public function test_registration_requires_accepting_terms_and_privacy(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Bohater')
            ->set('email', 'bohater@example.com')
            ->set('password', 'Haslo1234!')
            ->set('password_confirmation', 'Haslo1234!')
            ->set('terms', false)
            ->set('privacy', false)
            ->call('register')
            ->assertHasErrors(['terms', 'privacy']);

        $this->assertDatabaseMissing('users', ['email' => 'bohater@example.com']);
    }

    public function test_registration_succeeds_when_terms_and_privacy_accepted(): void
    {
        Livewire::test(Register::class)
            ->set('name', 'Bohater')
            ->set('email', 'bohater@example.com')
            ->set('password', 'Haslo1234!')
            ->set('password_confirmation', 'Haslo1234!')
            ->set('terms', true)
            ->set('privacy', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('homepage'));

        $this->assertDatabaseHas('users', ['email' => 'bohater@example.com']);
    }

    public function test_terms_and_privacy_pages_are_accessible(): void
    {
        $this->get(route('terms'))->assertOk()->assertSee('Regulamin Gry Berserk Rush');
        $this->get(route('privacy'))->assertOk()->assertSee('Polityka Prywatności Berserk Rush');
    }
}
