<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Application\Quests\QuestService;
use App\Infrastructure\Persistence\CharacterQuest;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class Quests extends Component
{
    public Character $character;
    public string $activeTab = 'quests';

    public function mount(Character $character)
    {
        if (auth()->user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

    public function acceptQuest($questId)
    {
        $questService = app(QuestService::class);
        $quest = \App\Infrastructure\Persistence\Quest::findOrFail($questId);

        $result = $questService->acceptQuest($this->character, $quest);

        if ($result->isOk()) {
            session()->flash('message', $result->getPayload());
            
            // Tutorial step
            if (auth()->user()->game_stage == 24) {
                auth()->user()->update(['game_stage' => 25]);
            }
        } else {
            session()->flash('error', $result->getErrorMessage());
        }
    }

    public function claimReward($characterQuestId)
    {
        $questService = app(QuestService::class);
        $cq = CharacterQuest::findOrFail($characterQuestId);

        $result = $questService->claimReward($this->character, $cq);

        if ($result->isOk()) {
            session()->flash('message', $result->getPayload());
            $this->dispatch('reward-claimed');
            
            // Tutorial step
            if (auth()->user()->game_stage == 27) {
                auth()->user()->update(['game_stage' => 28]);
            }
        } else {
            session()->flash('error', $result->getErrorMessage());
        }
    }

    public function claimAchievement($characterAchievementId)
    {
        $achievementService = app(\App\Application\Achievements\AchievementService::class);
        $ca = \App\Infrastructure\Persistence\CharacterAchievement::findOrFail($characterAchievementId);

        $result = $achievementService->claimReward($this->character, $ca);

        if ($result->isOk()) {
            session()->flash('message', $result->getPayload());
            $this->dispatch('reward-claimed');
        } else {
            session()->flash('error', $result->getErrorMessage());
        }
    }

    public function setTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function backToHub()
    {
        return redirect()->route('city.hub', $this->character);
    }

    public function getQuestHint($quest)
    {
        if ($quest->type->value === 'hunting') {
            $map = \App\Infrastructure\Persistence\Map::find($quest->target_id);
            if ($map) {
                return "Szukaj na mapie: " . $map->name;
            }
            
            $monster = \App\Infrastructure\Persistence\Monster::find($quest->target_id);
            if ($monster) {
                $maps = $monster->maps()->pluck('name')->join(', ');
                return "Występuje: " . ($maps ?: 'Nieznane miejsce');
            }
        } elseif ($quest->type->value === 'gathering') {
            $item = \App\Infrastructure\Persistence\ItemTemplate::find($quest->target_id);
            if ($item) {
                // Find monsters that drop this item
                $monsters = \App\Infrastructure\Persistence\Monster::whereHas('lootTables', function($q) use ($item) {
                    $q->where('item_template_id', $item->id);
                })->with('maps')->get();

                if ($monsters->isNotEmpty()) {
                    $hints = [];
                    foreach ($monsters as $m) {
                        $maps = $m->maps->pluck('name')->join(', ');
                        $hints[] = "{$m->name} (" . ($maps ?: 'Nieznane') . ")";
                    }
                    return "Wypada z: " . implode(' | ', $hints);
                } else {
                    return "Brak informacji o występowaniu tego przedmiotu.";
                }
            }
        }

        return null;
    }

    public function getQuestRequirement($quest)
    {
        if ($quest->type->value === 'hunting') {
            $amount = $quest->target_amount;
            
            // Check if target is map or monster
            $map = \App\Infrastructure\Persistence\Map::find($quest->target_id);
            if ($map) {
                return "Pokonaj potwory ($amount szt.) na mapie: " . $map->name;
            }
            
            $monster = \App\Infrastructure\Persistence\Monster::find($quest->target_id);
            if ($monster) {
                return "Pokonaj potwora: " . $monster->name . " ($amount szt.)";
            }
            
            return "Pokonaj przeciwników ($amount szt.)";
        } elseif ($quest->type->value === 'gathering') {
            $amount = $quest->target_amount;
            $item = \App\Infrastructure\Persistence\ItemTemplate::find($quest->target_id);
            
            if ($item) {
                return "Zdobądź i przynieś: " . $item->name . " ($amount szt.)";
            }
            
            return "Zdobądź przedmioty ($amount szt.)";
        }
        
        return "Wykonaj zadanie";
    }

    public function render()
    {
        $questService = app(QuestService::class);
        $availableQuests = $questService->getAvailableQuests($this->character);
        
        $activeQuests = $this->character->characterQuests()
            ->with('quest')
            ->whereIn('status', [\App\Domain\Quests\Enums\QuestStatus::ACTIVE->value, \App\Domain\Quests\Enums\QuestStatus::COMPLETED->value])
            ->get();

        $completedQuests = $this->character->characterQuests()
            ->with('quest')
            ->where('status', \App\Domain\Quests\Enums\QuestStatus::REWARDED->value)
            ->get();

        $achievements = [];
        if ($this->activeTab === 'achievements') {
            $achievements = \App\Infrastructure\Persistence\Achievement::with(['title', 'itemTemplate', 'characterAchievements' => function ($query) {
                $query->where('character_id', $this->character->id);
            }])
            ->where(function ($query) {
                $query->whereNull('parent_achievement_id')
                      ->orWhereHas('parentAchievement.characterAchievements', function ($subQuery) {
                          $subQuery->where('character_id', $this->character->id)
                                   ->whereNotNull('completed_at');
                      });
            })
            ->get();
        }

        return view('livewire.city.quests', [
            'availableQuests' => $availableQuests,
            'activeQuests' => $activeQuests,
            'completedQuests' => $completedQuests,
            'achievements' => $achievements,
        ]);
    }
}
