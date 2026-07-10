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
    public string $inventoryFilter = 'all';

    // Market Selling
    public ?string $sellingItemUlid = null;
    public int $sellPrice = 100;
    public string $sellCurrency = 'gold';
    public int $sellDuration = 24;

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

    public function setInventoryFilter(string $filter)
    {
        $this->inventoryFilter = $filter;
    }

    public function stackItems()
    {
        $character = $this->character;
        $inventory = $character->inventoryItems()->get();
        
        $groups = $inventory->groupBy('template_id');
        
        foreach ($groups as $templateId => $items) {
            $template = $items->first()->template;
            // Only stack specific types
            if (in_array($template->type, ['material', 'consumable', 'currency'])) {
                if ($items->count() > 1) {
                    $firstItem = $items->first();
                    $totalStack = $items->sum('stack_size');
                    
                    $firstItem->stack_size = $totalStack;
                    $firstItem->save();
                    
                    // delete other items
                    foreach ($items->skip(1) as $item) {
                        $item->delete();
                    }
                }
            }
        }
        
        $this->dispatch('notify', type: 'success', message: 'Ekwipunek został uporządkowany.');
        $this->character->refresh();
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

    public function openSellModal(string $itemUlid)
    {
        $this->sellingItemUlid = $itemUlid;
        $this->sellPrice = 100;
        $this->sellCurrency = 'gold';
        $this->sellDuration = 24;
    }

    public function closeSellModal()
    {
        $this->sellingItemUlid = null;
    }

    public function sellItem(\App\Application\Economy\Actions\CreateMarketListingAction $action)
    {
        if (!$this->sellingItemUlid) return;
        
        $item = ItemInstance::find($this->sellingItemUlid);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Przedmiot nie istnieje.');
            return;
        }

        $result = $action->execute($this->character, $item, (int) $this->sellPrice, $this->sellCurrency, (int) $this->sellDuration);
        
        if ($result->isOk()) {
            $this->dispatch('notify', type: 'success', message: 'Przedmiot wystawiony na market!');
            $this->closeSellModal();
            $this->character->refresh();
        } else {
            $this->dispatch('notify', type: 'error', message: $result->getErrorMessage());
        }
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
        
        $eqStats = $this->character->getEquipmentStats();
        
        $baseDmg = 10 + ($str * 2) + ($level * 1);
        $magicDmg = ($int * 2) + ($level * 1);

        $derivedStats = [
            'max_hp' => 100 + ($vit * 10) + ($level * 5) + $eqStats['hp_bonus'],
            'base_damage_min' => $baseDmg + $eqStats['attack_min'],
            'base_damage_max' => $baseDmg + $eqStats['attack_max'],
            'magic_damage_min' => $magicDmg + $eqStats['magic_attack_min'],
            'magic_damage_max' => $magicDmg + $eqStats['magic_attack_max'],
            'crit_chance' => min(50, 5 + ($agi * 0.5) + $eqStats['crit_chance']),
            'dodge_chance' => min(50, 2 + ($agi * 0.3)),
            'damage_reduction' => ($vit * 1) + $eqStats['defense'],
        ];

        $inventory = $this->character->inventoryItems;
        if ($this->inventoryFilter !== 'all') {
            $inventory = $inventory->filter(function ($item) {
                return $item->template->type === $this->inventoryFilter;
            });
        }

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $inventory,
            'totalAttributes' => $totalAttributes,
            'derivedStats' => $derivedStats,
        ]);
    }
}
