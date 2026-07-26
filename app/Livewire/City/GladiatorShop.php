<?php

namespace App\Livewire\City;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\MerchantItem;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;

class GladiatorShop extends Component
{
    public Character $character;
    public $merchantItems;

    public function mount(Character $character)
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
        $this->loadItems();
    }

    public function loadItems()
    {
        $this->merchantItems = MerchantItem::where('merchant_id', 'gladiator')
            ->with('template')
            ->get();
    }

    public function buyItem($itemId)
    {
        $item = MerchantItem::with('template')->findOrFail($itemId);

        if ($item->merchant_id !== 'gladiator') {
            session()->flash('error', 'Ten przedmiot nie jest dostępny u tego kupca.');
            $this->dispatch('notify', type: 'error', message: 'Ten przedmiot nie jest dostępny u tego kupca.');
            return;
        }

        if ($this->character->arena_tokens < $item->price) {
            session()->flash('error', 'Nie masz wystarczająco dużo Żetonów Areny!');
            $this->dispatch('notify', type: 'error', message: 'Nie masz wystarczająco dużo Żetonów Areny!');
            return;
        }

        if ($this->character->isBackpackFull()) {
            session()->flash('error', 'Twój plecak jest pełny!');
            $this->dispatch('notify', type: 'error', message: 'Twój plecak jest pełny!');
            return;
        }

        DB::transaction(function () use ($item) {
            // Deduct tokens
            $this->character->decrement('arena_tokens', $item->price);

            // Give item
            $template = $item->template;
            if ($template && in_array($template->type, ['material', 'consumable', 'currency'])) {
                $existingItem = ItemInstance::where('owner_character_id', $this->character->id)
                    ->where('template_id', $template->id)
                    ->where('location', 'inventory')
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('stack_size');
                } else {
                    ItemInstance::create([
                        'template_id' => $item->item_template_id,
                        'owner_character_id' => $this->character->id,
                        'location' => 'inventory',
                        'stack_size' => 1,
                        'rarity' => 'common',
                        'upgrade_level' => 0,
                    ]);
                }
            } else {
                ItemInstance::create([
                    'template_id' => $item->item_template_id,
                    'owner_character_id' => $this->character->id,
                    'location' => 'inventory',
                    'stack_size' => 1,
                    'rarity' => 'common',
                    'upgrade_level' => 0,
                ]);
            }

            session()->flash('success', "Kupiłeś {$item->template->name}!");
            $this->dispatch('notify', type: 'success', message: "Kupiłeś {$item->template->name}!");
            $this->dispatch('play-audio', type: 'buy');
        });

        $this->character->refresh();
        $this->loadItems();
    }

    public function backToArena()
    {
        return redirect()->route('city.arena', $this->character);
    }

    public function render()
    {
        $equipped = [];
        foreach($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.city.gladiator-shop', [
            'equipped' => $equipped
        ]);
    }
}
