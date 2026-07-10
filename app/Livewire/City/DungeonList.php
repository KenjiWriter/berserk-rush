<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use Illuminate\Support\Facades\Auth;

class DungeonList extends Component
{
    public Character $character;

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

    public function enterDungeon(int $dungeonId): void
    {
        $dungeon = Dungeon::findOrFail($dungeonId);

        $this->redirect(
            route('city.dungeon.run', [$this->character, $dungeon]),
            navigate: true
        );
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render()
    {
        $dungeons = Dungeon::with(['stages', 'entryItemTemplate'])->get();

        $activeRun = CharacterDungeonRun::where('character_id', $this->character->id)
            ->where('is_completed', false)
            ->where('is_failed', false)
            ->first();

        return view('livewire.city.dungeon-list', [
            'dungeons' => $dungeons,
            'activeRun' => $activeRun,
        ]);
    }
}
