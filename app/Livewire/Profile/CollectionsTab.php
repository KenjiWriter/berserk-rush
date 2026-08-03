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

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: $result->getPayload()['message'] ?? 'Nagroda odebrana!');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function selectTitle($titleId)
    {
        if (!empty($titleId)) {
            $hasTitle = $this->character->unlockedTitles()
                ->where('title_id', $titleId)
                ->where(function ($query) {
                    $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })
                ->exists();

            if (!$hasTitle) {
                $this->dispatch('notify', type: 'error', message: 'Nie posiadasz tego tytułu lub tytuł wygasł.');
                return;
            }
        } else {
            $titleId = null;
        }

        $this->character->active_title_id = $titleId;
        $this->character->save();
        $this->character->clearStatsCache();
        $this->dispatch('notify', type: 'success', message: 'Zmieniono aktywny tytuł.');
    }

    public function render()
    {
        $now = now();
        $unlockedTitles = $this->character->unlockedTitles()
            ->where(function ($query) use ($now) {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', $now);
            })
            ->with('title')
            ->get();

        return view('livewire.profile.collections-tab', [
            'titles' => $unlockedTitles,
        ]);
    }
}
