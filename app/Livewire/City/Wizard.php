<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;
use App\Application\Wizard\EnchantItem;
use App\Application\Wizard\RerollEnchantments;
use App\Infrastructure\Persistence\ItemInstance;

use Livewire\Attributes\On;

class Wizard extends Component
{
    public Character $character;
    
    public ?string $activeItemId = null;
    public ?string $actionMessage = null;
    public ?string $actionType = null; // 'success' or 'error'

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        // Re-render component on tutorial step update
    }

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function selectItemToEnchant(string $itemInstanceId)
    {
        $this->activeItemId = $itemInstanceId;
        $this->clearMessages();
    }

    public function deselectItem()
    {
        $this->activeItemId = null;
        $this->clearMessages();
    }

    public function clearMessages()
    {
        $this->actionMessage = null;
        $this->actionType = null;
    }

    public function enchant(string $currencyType, EnchantItem $enchantItemAction)
    {
        $this->clearMessages();
        
        if (!$this->activeItemId) return;
        
        $item = ItemInstance::find($this->activeItemId);
        if (!$item) return;

        try {
            $result = $enchantItemAction->execute($item, $this->character, $currencyType);
            
            if ($result->isError()) {
                $this->actionType = 'error';
                $this->actionMessage = $result->getErrorMessage();
                $this->dispatch('play-audio', type: 'enchant-fail');
            } else {
                $payload = $result->getPayload();
                if ($payload['success'] ?? false) {
                    $this->actionType = 'success';
                    $this->actionMessage = $payload['message'] ?? 'Przedmiot został pomyślnie zaklęty. Dodano nowy bonus!';
                    $this->dispatch('play-audio', type: 'enchant-success');

                    // Tutorial step update
                    if (auth()->user()->game_stage == 32) {
                        auth()->user()->update(['game_stage' => 33]);
                    }
                } else {
                    $this->actionType = 'error';
                    $this->actionMessage = $payload['message'] ?? 'Zaklinanie nie powiodło się.';
                    $this->dispatch('play-audio', type: 'enchant-fail');
                }
                $this->character->refresh();
                $this->dispatch('stats-updated', gold: $this->character->gold, gems: $this->character->gems);
            }
        } catch (\Exception $e) {
            $this->actionType = 'error';
            $this->actionMessage = $e->getMessage();
            $this->dispatch('play-audio', type: 'enchant-fail');
        }
    }

    public function reroll(string $currencyType, RerollEnchantments $rerollAction)
    {
        $this->clearMessages();
        
        if (!$this->activeItemId) return;
        
        $item = ItemInstance::find($this->activeItemId);
        if (!$item) return;

        try {
            $result = $rerollAction->execute($item, $this->character, $currencyType);
            
            if ($result->isError()) {
                $this->actionType = 'error';
                $this->actionMessage = $result->getErrorMessage();
                $this->dispatch('play-audio', type: 'enchant-fail');
            } else {
                $payload = $result->getPayload();
                if ($payload['success'] ?? false) {
                    $this->actionType = 'success';
                    $this->actionMessage = $payload['message'] ?? 'Bonusy przedmiotu zostały wylosowane na nowo!';
                    $this->dispatch('play-audio', type: 'enchant-success');
                } else {
                    $this->actionType = 'error';
                    $this->actionMessage = $payload['message'] ?? 'Operacja nie powiodła się.';
                    $this->dispatch('play-audio', type: 'enchant-fail');
                }
                $this->character->refresh();
                $this->dispatch('stats-updated', gold: $this->character->gold, gems: $this->character->gems);
            }
        } catch (\Exception $e) {
            $this->actionType = 'error';
            $this->actionMessage = $e->getMessage();
            $this->dispatch('play-audio', type: 'enchant-fail');
        }
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render()
    {
        // Get upgradable/enchantable items (weapons, armor, accessory)
        $enchantableItems = $this->character->inventoryItems()->whereHas('template', function($q) {
            $q->whereIn('type', ['weapon', 'armor', 'accessory']);
        })->get()->merge(
            $this->character->equippedItems()->whereHas('template', function($q) {
                $q->whereIn('type', ['weapon', 'armor', 'accessory']);
            })->get()
        );

        return view('livewire.city.wizard', [
            'enchantableItems' => $enchantableItems,
            'activeItem' => $this->activeItemId ? $enchantableItems->firstWhere('id', $this->activeItemId) : null,
        ]);
    }
}
