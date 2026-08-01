<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Domain\Pets\PetFusionRules;
use App\Domain\Pets\PetTier;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Pets\IncubatorService;
use App\Application\Pets\PetFeedingService;
use App\Application\Pets\PetFusionService;
use App\Application\Pets\PetEquipmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PetsComponent extends Component
{
    public Character $character;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    // --- FUZJA (2 pety tego samego tieru) ---
    public array $selectedFusionPetIds = [];

    // --- KARMIENIE ---
    public ?int $feedingPetId = null;
    public array $selectedFeedItemIds = [];

    // --- EKWIPUNEK PETA (obroża/charm) ---
    public ?int $gearPetId = null;
    public ?string $gearSlot = null;

    // --- INFO / PORADNIK ---
    public bool $showInfoModal = false;

    // --- SPRZEDAŻ NA RYNKU ---
    public ?int $sellingPetId = null;
    public $sellPrice = 1000;
    public $sellCurrency = 'gold';
    public $sellDuration = 24;

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

    // --- LOGIKA INKUBATORA ---

    public function placeEgg(string $eggItemInstanceId): void
    {
        $this->resetMessages();
        $result = app(IncubatorService::class)->placeEgg($this->character, $eggItemInstanceId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $this->successMessage = 'Jajko zostało umieszczone w inkubatorze!';
    }

    public function hatchEgg(): void
    {
        $this->resetMessages();
        $result = app(IncubatorService::class)->hatchEgg($this->character);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $pet = $result->getPayload();
        $this->successMessage = "Wykluł się nowy chowaniec: {$pet->name} ({$pet->tierName()})!";
        $this->character->refresh();
    }

    // --- AKTYWNY TOWARZYSZ ---

    public function toggleEquipPet(int $petId): void
    {
        $this->resetMessages();
        $result = app(PetEquipmentService::class)->toggleActiveCompanion($this->character, $petId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $pet = $payload['pet'] ?? null;

        $this->successMessage = ($payload['action'] ?? '') === 'equipped'
            ? "Chowaniec {$pet->name} został przywołany jako towarzysz!"
            : "Chowaniec {$pet->name} został odwołany.";

        $this->character->refresh();
    }

    // --- EKWIPUNEK PETA ---

    public function openGearPicker(int $petId, string $slot): void
    {
        $this->resetMessages();
        $this->gearPetId = $petId;
        $this->gearSlot = $slot;
    }

    public function closeGearPicker(): void
    {
        $this->gearPetId = null;
        $this->gearSlot = null;
    }

    public function equipGear(string $itemInstanceId): void
    {
        $this->resetMessages();
        if (!$this->gearPetId || !$this->gearSlot) {
            return;
        }

        $result = app(PetEquipmentService::class)->equipGear($this->character, $this->gearPetId, $this->gearSlot, $itemInstanceId);
        $this->closeGearPicker();

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $this->successMessage = 'Ekwipunek chowańca został założony!';
        $this->character->refresh();
    }

    public function unequipGear(int $petId, string $slot): void
    {
        $this->resetMessages();
        $result = app(PetEquipmentService::class)->unequipGear($this->character, $petId, $slot);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $this->successMessage = 'Ekwipunek chowańca został zdjęty.';
        $this->character->refresh();
    }

    // --- FUZJA ---

    public function toggleFusionPet(int $petId): void
    {
        $this->resetMessages();

        if (in_array($petId, $this->selectedFusionPetIds)) {
            $this->selectedFusionPetIds = array_values(array_diff($this->selectedFusionPetIds, [$petId]));
            return;
        }

        if (count($this->selectedFusionPetIds) >= 2) {
            $this->errorMessage = 'Możesz wybrać maksymalnie 2 chowańce do fuzji.';
            return;
        }

        $pet = Pet::where('id', $petId)->where('character_id', $this->character->id)->first();
        if (!$pet || $pet->is_equipped) {
            $this->errorMessage = 'Nie możesz użyć założonego lub nieistniejącego chowańca.';
            return;
        }

        if (!empty($this->selectedFusionPetIds)) {
            $firstPet = Pet::find($this->selectedFusionPetIds[0]);
            if ($firstPet && $firstPet->tier !== $pet->tier) {
                $this->errorMessage = 'Oba chowańce muszą posiadać ten sam tier (' . $firstPet->tierName() . ')!';
                return;
            }
        }

        if (!PetFusionRules::canFuse($pet->tier)) {
            $this->errorMessage = 'Chowańce najwyższego tieru (Legendarny) nie mogą być już dalej łączone.';
            return;
        }

        $this->selectedFusionPetIds[] = $petId;
    }

    public function clearFusion(): void
    {
        $this->selectedFusionPetIds = [];
    }

    public function fusePets(): void
    {
        $this->resetMessages();
        $result = app(PetFusionService::class)->fusePets($this->character, $this->selectedFusionPetIds);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $this->selectedFusionPetIds = [];

        if (!($payload['success'] ?? false)) {
            $this->errorMessage = $payload['message'] ?? 'Fuzja nie powiodła się!';
        } else {
            $this->successMessage = $payload['message'] ?? 'Fuzja zakończona sukcesem!';
        }

        $this->character->refresh();
    }

    // --- KARMIENIE ---

    public function openFeedModal(int $petId): void
    {
        $this->resetMessages();
        $this->feedingPetId = $petId;
        $this->selectedFeedItemIds = [];
    }

    public function closeFeedModal(): void
    {
        $this->feedingPetId = null;
        $this->selectedFeedItemIds = [];
    }

    public function toggleFeedItem(string $itemId): void
    {
        if (in_array($itemId, $this->selectedFeedItemIds)) {
            $this->selectedFeedItemIds = array_values(array_diff($this->selectedFeedItemIds, [$itemId]));
        } else {
            $this->selectedFeedItemIds[] = $itemId;
        }
    }

    public function selectAllFeedItems(): void
    {
        $pet = $this->feedingPetId ? Pet::find($this->feedingPetId) : null;
        $eligibleIds = $this->feedableItemsFor($pet)->pluck('id')->toArray();

        if (count($this->selectedFeedItemIds) === count($eligibleIds)) {
            $this->selectedFeedItemIds = [];
        } else {
            $this->selectedFeedItemIds = $eligibleIds;
        }
    }

    public function feedPet(): void
    {
        $this->resetMessages();
        if (!$this->feedingPetId) {
            $this->errorMessage = 'Wybierz chowańca do nakarmienia.';
            return;
        }

        $result = app(PetFeedingService::class)->feedPet($this->character, $this->feedingPetId, $this->selectedFeedItemIds);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $this->selectedFeedItemIds = [];

        $msg = "Przekazano pożywienie! Chowaniec otrzymał +{$payload['gainedExp']} EXP.";
        if ($payload['leveledUp'] ?? false) {
            $msg .= " Awans na Poziom {$payload['newLevel']}!";
        }
        $this->successMessage = $msg;
        $this->character->refresh();
    }

    public function toggleInfoModal(): void
    {
        $this->showInfoModal = !$this->showInfoModal;
    }

    // --- SPRZEDAŻ NA RYNKU ---

    public function openSellModal(int $petId): void
    {
        $this->resetMessages();
        $this->sellingPetId = $petId;
        $this->sellPrice = 1000;
        $this->sellCurrency = 'gold';
        $this->sellDuration = 24;
    }

    public function closeSellModal(): void
    {
        $this->sellingPetId = null;
    }

    public function sellPet(\App\Application\Economy\Actions\CreateMarketListingAction $action): void
    {
        $this->resetMessages();
        if (!$this->sellingPetId) {
            return;
        }

        $price = \App\Support\PriceParser::parse($this->sellPrice);
        if ($price < 1) {
            $this->errorMessage = 'Cena musi być większa od zera.';
            return;
        }

        $pet = Pet::find($this->sellingPetId);
        if (!$pet) {
            $this->errorMessage = 'Chowaniec nie istnieje.';
            return;
        }

        $result = $action->executeForPet($this->character, $pet, $price, (string) $this->sellCurrency, (int) $this->sellDuration);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $this->successMessage = 'Chowaniec wystawiony na Rynku!';
        $this->closeSellModal();
        $this->character->refresh();
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    private function resetMessages(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    /**
     * Przedmioty z plecaka mieszczące się w przedziale poziomowym akceptowanym
     * przez tier danego peta - używane w modalu karmienia.
     */
    private function feedableItemsFor(?Pet $pet)
    {
        $items = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->with('template')
            ->get();

        if (!$pet) {
            return $items;
        }

        return $items->filter(function ($item) use ($pet) {
            $level = (int) ($item->template->level_requirement ?? 1);
            return PetTier::isItemLevelAccepted($pet->tier, $level);
        })->values();
    }

    public function render()
    {
        $pets = Pet::where('character_id', $this->character->id)
            ->orderByDesc('is_equipped')
            ->orderByDesc('tier')
            ->get();

        $incubator = CharacterIncubator::with('eggItemInstance.template')
            ->where('character_id', $this->character->id)
            ->first();

        $eggs = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->whereHas('template', fn($q) => $q->where('type', 'egg'))
            ->with('template')
            ->get();

        $feedingPet = $this->feedingPetId ? Pet::find($this->feedingPetId) : null;
        $feedableItems = $this->feedableItemsFor($feedingPet);

        $gearPet = $this->gearPetId ? Pet::find($this->gearPetId) : null;
        $gearPickerItems = collect();
        if ($gearPet && $this->gearSlot) {
            $expectedType = config("pets.gear_types.{$this->gearSlot}");
            $gearPickerItems = ItemInstance::where('owner_character_id', $this->character->id)
                ->where('location', 'inventory')
                ->whereHas('template', fn($q) => $q->where('type', $expectedType))
                ->with('template')
                ->get();
        }

        return view('livewire.city.pets', [
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
            'feedingPet' => $feedingPet,
            'inventoryItems' => $feedableItems,
            'gearPet' => $gearPet,
            'gearPickerItems' => $gearPickerItems,
            'tiers' => PetTier::all(),
        ]);
    }
}
