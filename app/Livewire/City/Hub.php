<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Auth;

class Hub extends Component
{
    public Character $character;

    public function mount(Character $character): void
    {
        // Sprawdź czy postać należy do zalogowanego użytkownika
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;

        // Set active character in session
        session(['active_character' => $character->id]);
    }

    public function goTo(string $building): void
    {
        $route = match ($building) {
            'armorsmith' => route('city.armorsmith', $this->character),
            'weaponsmith' => route('city.weaponsmith', $this->character),
            'witch' => route('city.witch', $this->character),
            'adventure' => route('city.adventure', $this->character),
            default => route('city.hub', $this->character),
        };

        $this->redirect($route, navigate: true);
    }

    public function backToHomepage(): void
    {
        session()->forget('active_character');
        $this->redirect(route('homepage'), navigate: true);
    }

    public function render()
    {
        return view('livewire.city.hub');
    }
}
