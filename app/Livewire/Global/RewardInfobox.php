<?php

namespace App\Livewire\Global;

use Livewire\Component;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Auth;

class RewardInfobox extends Component
{
    public $characterStats = [
        'avatar_image' => 'default.png',
        'title' => 'Początkujący',
        'nickname' => 'Gracz',
        'level' => 1,
        'experience' => 0,
        'experience_required' => 100,
        'gold' => 0,
        'gems' => 0,
    ];

    public function mount()
    {
        $this->loadCharacterStats();
    }

    // Usunięto handleStatsUpdated. Aktualizacje są obsługiwane całkowicie lokalnie w Alpine.js.

    private function loadCharacterStats()
    {
        $characterId = session('active_character');
        $character = $characterId ? \App\Infrastructure\Persistence\Character::find($characterId) : null;

        if ($character) {
            $this->characterStats = [
                'avatar_image' => $character->avatar, // this stores just the basename usually
                'title' => $character->activeTitle->name ?? 'Brak tytułu',
                'nickname' => $character->name,
                'level' => $character->level,
                'experience' => $character->xp,
                'experience_required' => app(\App\Application\Characters\LevelUpService::class)->xpToNext($character->level),
                'gold' => $character->gold,
                'gems' => $character->gems ?? 0, // Fallback if gems doesn't exist
            ];
        }
    }

    public function render()
    {
        return view('livewire.global.reward-infobox');
    }
}
