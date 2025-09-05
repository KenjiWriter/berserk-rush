<?php

namespace App\Livewire\Adventure;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Application\Combat\EncounterStub;
use Illuminate\Support\Facades\Auth;

class MapStub extends Component
{
    public Character $character;
    public Map $map;
    public string $background;
    public array $player;
    public array $enemy;
    public array $turns;
    public int $playbackSpeed = 1;
    public bool $isPlaying = false;
    public int $currentTurn = 0;
    public string $result;
    public string $first;

    public function mount(Character $character, Map $map): void
    {
        // Authorization check
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        // Level range warning (not blocking for now)
        if (!$map->isAccessibleBy($character)) {
            session()->flash('warning', "Ostrzeżenie: Twój poziom ({$character->level}) może nie być odpowiedni dla tej mapy (poziom {$map->level_range}).");
        }

        $this->character = $character;
        $this->map = $map;
        $this->background = $this->backgroundFor($map);

        // Generate fake encounter
        $encounter = app(EncounterStub::class)->generateFakeEncounter($character, $map);

        $this->player = $encounter['player'];
        $this->enemy = $encounter['enemy'];
        $this->turns = $encounter['turns'];
        $this->result = $encounter['result'];
        $this->first = $encounter['first'];
    }

    public function togglePlayback(): void
    {
        $this->isPlaying = !$this->isPlaying;

        if ($this->isPlaying && $this->currentTurn < count($this->turns)) {
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        } else {
            $this->dispatch('stop-playback');
        }
    }

    public function setPlaybackSpeed(int $speed): void
    {
        $this->playbackSpeed = $speed;

        if ($this->isPlaying) {
            $this->dispatch('update-playback-speed', speed: $speed);
        }
    }

    public function nextTurn(): void
    {
        if ($this->currentTurn < count($this->turns)) {
            $this->currentTurn++;

            if ($this->currentTurn >= count($this->turns)) {
                $this->isPlaying = false;
                $this->dispatch('encounter-finished', result: $this->result);
            }
        }
    }

    public function resetEncounter(): void
    {
        $this->currentTurn = 0;
        $this->isPlaying = false;
        $this->dispatch('stop-playback');
    }

    public function backToAdventure(): void
    {
        $this->redirect(route('city.adventure', $this->character), navigate: true);
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    private function backgroundFor(Map $map): string
    {
        return match ($map->name) {
            'Mroczny Las' => asset('img/maps/backgrounds/dark-forest.png'),
            'Stare Ruiny' => asset('img/maps/old-ruins.png'),
            'Jaskinia Trolli' => asset('img/maps/troll-cave.png'),
            'Pustkowia Orków' => asset('img/maps/orc-wasteland.png'),
            'Bagna Grozy' => asset('img/maps/horror-swamps.png'),
            'Góry Cienia' => asset('img/maps/shadow-mountains.png'),
            'Wieża Magów' => asset('img/maps/shadow-mountains.png'), // Fallback
            'Skażone Miasto' => asset('img/maps/corrupted-city.png'),
            default => asset('img/maps/default.jpg'),
        };
    }

    public function render()
    {
        return view('livewire.adventure.map-stub');
    }
}
