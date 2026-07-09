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
    
    public function mount(Character $character)
    {
        $this->character = $character;
        
        // Ensure character belongs to user
        if (auth()->id() !== $character->user_id) {
            abort(403);
        }
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

        return view('livewire.city.profile', [
            'equipped' => $equipped,
            'inventory' => $this->character->inventoryItems,
            'totalAttributes' => $this->character->getTotalAttributes(),
        ]);
    }
}
