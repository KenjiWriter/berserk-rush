<?php

namespace App\Livewire\Profile;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterAchievement;
use App\Infrastructure\Persistence\Title;
use App\Application\Achievements\AchievementService;
use Livewire\Component;

class CollectionsTab extends Component
{
    public $character;

    protected $listeners = ['refreshCollections' => '$refresh'];

    public function mount(Character $character)
    {
        $this->character = $character;
    }

    public function claimReward($charAchievementId)
    {
        $charAchievement = CharacterAchievement::with('achievement')->find($charAchievementId);
        
        if (!$charAchievement) return;

        $service = app(AchievementService::class);
        $result = $service->claimReward($this->character, $charAchievement);

        if ($result->isSuccess()) {
            $this->dispatch('notify', type: 'success', message: $result->getMessage());
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function selectTitle($titleId)
    {
        // Sprawdź czy użytkownik posiada ten tytuł
        $hasTitle = $this->character->unlockedTitles()->where('title_id', $titleId)->exists();
        if (!$hasTitle && $titleId !== null) {
            $this->dispatch('notify', type: 'error', message: 'Nie posiadasz tego tytułu.');
            return;
        }

        $this->character->active_title_id = $titleId;
        $this->character->save();
        $this->character->clearStatsCache(); // Wymuś przeliczenie statystyk
        $this->dispatch('notify', type: 'success', message: 'Zmieniono aktywny tytuł.');
    }

    public function render()
    {
        $character = $this->character->loadMissing([
            'unlockedTitles.title'
        ]);

        return view('livewire.profile.collections-tab', [
            'titles' => $character->unlockedTitles,
        ]);
    }
}
