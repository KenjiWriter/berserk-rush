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

    public string $activeTab = 'forge'; // 'forge', 'crafting', 'dismantle'
    public ?string $selectedUpgradeItemId = null;
    public ?string $selectedDismantleItemId = null;

    // Filtr typu/slotu ekwipunku: all, weapon, head, chest, feet, ring, neck
    public string $itemFilter = 'all';

    public bool $showUpgradeModal = false;
    public string $upgradeModalTitle = '';
    public string $upgradeModalMessage = '';
    public string $upgradeModalType = 'success';

    // --- INFO / OPIS MECHANIK ---
    public bool $showInfoModal = false;

    // Gdy zaznaczone, następna próba ulepszenia (na poziomie +6 i wyżej) zużyje 1x
    // Zaczarowany Magiczny Metal i ochroni przedmiot przed regresją w razie porażki -
    // patrz UpgradeService::upgradeItem().
    public bool $useProtectionMetal = false;

    public function mount(Character $character)
    {
        Gate::authorize('view', $character);

        if ($character->hasActiveDungeonRun()) {
            session()->flash('error', 'Musisz najpierw ukończyć aktywną ekspedycję w lochu, aby móc wejść do tej lokacji.');
            return redirect()->route('city.adventure', ['character' => $character, 'tab' => 'dungeons']);
        }

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

    public function selectItemForDismantle($itemId)
    {
        $this->selectedDismantleItemId = $itemId;
        $this->activeTab = 'dismantle';
    }

    public function cancelUpgradeSelection()
    {
        $this->selectedUpgradeItemId = null;
    }

    public function cancelDismantleSelection()
    {
        $this->selectedDismantleItemId = null;
    }

    public function closeUpgradeModal()
    {
        $this->showUpgradeModal = false;
    }

    public function toggleInfoModal(): void
    {
        $this->showInfoModal = !$this->showInfoModal;
    }

    public function toggleProtectionMetal(): void
    {
        $this->useProtectionMetal = !$this->useProtectionMetal;
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    public function upgradeItem(string $itemInstanceId, \App\Application\Items\UpgradeService $upgrade)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        $result = $upgrade->upgradeItem($this->character, $item, $this->useProtectionMetal);

        $this->upgradeModalType = $result['success'] ? 'success' : 'error';
        $this->upgradeModalTitle = $result['success'] ? 'Sukces!' : 'Niepowodzenie';
        $this->upgradeModalMessage = $result['message'];
        $this->showUpgradeModal = true;
        $this->useProtectionMetal = false;

        $this->dispatch('play-audio', type: $result['success'] ? 'upgrade-success' : 'upgrade-fail');

        $this->character->refresh();

        $this->dispatch('stats-updated', gold: $this->character->gold);
    }

    public function dismantleItem(string $itemInstanceId, \App\Application\Items\DismantleService $dismantleService)
    {
        $item = \App\Infrastructure\Persistence\ItemInstance::find($itemInstanceId);
        if (!$item) {
            $this->dispatch('notify', type: 'error', message: 'Nie znaleziono przedmiotu.');
            return;
        }

        $result = $dismantleService->dismantleItem($this->character, $item);

        if ($result['success']) {
            $this->dispatch('notify', type: 'success', message: $result['message']);
            $this->dispatch('play-audio', type: 'upgrade-success');
            if ($this->selectedDismantleItemId === $itemInstanceId) {
                $this->selectedDismantleItemId = null;
            }
        } else {
            $this->dispatch('notify', type: 'error', message: $result['message']);
        }

        $this->character->refresh();
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
        $upgradableItems = ItemSorter::sort($upgradableItems, equippedFirst: true);

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

        $protectionMetalTplId = ItemTemplate::where('sub_type', 'upgrade_protection')->value('id');
        $protectionMetalCount = $protectionMetalTplId ? $this->character->items()
            ->whereIn('location', ['material_stash', 'inventory'])
            ->where('template_id', $protectionMetalTplId)
            ->sum('stack_size') : 0;

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
        $allMaterialsByName = ItemTemplate::where('type', 'material')->get()->keyBy('name');

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
                $mat = isset($ing['template_id']) ? $materialTemplates->get($ing['template_id']) : null;
                if (!$mat && !empty($ing['name'])) {
                    $mat = $allMaterialsByName->get($ing['name']);
                }
                $owned = $mat ? $inventoryMaterials->where('template_id', $mat->id)->sum('stack_size') : 0;
                $req = $ing['quantity'];

                if ($owned < $req) $canCraft = false;

                $dropMonsters = $mat ? ($monsterDropsMap[$mat->id] ?? []) : [];

                $preparedIngredients[] = [
                    'name' => $mat ? $mat->name : ($ing['name'] ?? 'Nieznany'),
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

        // Krzywa szans/kar ulepszenia jest identyczna dla każdego szablonu (patrz
        // UpgradeRuleSeeder) - pobrana raz jako lista distinct wierszy, żeby panel
        // "Opis mechanik" nigdy nie rozjechał się z realnymi regułami w DB. Liczone
        // tylko gdy modal jest otwarty, żeby nie obciążać każdego renderu komponentu.
        $upgradeSteps = $this->showInfoModal
            ? \App\Infrastructure\Persistence\UpgradeRule::select('from_level', 'to_level', 'success_chance', 'on_fail')
                ->distinct()
                ->orderBy('from_level')
                ->get()
            : collect();

        // Przedmioty do przetopienia (tylko z plecaka, niezałożone)
        $dismantlableItems = $this->character->inventoryItems()->whereHas('template', function ($q) {
            $q->whereIn('type', ['weapon', 'armor', 'accessory']);
        })->get()->filter(function ($item) {
            return $this->matchesItemFilter($item->template->type ?? null, $item->template->slot ?? null);
        });
        $dismantlableItems = ItemSorter::sort($dismantlableItems);

        $dismantleService = app(\App\Application\Items\DismantleService::class);
        $dismantleYields = [];
        foreach ($dismantlableItems as $dItem) {
            $dismantleYields[$dItem->id] = $dismantleService->calculateShardYield($dItem);
        }

        $runicTemplate = ItemTemplate::where('name', 'Runiczny Odłamek')->first();
        $runicShardCount = $runicTemplate
            ? $inventoryMaterials->where('template_id', $runicTemplate->id)->sum('stack_size')
            : 0;

        return view('livewire.city.blacksmith', [
            'upgradableItems' => $upgradableItems,
            'upgradeCosts' => $upgradeCosts,
            'dismantlableItems' => $dismantlableItems,
            'dismantleYields' => $dismantleYields,
            'runicShardCount' => $runicShardCount,
            'inventoryMaterials' => $inventoryMaterials,
            'protectionMetalCount' => $protectionMetalCount,
            'recipes' => $preparedRecipes,
            'equipped' => $equipped,
            'upgradeSteps' => $upgradeSteps,
        ]);
    }
}
