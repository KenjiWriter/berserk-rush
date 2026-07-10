<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\CharacterIncubator;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Pets\IncubatorService;
use Illuminate\Support\Facades\Auth;

class PetsComponent extends Component
{
    public Character $character;
    public ?string $errorMessage = null;
    public ?string $successMessage = null;

    public function mount(Character $character): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
    }

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

        $incubator = CharacterIncubator::where('character_id', $this->character->id)->first();

        $eggs = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->whereHas('template', fn($q) => $q->where('type', 'egg'))
            ->with('template')
            ->get();

        return view('livewire.city.pets', [
            'pets' => $pets,
            'incubator' => $incubator,
            'eggs' => $eggs,
        ]);
    }
}
