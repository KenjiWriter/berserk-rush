<?php

namespace App\Livewire\City;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Infrastructure\Persistence\Character;
use App\Application\PvP\MatchmakingService;
use App\Application\PvP\PvPEncounterService;
use App\Infrastructure\Persistence\PvpEncounter;

class Arena extends Component
{
    use WithPagination;

    public Character $character;
    public array $opponents = [];
    public ?string $currentLeague = null;
    public string $activeTab = 'opponents'; // 'opponents', 'ranking', 'cp_ranking', or 'history'

    protected $queryString = [
        'activeTab' => ['except' => 'opponents'],
    ];

    public function mount(Character $character)
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        if ($character->level < 15) {
            session()->flash('error', 'Arena jest zablokowana! Twój poziom jest zbyt niski (wymagany 15 poziom).');
            return redirect()->route('city.hub', $character);
        }

        if (Auth::user()->game_stage < 38) {
            Auth::user()->update(['game_stage' => 38]);
            return redirect()->route('city.hub', $character);
        }

        Auth::user()->checkAndRepairTutorialStage($character);

        $this->character = $character;
        $this->character->checkAndResetDailyPvpFights();
        $this->currentLeague = $character->league ?? 'bronze';
        $this->loadOpponents();
    }

    public function switchTab(string $tab)
    {
        if (in_array($tab, ['opponents', 'ranking', 'cp_ranking', 'history'])) {
            $this->activeTab = $tab;
            $this->resetPage();
        }
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
        if ($defenderId === $this->character->id) {
            session()->flash('error', 'Nie możesz wyzwać samego siebie.');
            return;
        }

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
        $ranking = null;
        $rankingEquipment = [];
        if ($this->activeTab === 'ranking') {
            $ranking = Character::query()
                ->with('user')
                ->where('level', '>=', 15)
                ->orderBy('elo', 'desc')
                ->orderBy('level', 'desc')
                ->paginate(10);

            foreach ($ranking as $rowChar) {
                $rankingEquipment[$rowChar->id] = $rowChar->getEquipmentSlotsFor('pvp');
            }
        }

        $cpRanking = null;
        $cpRankingEquipment = [];
        $cpValues = [];
        if ($this->activeTab === 'cp_ranking') {
            $allChars = Character::query()
                ->with(['user', 'itemInstances.template', 'equipmentSetItems.itemInstance.template', 'activePet'])
                ->where('level', '>=', 15)
                ->get();

            $sortedChars = $allChars->sortByDesc(function (Character $char) use (&$cpValues) {
                $cp = $char->getTotalCombatPower('pvp');
                $cpValues[$char->id] = $cp;
                return $cp;
            })->values();

            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = 10;
            $itemsForCurrentPage = $sortedChars->slice(($currentPage - 1) * $perPage, $perPage)->values();

            $cpRanking = new LengthAwarePaginator(
                $itemsForCurrentPage,
                $sortedChars->count(),
                $perPage,
                $currentPage,
                ['path' => LengthAwarePaginator::resolveCurrentPath(), 'query' => request()->query()]
            );

            foreach ($cpRanking as $rowChar) {
                $cpRankingEquipment[$rowChar->id] = $rowChar->getEquipmentSlotsFor('pvp');
            }
        }

        $history = null;
        $historyEquipment = [];
        if ($this->activeTab === 'history') {
            $history = PvpEncounter::with(['attacker.user', 'defender.user', 'winner'])
                ->forCharacter($this->character->id)
                ->finished()
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            foreach ($history as $fight) {
                $oppChar = $fight->attacker_character_id === $this->character->id 
                    ? $fight->defender 
                    : $fight->attacker;
                if ($oppChar) {
                    $historyEquipment[$oppChar->id] = $oppChar->getEquipmentSlotsFor('pvp');
                }
            }
        }

        $opponentEquipment = [];
        foreach ($this->opponents as $opponent) {
            if ($opponent instanceof Character) {
                $opponentEquipment[$opponent->id] = $opponent->getEquipmentSlotsFor('pvp');
            }
        }

        return view('livewire.city.arena', [
            'ranking' => $ranking,
            'cpRanking' => $cpRanking,
            'cpValues' => $cpValues,
            'history' => $history,
            'opponentEquipment' => $opponentEquipment,
            'rankingEquipment' => $rankingEquipment,
            'cpRankingEquipment' => $cpRankingEquipment,
            'historyEquipment' => $historyEquipment,
        ]);
    }
}
