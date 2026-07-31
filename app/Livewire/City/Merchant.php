<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Application\Items\ItemSorter;
use Illuminate\Support\Facades\Gate;

class Merchant extends Component
{
    public Character $character;

    public array $selectedItemIds = [];
    public bool $bulkSellMode = false;
    public string $playerItemFilter = 'items'; // 'items', 'materials'
    public string $slotFilter = 'all'; // 'all', 'weapon', 'chest', 'head', 'feet', 'neck', 'ring'
    public $tierFilter = 'all'; // 'all' or int (required_level tieru)

    public function setPlayerItemFilter(string $filter)
    {
        $this->playerItemFilter = $filter;
        $this->selectedItemIds = [];
    }

    public function setSlotFilter(string $filter)
    {
        $this->slotFilter = $filter;
    }

    public function setTierFilter($tier)
    {
        $this->tierFilter = $tier === 'all' ? 'all' : (int) $tier;
    }

    private function matchesSlotFilter(?string $slot): bool
    {
        if ($this->slotFilter === 'all') {
            return true;
        }

        if ($this->slotFilter === 'weapon') {
            return $slot === 'main_hand';
        }

        return $slot === $this->slotFilter;
    }

    private function matchesTierFilter(?int $requiredLevel): bool
    {
        if ($this->tierFilter === 'all') {
            return true;
        }

        return $requiredLevel === (int) $this->tierFilter;
    }

    public function toggleBulkSellMode()
    {
        $this->bulkSellMode = !$this->bulkSellMode;
        if (!$this->bulkSellMode) {
            $this->selectedItemIds = [];
        }
    }

    public function toggleSelectItem(string $itemInstanceId)
    {
        if (in_array($itemInstanceId, $this->selectedItemIds)) {
            $this->selectedItemIds = array_values(array_filter($this->selectedItemIds, fn($id) => $id !== $itemInstanceId));
        } else {
            $this->selectedItemIds[] = $itemInstanceId;
        }
    }

    public function selectByRarity(string $rarity)
    {
        $query = ($this->playerItemFilter === 'materials')
            ? $this->character->materialStashItems()
            : $this->character->inventoryItems();

        $items = $query->where('rarity', $rarity)->get();

        if ($this->playerItemFilter !== 'materials') {
            $items = $items->filter(fn($item) => $this->matchesSlotFilter($item->template->slot ?? null));
        }

        $matchingItemIds = $items->pluck('id')->toArray();

        if (empty($matchingItemIds)) return;

        $allSelected = true;
        foreach ($matchingItemIds as $id) {
            if (!in_array($id, $this->selectedItemIds)) {
                $allSelected = false;
                break;
            }
        }

        if ($allSelected) {
            $this->selectedItemIds = array_values(array_diff($this->selectedItemIds, $matchingItemIds));
        } else {
            $this->selectedItemIds = array_values(array_unique(array_merge($this->selectedItemIds, $matchingItemIds)));
        }
    }

    public function selectAllInventory()
    {
        $query = ($this->playerItemFilter === 'materials')
            ? $this->character->materialStashItems()
            : $this->character->inventoryItems();

        $items = $query->get();

        if ($this->playerItemFilter !== 'materials') {
            $items = $items->filter(fn($item) => $this->matchesSlotFilter($item->template->slot ?? null));
        }

        $allIds = $items->pluck('id')->toArray();
        if (count($this->selectedItemIds) === count($allIds)) {
            $this->selectedItemIds = [];
        } else {
            $this->selectedItemIds = $allIds;
        }
    }

    public function clearSelection()
    {
        $this->selectedItemIds = [];
    }

    public function sellSelectedItems(\App\Application\Items\ShopService $shop)
    {
        if (empty($this->selectedItemIds)) {
            $this->dispatch('notify', type: 'error', message: 'Wybierz przedmioty do sprzedaży.');
            return;
        }

        $result = $shop->sellMultipleItems($this->character, $this->selectedItemIds);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('stats-updated', goldAdded: $result['goldAdded'] ?? 0);
            $this->dispatch('play-audio', type: 'sell');
            $this->selectedItemIds = [];
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
    }

    #[\Livewire\Attributes\On('tutorial-completed')]
    public function onTutorialCompleted()
    {
        // Refresh component state
    }

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function buyItem(int $merchantItemId, \App\Application\Items\ShopService $shop)
    {
        $merchantItem = \App\Infrastructure\Persistence\MerchantItem::with('template')->findOrFail($merchantItemId);
        $result = $shop->buyItem($this->character, $merchantItem);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('play-audio', type: 'buy');

            $user = auth()->user();
            if ($user && $user->game_stage == 19 && $merchantItem->template->type === 'weapon') {
                $user->game_stage = 20;
                $user->save();
            }
            $this->character->refresh();
            $this->dispatch('stats-updated', gold: $this->character->gold);
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
    }

    public function sellItem(string $itemInstanceId, \App\Application\Items\ShopService $shop, int|string $quantity = 1)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        if (!$item) return;

        $result = $shop->sellItem($this->character, $item, $quantity);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('stats-updated', goldAdded: $result['goldAdded'] ?? 0);
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render(\App\Application\Items\ShopService $shopService)
    {
        $allShopItems = \App\Infrastructure\Persistence\MerchantItem::where('merchant_id', 'merchant')
            ->where('required_level', '<=', $this->character->level)
            ->whereHas('template')
            ->with('template')
            ->get()
            ->filter(function($mi) {
                return $mi->template && (!$mi->is_limited || $mi->sold_quantity < $mi->max_quantity);
            });

        $availableTiers = $allShopItems->pluck('required_level')->unique()->sort()->values();

        $shopItems = $allShopItems->filter(function($mi) {
            return $this->matchesSlotFilter($mi->template->slot ?? null)
                && $this->matchesTierFilter($mi->required_level);
        });

        $shopPrices = [];
        foreach($allShopItems as $mi) {
            if (!$mi->template) continue;
            $shopPrices[$mi->id] = $shopService->getBuyPrice($mi->template);
        }


        // Sell items based on playerItemFilter
        $rawItems = ($this->playerItemFilter === 'materials')
            ? $this->character->materialStashItems()->take(100)->get()
            : $this->character->inventoryItems()->get()
                ->filter(fn($item) => $this->matchesSlotFilter($item->template->slot ?? null));

        $inventoryItems = ItemSorter::sort($rawItems)->take(64);

        $sellPrices = [];
        foreach($inventoryItems as $item) {
            $sellPrices[$item->id] = $shopService->getSellPrice($item);
        }

        $equipped = [];
        foreach($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.city.merchant', [
            'shopItems' => $shopItems,
            'shopPrices' => $shopPrices,
            'inventoryItems' => $inventoryItems,
            'sellPrices' => $sellPrices,
            'equipped' => $equipped,
            'availableTiers' => $availableTiers,
        ]);
    }
}
