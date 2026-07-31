<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;
use App\Infrastructure\Persistence\ItemRecipe;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Items\CraftingService;
use App\Application\Items\ItemSorter;

class Blacksmith extends Component
{
    public Character $character;

    public string $activeTab = 'forge'; // 'forge', 'crafting'
    public ?string $selectedUpgradeItemId = null;

    // Filtr typu/slotu ekwipunku: all, weapon, head, chest, feet, ring, neck
    public string $itemFilter = 'all';

    public bool $showUpgradeModal = false;
    public string $upgradeModalTitle = '';
    public string $upgradeModalMessage = '';
    public string $upgradeModalType = 'success';

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function setTab(string $tab)
    {
        $this->activeTab = $tab;
    }

    public function setItemFilter(string $filter)
    {
        $this->itemFilter = $filter;
    }

    public function selectItemForUpgrade($itemId)
    {
        $this->selectedUpgradeItemId = $itemId;
        $this->activeTab = 'forge';
    }

    public function cancelUpgradeSelection()
    {
        $this->selectedUpgradeItemId = null;
    }

    public function closeUpgradeModal()
    {
        $this->showUpgradeModal = false;
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
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

        $this->dispatch('stats-updated', gold: $this->character->gold);
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

        $this->dispatch('stats-updated', gold: $this->character->gold);
    }

    /**
     * Sprawdza, czy dany szablon (typ + slot) pasuje do aktualnie wybranego filtra Kowala.
     */
    private function matchesItemFilter(?string $type, ?string $slot): bool
    {
        if ($this->itemFilter === 'all') {
            return true;
        }

        if ($this->itemFilter === 'weapon') {
            return $type === 'weapon';
        }

        // head, chest, feet - slot zbroi; ring, neck - slot akcesoriów
        return $slot === $this->itemFilter;
    }

    public function render(\App\Application\Items\UpgradeService $upgradeService)
    {
        // Ulepszanie: broń oraz zbroja w jednym, ogólnym widoku Kowala
        $upgradableItems = $this->character->inventoryItems()->whereHas('template', function ($q) {
            $q->whereIn('type', ['weapon', 'armor', 'accessory']);
        })->take(64)->get()->merge(
            $this->character->equippedItems()->whereHas('template', function ($q) {
                $q->whereIn('type', ['weapon', 'armor', 'accessory']);
            })->get()
        );

        $upgradableItems = $upgradableItems->filter(function ($item) {
            return $this->matchesItemFilter($item->template->type ?? null, $item->template->slot ?? null);
        });
        $upgradableItems = ItemSorter::sort($upgradableItems);

        // Koszt ulepszenia liczony wyłącznie dla aktualnie wybranego przedmiotu (widok i tak
        // pokazuje tylko $upgradeCosts[$selectedUpgradeItemId]) - liczenie go dla całej listy
        // (do kilkudziesięciu przedmiotów, każdy z własnymi zapytaniami o zasady/materiały/dropy)
        // przy każdym renderze komponentu powodowało zauważalny lag przy przełączaniu zakładek.
        $upgradeCosts = [];
        if ($this->selectedUpgradeItemId) {
            $selectedItem = $upgradableItems->firstWhere('id', $this->selectedUpgradeItemId);
            if ($selectedItem) {
                $upgradeCosts[$selectedItem->id] = $upgradeService->getUpgradeCost($selectedItem);
            }
        }

        $inventoryMaterials = $this->character->items()
            ->whereIn('location', ['material_stash', 'inventory'])
            ->whereHas('template', function ($q) {
                $q->where('type', 'material');
            })->get();

        // Rzemiosło: przepisy na broń oraz zbroję w jednym widoku
        $recipes = ItemRecipe::with('resultItemTemplate')->whereHas('resultItemTemplate', function ($q) {
            $q->whereIn('type', ['weapon', 'armor', 'accessory']);
        })->get();

        $recipes = $recipes->filter(function ($recipe) {
            return $this->matchesItemFilter(
                $recipe->resultItemTemplate->type ?? null,
                $recipe->resultItemTemplate->slot ?? null
            );
        })->sortBy(fn ($recipe) => $recipe->resultItemTemplate->level_requirement ?? 1)->values();

        // Batch fetch monster drops to eliminate N+1 queries
        $allMatIds = [];
        foreach ($recipes as $recipe) {
            foreach ($recipe->ingredients as $ing) {
                if (!empty($ing['template_id'])) {
                    $allMatIds[] = $ing['template_id'];
                }
            }
        }
        $allMatIds = array_unique($allMatIds);

        // Batch fetch materiał templates (eliminuje N+1 z pojedynczych ItemTemplate::find() w pętli niżej)
        $materialTemplates = !empty($allMatIds)
            ? ItemTemplate::whereIn('id', $allMatIds)->get()->keyBy('id')
            : collect();

        $monsterDropsMap = [];
        if (!empty($allMatIds)) {
            $entries = \App\Infrastructure\Persistence\LootTableEntry::whereIn('ref_ulid', $allMatIds)
                ->whereHas('lootTable.monsters')
                ->with('lootTable.monsters.map')
                ->get();
            foreach ($entries as $entry) {
                if ($entry->lootTable && $entry->lootTable->monsters) {
                    foreach ($entry->lootTable->monsters as $m) {
                        if ($m->name) {
                            $key = $m->name . '_' . ($m->map->name ?? '');
                            $monsterDropsMap[$entry->ref_ulid][$key] = [
                                'monster' => $m->name,
                                'map' => $m->map->name ?? null,
                            ];
                        }
                    }
                }
            }
            foreach ($monsterDropsMap as $ulid => $mList) {
                $monsterDropsMap[$ulid] = array_values($mList);
            }
        }

        $preparedRecipes = [];
        foreach ($recipes as $recipe) {
            $preparedIngredients = [];
            $canCraft = $this->character->gold >= $recipe->gold_cost;

            foreach ($recipe->ingredients as $ing) {
                $mat = $materialTemplates->get($ing['template_id']);
                $owned = $inventoryMaterials->where('template_id', $ing['template_id'])->sum('stack_size');
                $req = $ing['quantity'];

                if ($owned < $req) $canCraft = false;

                $dropMonsters = $mat ? ($monsterDropsMap[$mat->id] ?? []) : [];

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
                'result_slot' => $recipe->resultItemTemplate->slot ?? null,
                'result_stats' => $recipe->resultItemTemplate->base_stats ?? [],
                'gold_cost' => $recipe->gold_cost,
                'ingredients' => $preparedIngredients,
                'can_craft' => $canCraft,
            ];
        }

        usort($preparedRecipes, fn ($a, $b) => ($a['result_level'] <=> $b['result_level']) ?: strcmp($a['result_name'], $b['result_name']));

        $equipped = [];
        foreach ($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.city.blacksmith', [
            'upgradableItems' => $upgradableItems,
            'upgradeCosts' => $upgradeCosts,
            'inventoryMaterials' => $inventoryMaterials,
            'recipes' => $preparedRecipes,
            'equipped' => $equipped,
        ]);
    }
}
