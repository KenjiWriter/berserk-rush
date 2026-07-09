<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;

class Weaponsmith extends Component
{
    public Character $character;

    public string $activeTab = 'buy';
    
    public bool $showUpgradeModal = false;
    public string $upgradeModalTitle = '';
    public string $upgradeModalMessage = '';
    public string $upgradeModalType = 'success';

    public function closeUpgradeModal()
    {
        $this->showUpgradeModal = false;
    }

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function buyItem(int $merchantItemId, \App\Application\Items\ShopService $shop)
    {
        $merchantItem = \App\Infrastructure\Persistence\MerchantItem::with('template')->findOrFail($merchantItemId);
        $result = $shop->buyItem($this->character, $merchantItem);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
    }

    public function sellItem(string $itemInstanceId, \App\Application\Items\ShopService $shop)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        $result = $shop->sellItem($this->character, $item);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
    }

    public function upgradeItem(string $itemInstanceId, \App\Application\Items\UpgradeService $upgrade)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        $result = $upgrade->upgradeItem($this->character, $item);
        
        $this->upgradeModalType = $result['success'] ? 'success' : 'error';
        $this->upgradeModalTitle = $result['success'] ? 'Sukces!' : 'Niepowodzenie';
        $this->upgradeModalMessage = $result['message'];
        $this->showUpgradeModal = true;
        
        $this->character->refresh();
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function render(\App\Application\Items\ShopService $shopService, \App\Application\Items\UpgradeService $upgradeService)
    {
        $shopItems = \App\Infrastructure\Persistence\MerchantItem::where('merchant_id', 'weaponsmith')
            ->where('required_level', '<=', $this->character->level)
            ->with('template')
            ->get()
            ->filter(function($mi) {
                return !$mi->is_limited || $mi->sold_quantity < $mi->max_quantity;
            });
        
        $shopPrices = [];
        foreach($shopItems as $mi) {
            $shopPrices[$mi->id] = $shopService->getBuyPrice($mi->template);
        }

        // Sell all types of items (inventory + equipped)
        $inventoryItems = $this->character->inventoryItems()->with('template')->get()->merge(
            $this->character->equippedItems()->with('template')->get()
        );
        $sellPrices = [];
        foreach($inventoryItems as $item) {
            $sellPrices[$item->id] = $shopService->getSellPrice($item);
        }

        // Upgrade only weapons
        $upgradableItems = $this->character->inventoryItems()->whereHas('template', function($q) {
            $q->where('type', 'weapon');
        })->get()->merge(
            $this->character->equippedItems()->whereHas('template', function($q) {
                $q->where('type', 'weapon');
            })->get()
        );
        
        $upgradeCosts = [];
        foreach($upgradableItems as $item) {
            $upgradeCosts[$item->id] = $upgradeService->getUpgradeCost($item);
        }
        
        $inventoryMaterials = $this->character->inventoryItems()->whereHas('template', function($q) {
            $q->where('type', 'material');
        })->get();

        return view('livewire.city.weaponsmith', [
            'shopItems' => $shopItems,
            'shopPrices' => $shopPrices,
            'inventoryItems' => $inventoryItems,
            'sellPrices' => $sellPrices,
            'upgradableItems' => $upgradableItems,
            'upgradeCosts' => $upgradeCosts,
            'inventoryMaterials' => $inventoryMaterials
        ]);
    }
}
