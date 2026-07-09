<?php

namespace App\Livewire\City;

use App\Application\Items\EquipItem;
use App\Application\Items\UnequipItem;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Profile extends Component
{
    public Character $character;
    public string $activeTab = 'attributes';

    public function mount(Character $character)
    {
        $this->character = $character;
        
        // Ensure character belongs to user
        if (auth()->id() !== $character->user_id) {
            abort(403);
        }
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function equipItem(string $itemUlid, EquipItem $equipAction)
    {
        $item = ItemInstance::with('template')->find($itemUlid);
        
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $equipAction->handle($this->character, $item);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot założony pomyślnie.');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function unequipItem(string $itemUlid, UnequipItem $unequipAction)
    {
        $item = ItemInstance::find($itemUlid);
        
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $unequipAction->handle($this->character, $item);

        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot zdjęty pomyślnie.');
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
    }

    public function addAttribute(string $attribute, int $amount = 1)
    {
        $validAttributes = ['str', 'int', 'vit', 'agi'];
        if (!in_array($attribute, $validAttributes)) {
            return;
        }

        $points = $this->character->character_points ?? 0;
        
        // Use all available points if requested amount is very large (e.g. 999)
        if ($amount > $points) {
            $amount = $points;
        }

        if ($amount <= 0) {
            return;
        }

        $attributes = $this->character->getAttribute('attributes') ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0];
        $attributes[$attribute] = ($attributes[$attribute] ?? 0) + $amount;

        $this->character->attributes = $attributes;
        $this->character->character_points = $points - $amount;
        $this->character->save();

        $this->dispatch('notify', type: 'success', message: "Atrybut zwiększony o {$amount}.");
    }

    public function render()
    {
        $this->character->loadMissing(['equippedItems.template', 'inventoryItems.template']);

        $equipped = [];
        foreach ($this->character->equippedItems as $item) {
            $slot = $item->template->slot;
            if ($slot) {
                $equipped[$slot] = $item;
            }
        }
        
        $totalAttributes = $this->character->getTotalAttributes();
        
        // Derived stats
        $str = $totalAttributes['str'] ?? 0;
        $int = $totalAttributes['int'] ?? 0;
        $vit = $totalAttributes['vit'] ?? 0;
        $agi = $totalAttributes['agi'] ?? 0;
        $level = $this->character->level;
        
        $derivedStats = [
            'max_hp' => 100 + ($vit * 10) + ($level * 5),
            'base_damage' => 10 + ($str * 2) + ($level * 1),
            'magic_damage' => ($int * 2) + ($level * 1),
            'crit_chance' => min(50, 5 + ($agi * 0.5)),
            'dodge_chance' => min(50, 2 + ($agi * 0.3)),
            'damage_reduction' => $vit * 1,
        ];

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $this->character->inventoryItems,
            'totalAttributes' => $totalAttributes,
            'derivedStats' => $derivedStats,
        ]);
    }
}
