<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class Adventure extends Component
{
    public Character $character;
    public Collection $maps;

    public function mount(Character $character): void
    {
        // Sprawdź autoryzację
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
        $this->loadMaps();
    }

    private function loadMaps(): void
    {
        $this->maps = Map::orderBy('level_min')->get();
    }

    public function enterMap(string $mapId): void
    {
        $map = Map::findOrFail($mapId);

        if (!$map->isAccessibleBy($this->character)) {
            $this->addError('map_access', 'Twój poziom nie pozwala na wejście na tę mapę.');
            return;
        }

        // Redirect to encounter list (stub for now)
        $this->redirect(
            route('adventure.map', ['character' => $this->character, 'map' => $map]),
            navigate: true
        );
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render()
    {
        return view('livewire.city.adventure');
    }
}
