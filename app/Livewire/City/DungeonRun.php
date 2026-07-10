<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Dungeon\DungeonService;
use Illuminate\Support\Facades\Auth;

class DungeonRun extends Component
{
    public Character $character;
    public Dungeon $dungeon;
    public ?int $runId = null;
    public int $currentStage = 1;
    public int $totalStages = 0;
    public ?array $battleResult = null;
    public array $turns = [];
    public bool $showBattle = false;
    public ?string $errorMessage = null;

    public function mount(Character $character, Dungeon $dungeon): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->character = $character;
        $this->dungeon = $dungeon;
        $this->totalStages = $dungeon->stages()->count();

        // Check for existing active run in this dungeon
        $activeRun = CharacterDungeonRun::where('character_id', $character->id)
            ->where('dungeon_id', $dungeon->id)
            ->where('is_completed', false)
            ->where('is_failed', false)
            ->first();

        if ($activeRun) {
            $this->runId = $activeRun->id;
            $this->currentStage = $activeRun->current_stage;
        }
    }

    public function startRun(): void
    {
        $this->errorMessage = null;
        $service = app(DungeonService::class);
        $result = $service->startRun($this->character, $this->dungeon);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $run = $result->getPayload();
        $this->runId = $run->id;
        $this->currentStage = $run->current_stage;
        $this->character->refresh();
    }

    public function fight(): void
    {
        $this->errorMessage = null;
        $run = $this->getActiveRun();
        if (!$run) {
            $this->errorMessage = 'Brak aktywnej ekspedycji.';
            return;
        }

        $service = app(DungeonService::class);
        $result = $service->fightCurrentStage($run);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        $payload = $result->getPayload();
        $this->turns = $payload['turns'] ?? [];
        $this->battleResult = $payload;
        $this->showBattle = true;

        // Refresh run state
        $run->refresh();
        $this->currentStage = $run->current_stage;
        $this->character->refresh();
    }

    public function usePotion(string $itemInstanceId): void
    {
        $this->errorMessage = null;
        $run = $this->getActiveRun();
        if (!$run) {
            $this->errorMessage = 'Brak aktywnej ekspedycji.';
            return;
        }

        $service = app(DungeonService::class);
        $result = $service->usePotion($run, $itemInstanceId);

        if ($result->isError()) {
            $this->errorMessage = $result->getErrorMessage();
            return;
        }

        // Refresh run to get updated HP
        $run->refresh();
        $this->character->refresh();
    }

    public function dismissBattle(): void
    {
        $this->showBattle = false;
        $this->turns = [];

        // If run ended, keep the result for display
        if ($this->battleResult && in_array($this->battleResult['result'] ?? '', ['dungeon_complete', 'loss'])) {
            // Keep battleResult for final screen
            return;
        }

        $this->battleResult = null;
    }

    public function backToDungeonList(): void
    {
        $this->redirect(route('city.dungeons', $this->character), navigate: true);
    }

    private function getActiveRun(): ?CharacterDungeonRun
    {
        if (!$this->runId) {
            return null;
        }

        return CharacterDungeonRun::where('id', $this->runId)
            ->where('character_id', $this->character->id)
            ->first();
    }

    public function render()
    {
        $run = $this->getActiveRun();
        $currentStageModel = $run?->getCurrentStageModel();
        $monster = $currentStageModel?->monster;

        $maxHp = $this->character->getMaxHp();
        $currentHp = $run?->current_hp ?? $maxHp;

        // Get consumable potions from inventory
        $potions = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->whereHas('template', fn($q) => $q->where('type', 'consumable'))
            ->with('template')
            ->get();

        return view('livewire.city.dungeon-run', [
            'run' => $run,
            'monster' => $monster,
            'currentHp' => $currentHp,
            'maxHp' => $maxHp,
            'potions' => $potions,
        ]);
    }
}
