<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileManagementModal extends Component
{
    public bool $showModal = false;
    public string $activeTab = 'password'; // 'password' or 'delete'

    // Password change fields
    public string $current_password = '';
    public string $password = '';
    public string $password_confirmation = '';

    // Deletion code field
    public string $deletion_code = '';

    // Account deletion password confirmation field
    public string $delete_password = '';

    public string $successMessage = '';
    public string $errorMessage = '';

    public function openModal(): void
    {
        $this->resetInputFields();
        if (Auth::check()) {
            $this->deletion_code = Auth::user()->deletion_code ?? '';
        }
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetInputFields();
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetErrorBag();
        $this->successMessage = '';
        $this->errorMessage = '';
    }

    public function resetInputFields(): void
    {
        $this->reset(['current_password', 'password', 'password_confirmation', 'delete_password', 'successMessage', 'errorMessage']);
        $this->resetErrorBag();
    }

    public function updatePassword(): void
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'confirmed', Password::defaults()],
        ];

        // Require current password if user has a password set
        if (!empty($user->password)) {
            $rules['current_password'] = ['required', 'string'];
        }

        $this->validate($rules, [
            'current_password.required' => 'Wprowadź obecne hasło',
            'password.required' => 'Podaj nowe hasło',
            'password.confirmed' => 'Nowe hasła muszą być identyczne',
        ]);

        if (!empty($user->password) && !Hash::check($this->current_password, $user->password)) {
            $this->addError('current_password', 'Podane obecne hasło jest nieprawidłowe.');
            return;
        }

        $user->forceFill([
            'password' => Hash::make($this->password),
        ])->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        $this->successMessage = 'Hasło zostało pomyślnie zmienione.';
    }

    public function updateDeletionCode(): void
    {
        $this->validate([
            'deletion_code' => 'required|string|min:7|max:50',
        ], [
            'deletion_code.required' => 'Kod usunięcia postaci jest wymagany.',
            'deletion_code.min' => 'Kod usunięcia postaci musi mieć co najmniej 7 znaków.',
            'deletion_code.max' => 'Kod usunięcia postaci może mieć maksymalnie 50 znaków.',
        ]);

        Auth::user()->update([
            'deletion_code' => $this->deletion_code,
        ]);

        $this->successMessage = 'Kod usunięcia postaci został pomyślnie zaktualizowany.';
    }

    public function deleteAccount(): void
    {
        $user = Auth::user();

        if (!empty($user->password)) {
            $this->validate([
                'delete_password' => 'required|string',
            ], [
                'delete_password.required' => 'Wprowadź swoje hasło, aby potwierdzić usunięcie konta',
            ]);

            if (!Hash::check($this->delete_password, $user->password)) {
                $this->addError('delete_password', 'Podane hasło jest nieprawidłowe.');
                return;
            }
        }

        Auth::logout();

        $user->delete();

        session()->invalidate();
        session()->regenerateToken();

        session()->flash('status', 'Twoje konto oraz wszystkie związane z nim dane zostały pomyślnie usunięte.');

        $this->redirectRoute('homepage');
    }

    public function render()
    {
        return view('livewire.auth.profile-management-modal');
    }
}
