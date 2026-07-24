<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Items\CraftingService;

class Weaponsmith extends Component
{
    public Character $character;

    public string $activeTab = 'shop'; // 'shop', 'forge', 'craft'
    public ?string $selectedUpgradeItemId = null;

    public function selectItemForUpgrade($itemId)
    {
        $this->selectedUpgradeItemId = $itemId;
        $this->activeTab = 'forge';
    }

    public function cancelUpgradeSelection()
    {
        $this->selectedUpgradeItemId = null;
    }
    
    #[\Livewire\Attributes\On('tutorial-completed')]
    public function onTutorialCompleted()
    {
        // Refresh component state
    }
    
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

    public function sellItem(string $itemInstanceId, \App\Application\Items\ShopService $shop)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        $result = $shop->sellItem($this->character, $item);
        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('stats-updated', goldAdded: $result['goldAdded'] ?? 0);
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
        
        $this->dispatch('play-audio', type: $result['success'] ? 'upgrade-success' : 'upgrade-fail');
        
        $this->character->refresh();
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function craftItem(string $recipeId, CraftingService $craftingService)
    {
        $recipe = ItemRecipe::find($recipeId);
        if (!$recipe) return;

        $result = $craftingService->craftItem($this->character, $recipe);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('play-audio', type: 'upgrade-success');
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }
        $this->character->refresh();
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

        $recipes = ItemRecipe::with('resultItemTemplate')->whereHas('resultItemTemplate', function($q) {
            $q->where('type', 'weapon');
        })->get();

        $preparedRecipes = [];
        foreach ($recipes as $recipe) {
            $preparedIngredients = [];
            $canCraft = $this->character->gold >= $recipe->gold_cost;

            foreach ($recipe->ingredients as $ing) {
                $mat = ItemTemplate::find($ing['template_id']);
                $owned = $inventoryMaterials->where('template_id', $ing['template_id'])->sum('stack_size');
                $req = $ing['quantity'];
                
                if ($owned < $req) $canCraft = false;

                $dropMonsters = [];
                if ($mat) {
                    $dropMonsters = \App\Infrastructure\Persistence\Monster::whereHas('lootTable.entries', function($q) use ($mat) {
                        $q->where('ref_ulid', $mat->id);
                    })->pluck('name')->toArray();
                }

                $preparedIngredients[] = [
                    'name' => $mat ? $mat->name : 'Nieznany',
                    'icon' => $mat ? $mat->icon : null,
                    'owned' => $owned,
                    'required' => $req,
                    'ok' => $owned >= $req,
                    'dropped_by' => $dropMonsters,
                ];
            }

            $preparedRecipes[] = [
                'id' => $recipe->id,
                'result_name' => $recipe->resultItemTemplate->name ?? 'Nieznany',
                'result_icon' => $recipe->resultItemTemplate->icon ?? null,
                'result_level' => $recipe->resultItemTemplate->level_requirement ?? 1,
                'result_type' => $recipe->resultItemTemplate->type ?? 'weapon',
                'result_stats' => $recipe->resultItemTemplate->base_stats ?? [],
                'gold_cost' => $recipe->gold_cost,
                'ingredients' => $preparedIngredients,
                'can_craft' => $canCraft,
            ];
        }

        $equipped = [];
        foreach($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.city.weaponsmith', [
            'shopItems' => $shopItems,
            'shopPrices' => $shopPrices,
            'inventoryItems' => $inventoryItems,
            'sellPrices' => $sellPrices,
            'upgradableItems' => $upgradableItems,
            'upgradeCosts' => $upgradeCosts,
            'inventoryMaterials' => $inventoryMaterials,
            'recipes' => $preparedRecipes,
            'equipped' => $equipped,
        ]);
    }
}
