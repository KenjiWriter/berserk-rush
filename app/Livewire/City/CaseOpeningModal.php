<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Application\Items\Actions\OpenLootChestAction;
use App\Infrastructure\Persistence\Character;
use Livewire\Attributes\On;

class CaseOpeningModal extends Component
{
    public bool $isOpen = false;
    public bool $isSpinning = false;
    public bool $isFinished = false;

    public ?string $itemInstanceId = null;
    public ?array $chestData = null;
    public ?string $errorMessage = null;

    #[On('open-case-modal')]
    public function openCaseModal($itemInstanceId = null, int $count = 1, $payload = null, $characterId = null): void
    {
        $id = null;
        $charId = $characterId;

        if (is_string($itemInstanceId)) {
            $id = $itemInstanceId;
        } elseif (is_array($itemInstanceId)) {
            $id = $itemInstanceId['itemInstanceId'] ?? $itemInstanceId['id'] ?? $itemInstanceId[0] ?? null;
            if (isset($itemInstanceId['count'])) {
                $count = (int) $itemInstanceId['count'];
            }
            if (isset($itemInstanceId['characterId'])) {
                $charId = $itemInstanceId['characterId'];
            }
        } elseif (is_string($payload)) {
            $id = $payload;
        } elseif (is_array($payload)) {
            $id = $payload['itemInstanceId'] ?? $payload['id'] ?? $payload[0] ?? null;
            if (isset($payload['count'])) {
                $count = (int) $payload['count'];
            }
            if (isset($payload['characterId'])) {
                $charId = $payload['characterId'];
            }
        }

        if (!$id) {
            return;
        }

        $this->resetState();
        $this->itemInstanceId = $id;
        $this->startOpening($count, $charId);
    }

    public function startOpening(int $count = 1, ?string $characterId = null): void
    {
        $user = auth()->user();
        if (!$user || !$this->itemInstanceId) {
            $this->errorMessage = 'Nie znaleziono aktywnej postaci.';
            $this->isOpen = true;
            return;
        }

        $character = null;
        if ($characterId) {
            $character = Character::where('id', $characterId)->where('user_id', $user->id)->first();
        }
        if (!$character) {
            $activeCharId = session('active_character');
            if ($activeCharId) {
                $character = Character::where('id', $activeCharId)->where('user_id', $user->id)->first();
            }
        }
        if (!$character) {
            $character = $user->character ?? $user->characters()->first();
        }

        if (!$character) {
            $this->errorMessage = 'Nie znaleziono postaci gracza.';
            $this->isOpen = true;
            return;
        }

        $action = app(OpenLootChestAction::class);
        $result = $action->execute($character, $this->itemInstanceId, $count);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            $this->isOpen = true;
            $this->dispatch('inventory-updated');
            return;
        }

        $this->chestData = $result->getPayload();
        $this->isOpen = true;
        $this->isSpinning = true;
        $this->isFinished = false;
        $this->errorMessage = null;

        $this->dispatch('start-case-spin', payload: $this->chestData);
    }

    public function onSpinCompleted(): void
    {
        $this->isSpinning = false;
        $this->isFinished = true;
        $this->dispatch('inventory-updated');
    }

    public function closeModal(): void
    {
        $this->resetState();
        $this->dispatch('inventory-updated');
    }

    private function resetState(): void
    {
        $this->isOpen = false;
        $this->isSpinning = false;
        $this->isFinished = false;
        $this->chestData = null;
        $this->errorMessage = null;
    }

    public function render()
    {
        return view('livewire.city.case-opening-modal');
    }
}
