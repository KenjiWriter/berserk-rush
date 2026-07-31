<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Pets\IncubatorService;
use App\Application\Pets\PetService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;

#[Layout('components.layouts.app')]
class PetsComponent extends Component
{
    public Character $character;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    // --- ALCHEMICZNY SYNTEZATOR DUSZ (SOKOWIRÓWKA) ---
    public array $selectedSynthesizerPetIds = [];

    // --- SYSTEM KARMIENIA CHOWAŃCA ---
    public ?int $feedingPetId = null;
    public array $selectedFeedItemIds = [];

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

    // --- LOGIKA INKUBATORA & EKWIPOWANIA ---

    public function placeEgg(string $eggItemInstanceId): void
    {
        $this->resetMessages();
        $service = app(IncubatorService::class);
        $result = $service->placeEgg($this->character, $eggItemInstanceId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $this->successMessage = 'Jajko zostało umieszczone w inkubatorze!';
    }

    public function hatchEgg(): void
    {
        $this->resetMessages();
        $service = app(IncubatorService::class);
        $result = $service->hatchEgg($this->character);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $pet = $result->getPayload();
        $this->successMessage = "Wykluł się nowy pet: {$pet->name} ({$pet->rarity})!";
        $this->character->refresh();
    }

    public function toggleEquipPet(int $petId): void
    {
        $this->resetMessages();
        $service = app(IncubatorService::class);
        $result = $service->toggleEquipPet($this->character, $petId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $action = $payload['action'] ?? '';
        $pet = $payload['pet'] ?? null;

        if ($action === 'equipped') {
            $this->successMessage = "Pet {$pet->name} został założony!";
        } else {
            $this->successMessage = "Pet {$pet->name} został zdjęty.";
        }

        $this->character->clearStatsCache();
    }

    // --- LOGIKA ALCHEMICZNEGO SYNTEZATORA DUSZ (SOKOWIRÓWKA) ---

    public function toggleSynthesizerPet(int $petId): void
    {
        $this->resetMessages();

        if (in_array($petId, $this->selectedSynthesizerPetIds)) {
            $this->selectedSynthesizerPetIds = array_values(array_diff($this->selectedSynthesizerPetIds, [$petId]));
            return;
        }

        if (count($this->selectedSynthesizerPetIds) >= 3) {
            $this->errorMessage = 'Możesz wybrać maksymalnie 3 chowańce do transmutacji.';
            return;
        }

        $pet = Pet::where('id', $petId)->where('character_id', $this->character->id)->first();
        if (!$pet || $pet->is_equipped) {
            $this->errorMessage = 'Nie możesz użyć założonego lub nieistniejącego chowańca.';
            return;
        }

        if (!empty($this->selectedSynthesizerPetIds)) {
            $firstPet = Pet::find($this->selectedSynthesizerPetIds[0]);
            if ($firstPet && $firstPet->rarity !== $pet->rarity) {
                $this->errorMessage = 'Wszystkie 3 chowańce muszą posiadać ten sam stopień rzadkości (' . ucfirst($firstPet->rarity) . ')!';
                return;
            }
        }

        if ($pet->rarity === 'legendary') {
            $this->errorMessage = 'Chowańce rangi Legendarnej osiągnęły już najwyższą klasę.';
            return;
        }

        $this->selectedSynthesizerPetIds[] = $petId;
    }

    public function clearSynthesizer(): void
    {
        $this->selectedSynthesizerPetIds = [];
    }

    public function synthesizePets(): void
    {
        $this->resetMessages();
        $service = app(PetService::class);
        $result = $service->synthesizePets($this->character, $this->selectedSynthesizerPetIds);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $this->selectedSynthesizerPetIds = [];

        if (!($payload['success'] ?? false)) {
            $this->errorMessage = $payload['message'] ?? 'Transmutacja nie powiodła się!';
        } else {
            $this->successMessage = $payload['message'] ?? 'Transmutacja zakończona sukcesem!';
        }

        $this->character->refresh();
    }

    // --- LOGIKA KARMIENIA CHOWAŃCA ---

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
        $inventoryItems = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->pluck('id')
            ->toArray();

        if (count($this->selectedFeedItemIds) === count($inventoryItems)) {
            $this->selectedFeedItemIds = [];
        } else {
            $this->selectedFeedItemIds = $inventoryItems;
        }
    }

    public function feedPet(): void
    {
        $this->resetMessages();
        if (!$this->feedingPetId) {
            $this->errorMessage = 'Wybierz chowańca do nakarmienia.';
            return;
        }

        $service = app(PetService::class);
        $result = $service->feedPet($this->character, $this->feedingPetId, $this->selectedFeedItemIds);

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

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    private function resetMessages(): void
    {
        $this->errorMessage = null;
        $this->successMessage = null;
    }

    public function render()
    {
        $pets = Pet::where('character_id', $this->character->id)
            ->orderByDesc('is_equipped')
            ->orderByDesc('rarity')
            ->get();

        $incubator = CharacterIncubator::with('eggItemInstance.template')
            ->where('character_id', $this->character->id)
            ->first();

        $eggs = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->whereHas('template', fn($q) => $q->where('type', 'egg'))
            ->with('template')
            ->get();

        // Przedmioty w plecaku do nakarmienia peta
        $inventoryItems = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->with('template')
            ->get();

        $feedingPet = $this->feedingPetId ? Pet::find($this->feedingPetId) : null;

        return view('livewire.city.pets', [
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
            'inventoryItems' => $inventoryItems,
            'feedingPet' => $feedingPet,
        ]);
    }
}
