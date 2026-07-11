<?php

namespace App\Livewire\City;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;
use App\Application\PvP\MatchmakingService;
use App\Application\PvP\PvPEncounterService;
use App\Infrastructure\Persistence\PvpEncounter;

class Arena extends Component
{
    public Character $character;
    public array $opponents = [];
    public ?string $currentLeague = null;

    public function mount(Character $character)
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
        $this->currentLeague = $character->league ?? 'bronze';
        $this->loadOpponents();
    }

    public function loadOpponents()
    {
        $matchmaking = app(MatchmakingService::class);
        $this->opponents = $matchmaking->findOpponents($this->character);
    }

    public function refreshOpponents()
    {
        // Check if free refresh is available or needs to be reset
        if ($this->character->pvp_refreshes_reset_at && $this->character->pvp_refreshes_reset_at->lt(now())) {
            $this->character->update([
                'pvp_refreshes_used' => 0,
                'pvp_refreshes_reset_at' => now()->addHour()
            ]);
        }

        if ($this->character->pvp_refreshes_used >= 3) {
            session()->flash('error', 'Wykorzystałeś wszystkie darmowe odświeżenia na tę godzinę.');
            return;
        }

        if (!$this->character->pvp_refreshes_reset_at) {
            $this->character->update(['pvp_refreshes_reset_at' => now()->addHour()]);
        }

        $this->character->increment('pvp_refreshes_used');
        $this->loadOpponents();
    }

    public function challengeOpponent(string $defenderId)
    {
        $defender = Character::findOrFail($defenderId);

        $encounterService = app(PvPEncounterService::class);
        $result = $encounterService->startEncounter($this->character, $defender);

        if ($result->isError()) {
            session()->flash('error', $result->getErrorMessage());
            return;
        }

        $encounter = $result->getPayload();

        return redirect()->route('city.arena.combat.pvp', [
            'character' => $this->character->id,
            'pvpId' => $encounter->id
        ]);
    }

    public function backToHub()
    {
        return redirect()->route('city.hub', $this->character);
    }

    public function goTo(string $route)
    {
        if ($route === 'gladiator') {
            return redirect()->route('city.gladiator', $this->character);
        }
        return redirect()->route('city.hub', $this->character);
    }

    public function render()
    {
        return view('livewire.city.arena');
    }
}
