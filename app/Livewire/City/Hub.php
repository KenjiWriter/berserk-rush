<?php

namespace App\Livewire\City;

use Livewire\Component;
use Livewire\Attributes\On;
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

        Auth::user()->checkAndRepairTutorialStage($character);
    }

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        Auth::user()->checkAndRepairTutorialStage($this->character);
    }

    public function goTo(string $building): void
    {
        Auth::user()->checkAndRepairTutorialStage($this->character);

        if ($building === 'quests' && auth()->user()->game_stage < 22) {
            // Postać ma już wymagany poziom, ale utknęła wcześniej w łańcuchu samouczka -
            // klik w zablokowaną Tablicę Wyzwań przeskakuje od razu do etapu z Kapitanem.
            if ($this->character->level >= 5) {
                Auth::user()->update(['game_stage' => 22]);
                return;
            }

            $this->dispatch('notify', type: 'error', message: 'Tablica Wyzwań jest zablokowana! Wbij 5 poziom postaci i wyczekuj rozkazów Kapitana.');
            return;
        }

        if ($building === 'guild' && auth()->user()->game_stage < 35) {
            // Postać ma już wymagany poziom, ale utknęła wcześniej w łańcuchu samouczka -
            // klik w zablokowaną Gildię przeskakuje od razu do etapu z Kapitanem.
            if ($this->character->level >= 10) {
                Auth::user()->update(['game_stage' => 35]);
                return;
            }

            $this->dispatch('notify', type: 'error', message: 'Gildia jest zablokowana! Wbij 10 poziom postaci i wyczekuj rozkazów Kapitana.');
            return;
        }

        $route = match ($building) {
            'profile' => route('city.profile', $this->character),
            'merchant' => route('city.merchant', $this->character),
            'blacksmith' => route('city.blacksmith', $this->character),
            'witch' => route('city.witch', $this->character),
            'wizard' => route('city.wizard', $this->character),
            'warlock' => route('city.warlock', $this->character),
            'market' => route('city.market', $this->character),
            'mailbox' => route('city.mailbox', $this->character),
            'adventure' => route('city.adventure', $this->character),
            'guild' => route('city.guild', $this->character),
            'arena' => route('city.arena', $this->character),
            'gladiator' => route('city.gladiator', $this->character),
            'quests' => route('city.quests', $this->character),
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
        $questService = app(\App\Application\Quests\QuestService::class);
        $availableQuestsCount = count($questService->getAvailableQuests($this->character));
        $completedQuestsCount = $this->character->characterQuests()->where('status', 'completed')->count();
        
        return view('livewire.city.hub', [
            'completedQuestsCount' => $completedQuestsCount + $availableQuestsCount
        ]);
    }
}
