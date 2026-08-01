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
    public string $deletion_code = '';
    public bool $terms = false;
    public bool $privacy = false;

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255|min:3',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Password::defaults()],
            'deletion_code' => 'required|string|min:7|max:50',
            'terms' => 'accepted',
            'privacy' => 'accepted',
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
        'deletion_code.required' => 'Podaj kod usunięcia postaci',
        'deletion_code.min' => 'Kod usunięcia postaci musi mieć co najmniej 7 znaków',
        'deletion_code.max' => 'Kod usunięcia postaci może mieć maksymalnie 50 znaków',
        'terms.accepted' => 'Musisz zaakceptować regulamin gry',
        'privacy.accepted' => 'Musisz zaakceptować politykę prywatności',
    ];

    public function mount()
    {
        if (Auth::check()) {
            return redirect()->route('homepage');
        }
    }

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
            'deletion_code' => $validated['deletion_code'],
        ]);

        Auth::login($user);

        session()->regenerate();
        session()->forget('url.intended');

        return redirect()->route('homepage');
    }

    public function render()
    {
        return view('livewire.auth.register');
    }
}
