<?php

namespace App\Livewire\Global;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Character;

class TutorialOverlay extends Component
{
    public int $step = 1;
    public bool $isVisible = true;
    public ?string $rewardItemTemplateId = null;
    public int $rewardXp = 0;
    public int $rewardGold = 0;
    public ?ItemTemplate $rewardItem = null;

    public function mount(int $step = 1, ?string $rewardItemTemplateId = null, int $rewardXp = 0, int $rewardGold = 0)
    {
        $this->step = $step;
        $this->rewardItemTemplateId = $rewardItemTemplateId;
        $this->rewardXp = $rewardXp;
        $this->rewardGold = $rewardGold;
        
        $user = Auth::user();
        
        // We show it only if game_stage matches the step index (0-based or 1-based, here step-1)
        if ($user && $user->game_stage >= $this->step) {
            $this->isVisible = false;
        }

        if ($this->rewardItemTemplateId) {
            $this->rewardItem = ItemTemplate::find($this->rewardItemTemplateId);
        }
    }

    public function nextStep()
    {
        $user = Auth::user();
        if ($user) {
            if ($user->game_stage == $this->step - 1) {
                // Grant reward if present
                $activeCharacterId = session('active_character');
                $character = $activeCharacterId ? Character::where('id', $activeCharacterId)->where('user_id', $user->id)->first() : null;

                if ($this->rewardItemTemplateId && $this->rewardItem && $character) {
                    ItemInstance::create([
                        'template_id' => $this->rewardItem->id,
                        'owner_character_id' => $character->id,
                        'location' => 'inventory',
                        'stack_size' => 1,
                        'rarity' => 'common',
                        'roll_stats' => [],
                        'upgrade_level' => 0,
                        'bound_to_character' => true,
                    ]);
                }

                if ($this->rewardXp > 0 && $character) {
                    $character->xp += $this->rewardXp;
                }

                if ($this->rewardGold > 0 && $character) {
                    $character->gold += $this->rewardGold;
                }

                if ($character) {
                    $character->save();
                }

                $user->game_stage = $this->step;
                $user->save();

                if ($this->rewardGold > 0 || $this->rewardXp > 0) {
                    $this->dispatch('stats-updated', 
                        goldAdded: $this->rewardGold,
                        xpAdded: $this->rewardXp,
                        gemsAdded: 0
                    );
                }
            }
        }
        
        $this->isVisible = false;
        $this->dispatch('tutorial-completed', step: $this->step);
    }

    public function render()
    {
        return view('livewire.global.tutorial-overlay');
    }
}
