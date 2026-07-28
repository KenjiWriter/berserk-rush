<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Gate;

class Blacksmith extends Component
{
    public Character $character;

    public ?string $selectedUpgradeItemId = null;

    public bool $showUpgradeModal = false;
    public string $upgradeModalTitle = '';
    public string $upgradeModalMessage = '';
    public string $upgradeModalType = 'success';

    public function mount(Character $character): void
    {
        Gate::authorize('view', $character);
        $this->character = $character;
    }

    public function selectItemForUpgrade($itemId)
    {
        $this->selectedUpgradeItemId = $itemId;
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
    }

    public function render(\App\Application\Items\UpgradeService $upgradeService)
    {
        // Ulepszanie: broń oraz zbroja w jednym, ogólnym widoku Kowala
        $upgradableItems = $this->character->inventoryItems()->whereHas('template', function ($q) {
            $q->whereIn('type', ['weapon', 'armor']);
        })->take(64)->get()->merge(
            $this->character->equippedItems()->whereHas('template', function ($q) {
                $q->whereIn('type', ['weapon', 'armor']);
            })->get()
        );

        $upgradeCosts = [];
        foreach ($upgradableItems as $item) {
            $upgradeCosts[$item->id] = $upgradeService->getUpgradeCost($item);
        }

        $inventoryMaterials = $this->character->items()
            ->whereIn('location', ['material_stash', 'inventory'])
            ->whereHas('template', function ($q) {
                $q->where('type', 'material');
            })->get();

        $equipped = [];
        foreach ($this->character->equippedItems()->with('template')->get() as $eq) {
            $equipped[$eq->template->slot] = $eq;
        }

        return view('livewire.city.blacksmith', [
            'upgradableItems' => $upgradableItems,
            'upgradeCosts' => $upgradeCosts,
            'inventoryMaterials' => $inventoryMaterials,
            'equipped' => $equipped,
        ]);
    }
}
