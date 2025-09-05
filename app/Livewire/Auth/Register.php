<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class Register extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    protected $messages = [
        'name.required' => 'Podaj nazwę użytkownika',
        'name.min' => 'Nazwa musi mieć przynajmniej 3 znaki',
        'name.max' => 'Nazwa może mieć maksymalnie 255 znaków',
        'email.required' => 'Podaj adres email',
        'email.email' => 'Nieprawidłowy format email',
        'email.unique' => 'Ten email jest już zarejestrowany',
        'password.required' => 'Podaj hasło',
        'password.confirmed' => 'Hasła muszą być identyczne',
    ];

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }

    public function register()
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        session()->regenerate();

        return redirect()->intended('/');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
