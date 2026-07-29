<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class GameSettings extends Component
{
    public Character $character;

    public function mount(Character $character)
    {
        if (auth()->user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

    public function backToHub()
    {
        return redirect()->route('city.hub', $this->character);
    }

    public function render()
    {
        return view('livewire.city.game-settings');
    }
}
