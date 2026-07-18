<?php

namespace App\Livewire\City;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\MerchantItem;
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

    public function buyItem(int $itemId)
    {
        $item = MerchantItem::findOrFail($itemId);

        if ($item->merchant_id !== 'gladiator') {
            session()->flash('error', 'Ten przedmiot nie jest dostępny u tego kupca.');
            return;
        }

        if ($this->character->arena_tokens < $item->price) {
            session()->flash('error', 'Nie masz wystarczająco dużo Żetonów Areny!');
            return;
        }

        DB::transaction(function () use ($item) {
            // Deduct tokens
            $this->character->decrement('arena_tokens', $item->price);

            // Give item
            $this->character->items()->create([
                'item_template_id' => $item->item_template_id,
                'quantity' => 1,
            ]);

            session()->flash('success', "Kupiłeś {$item->template->name}!");
        });

        $this->character->refresh();
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
