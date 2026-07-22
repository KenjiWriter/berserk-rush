<?php

namespace App\Livewire\Auth;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;

class LoginModal extends Component
{
    public bool $showModal = false;
    public bool $hideButton = true;
    public string $email = '';
    public string $password = '';
    public bool $remember = false;

    protected $rules = [
        'email' => 'required|email',
        'password' => 'required|string|min:8',
    ];

    protected $messages = [
        'email.required' => 'Podaj adres email',
        'email.email' => 'Nieprawidłowy format email',
        'password.required' => 'Podaj hasło',
        'password.min' => 'Hasło musi mieć przynajmniej 8 znaków',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    #[On('open-login-modal')]
    public function openModal()
    {
        $this->showModal = true;
        $this->resetValidation();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->reset(['email', 'password', 'remember']);
        $this->resetValidation();
    }

    public function login()
    {
        $this->validate();

        if (Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            session()->regenerate();
            session()->forget('url.intended');
            $this->closeModal();
            $this->dispatch('user-logged-in');

            return redirect()->route('homepage');
        }

        throw ValidationException::withMessages([
            'email' => 'Nieprawidłowe dane logowania.',
        ]);
    }

    public function render()
    {
        return view('livewire.auth.login-modal');
    }
}
