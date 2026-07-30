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
    public function openCaseModal(string $itemInstanceId): void
    {
        $this->resetState();
        $this->itemInstanceId = $itemInstanceId;
        $this->startOpening();
    }

    public function startOpening(): void
    {
        /** @var Character|null $character */
        $character = auth()->user()?->character;
        if (!$character || !$this->itemInstanceId) {
            $this->errorMessage = 'Nie znaleziono aktywnej postaci.';
            return;
        }

        $action = app(OpenLootChestAction::class);
        $result = $action->execute($character, $this->itemInstanceId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
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
