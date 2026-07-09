<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;
use App\Application\Wizard\EnchantItem;
use App\Application\Wizard\RerollEnchantments;
use App\Infrastructure\Persistence\ItemInstance;

class Wizard extends Component
{
    public Character $character;
    
    public bool $showEnchantModal = false;
    public string $selectedItemId = '';
    public string $enchantModalTitle = '';
    public string $enchantModalMessage = '';
    public string $enchantModalType = 'success';

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function closeEnchantModal()
    {
        $this->showEnchantModal = false;
        $this->selectedItemId = '';
    }

    public function openEnchantModal(string $itemInstanceId)
    {
        $this->selectedItemId = $itemInstanceId;
        $this->enchantModalType = 'info';
        $this->enchantModalTitle = 'Zaklinanie Przedmiotu';
        $this->enchantModalMessage = 'Wybierz metodę zaklinania. Co wolisz poświęcić?';
        $this->showEnchantModal = true;
    }

    public function enchant(string $currencyType, EnchantItem $enchantItemAction)
    {
        if (!$this->selectedItemId) return;
        
        $item = ItemInstance::find($this->selectedItemId);
        if (!$item) return;

        try {
            $result = $enchantItemAction->execute($this->character->user, $item, $currencyType);
            $this->enchantModalType = 'success';
            $this->enchantModalTitle = 'Sukces!';
            $this->enchantModalMessage = 'Przedmiot został pomyślnie zaklęty. Dodano nowy bonus!';
            $this->character->refresh();
        } catch (\Exception $e) {
            $this->enchantModalType = 'error';
            $this->enchantModalTitle = 'Niepowodzenie';
            $this->enchantModalMessage = $e->getMessage();
        }
    }

    public function reroll(string $currencyType, RerollEnchantments $rerollAction)
    {
        if (!$this->selectedItemId) return;
        
        $item = ItemInstance::find($this->selectedItemId);
        if (!$item) return;

        try {
            $result = $rerollAction->execute($this->character->user, $item, $currencyType);
            $this->enchantModalType = 'success';
            $this->enchantModalTitle = 'Sukces!';
            $this->enchantModalMessage = 'Bonusy przedmiotu zostały wylosowane na nowo!';
            $this->character->refresh();
        } catch (\Exception $e) {
            $this->enchantModalType = 'error';
            $this->enchantModalTitle = 'Niepowodzenie';
            $this->enchantModalMessage = $e->getMessage();
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
        ]);
    }
}
