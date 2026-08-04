<?php

namespace App\Livewire\City;

use Livewire\Component;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Dungeon\DungeonService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

#[Layout('components.layouts.app')]
class DungeonRun extends Component
{
    public Character $character;
    public Dungeon $dungeon;
    public ?int $runId = null;
    public int $currentStage = 1;
    public int $totalStages = 0;
    public ?array $battleResult = null;
    public array $turns = [];
    public array $visibleTurns = [];
    public bool $showBattle = false;
    public ?string $errorMessage = null;

    // Playback state
    public bool $isCalculating = false;
    public bool $isPlaying = false;
    public int $currentTurnIndex = 0;
    public int $playbackSpeed = 1;
    public int $animatedPlayerHp = 0;
    public int $animatedEnemyHp = 0;
    public int $multiplier = 1;

    public function mount(Character $character, Dungeon $dungeon): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $requestedMultiplier = (int) request()->query('multiplier', 1);
        $this->multiplier = in_array($requestedMultiplier, [1, 3, 5]) ? $requestedMultiplier : 1;

        $this->playbackSpeed = session('combat_playback_speed', 1);
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
            $this->multiplier = $activeRun->key_multiplier ?? 1;
            
            if ($activeRun->combat_state === 'calculating') {
                $this->isCalculating = true;
                $this->showBattle = true;
            } elseif ($activeRun->combat_state === 'completed' && $activeRun->combat_data) {
                // If it finished while we were away, we can just load the result immediately
                $this->battleResult = $activeRun->combat_data;
                $this->turns = $this->battleResult['turns'] ?? [];
                $this->visibleTurns = $this->turns; // Show all turns directly if revisiting
                $this->showBattle = true;
                $this->isCalculating = false;
                
                $activeRun->combat_state = 'idle';
                $activeRun->save();
            }
        }
    }

    public function startRun(): void
    {
        $this->errorMessage = null;
        $service = app(DungeonService::class);
        $result = $service->startRun($this->character, $this->dungeon, $this->multiplier);

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

        $this->isCalculating = true;
        $this->showBattle = true;
        $this->visibleTurns = [];
        $this->currentTurnIndex = 0;
        $this->turns = [];
        $this->battleResult = null;
    }

    public function checkCombatStatus(): void
    {
        if (!$this->isCalculating || !$this->runId) {
            return;
        }

        $run = $this->getActiveRun();
        
        if (!$run) {
            $this->errorMessage = 'Run nie istnieje.';
            $this->isCalculating = false;
            return;
        }

        if ($run->combat_state === 'error') {
            $this->errorMessage = 'Wystąpił błąd podczas obliczania walki.';
            $this->isCalculating = false;
            $run->combat_state = 'idle';
            $run->save();
            return;
        }

        if ($run->combat_state === 'completed') {
            $this->isCalculating = false;
            
            $this->battleResult = $run->combat_data;
            $this->turns = $this->battleResult['turns'] ?? [];
            
            $this->animatedPlayerHp = $this->battleResult['start_player_hp'] ?? $run->current_hp;
            $this->animatedEnemyHp = $this->battleResult['start_monster_hp'] ?? ($this->battleResult['monster_max_hp'] ?? 0);
            
            // Start playback
            $this->isPlaying = true;
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
            
            // Reset state
            $run->combat_state = 'idle';
            $run->save();
            
            // Refresh run state for UI
            $run->refresh();
            $this->character->refresh();
        }
    }

    #[On('resume-playback')]
    public function resume(): void
    {
        if ($this->currentTurnIndex < count($this->turns)) {
            $turn = $this->turns[$this->currentTurnIndex];
            $this->visibleTurns[] = $turn;
            
            // Update animated HP for UI bindings
            if ($turn['actor'] === 'player') {
                $this->animatedEnemyHp = max(0, $this->animatedEnemyHp - $turn['value']);
            } else {
                $this->animatedPlayerHp = max(0, $this->animatedPlayerHp - $turn['value']);
            }
            
            $this->currentTurnIndex++;
            
            // Dispatch event for UI shake animations
            $this->dispatch('turn-played', actor: $turn['actor'], type: $turn['type'], value: $turn['value'] ?? 0);
        } else {
            $this->isPlaying = false;
            $this->dispatch('stop-playback');
        }
    }

    public function skipBattle(): void
    {
        if ($this->isPlaying) {
            $this->isPlaying = false;
            $this->dispatch('stop-playback');
            
            // Fast-forward UI state
            $this->visibleTurns = $this->turns;
            $this->currentTurnIndex = count($this->turns);
            $this->animatedPlayerHp = $this->battleResult['player_hp'] ?? 0;
            
            $lastEnemyHp = 0;
            if (count($this->turns) > 0) {
                $lastEnemyHp = end($this->turns)['enemyHp'] ?? 0;
            }
            $this->animatedEnemyHp = $lastEnemyHp;
            
            // Auto scroll to bottom via JS event
            $this->dispatch('scroll-to-bottom');
        }
    }

    public function usePotion(string $itemInstanceId): void
    {
        $this->errorMessage = null;
        if ($this->isCalculating || $this->isPlaying) {
            $this->errorMessage = 'Nie możesz używać mikstur w trakcie walki.';
            return;
        }

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
        $this->visibleTurns = [];
        $this->isPlaying = false;
        $this->isCalculating = false;

        // If run ended, keep the result for display
        if ($this->battleResult && in_array($this->battleResult['result'] ?? '', ['dungeon_complete', 'lose'])) {
            // Keep battleResult for final screen
            return;
        }

        $this->battleResult = null;
        
        $run = $this->getActiveRun();
        if ($run) {
            $this->currentStage = $run->current_stage;
        }
    }

    public function canUseSpeed5(): bool
    {
        if (!$this->character) {
            return false;
        }

        $hasVip = $this->character->user?->hasPremium() ?? false;
        return $this->character->level >= 30 || $hasVip;
    }

    public function setPlaybackSpeed(int $speed): void
    {
        if ($speed === 5 && !$this->canUseSpeed5()) {
            $speed = 2;
        }

        if (!in_array($speed, [1, 2, 5], true)) {
            $speed = 1;
        }

        $this->playbackSpeed = $speed;
        session(['combat_playback_speed' => $speed]);
    }

    public function backToDungeonList(): void
    {
        $this->redirect(route('city.adventure', ['character' => $this->character, 'tab' => 'dungeons']), navigate: true);
    }

    public function getCurrentPlayerMana(): int
    {
        $maxMana = $this->character->getMaxMana();

        // Tylko tury gracza niosą 'playerMana' - szukamy od końca ostatniej znanej wartości
        for ($i = count($this->visibleTurns) - 1; $i >= 0; $i--) {
            if (isset($this->visibleTurns[$i]['playerMana'])) {
                return $this->visibleTurns[$i]['playerMana'];
            }
        }

        $run = $this->getActiveRun();
        if ($run && $run->current_mana !== null) {
            return min($maxMana, max(0, (int) $run->current_mana));
        }

        return $maxMana;
    }

    public function getPlayerManaPercent(): float
    {
        $maxMana = $this->character->getMaxMana();
        return min(100, max(0, ($this->getCurrentPlayerMana() / max(1, $maxMana)) * 100));
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
        $currentStageModel = $this->dungeon->stages()->where('stage_order', $this->currentStage)->first();
        $monster = $currentStageModel?->monster;

        $maxHp = $this->character->getMaxHp();
        $currentHp = $run?->current_hp ?? $maxHp;

        // Tylko prawdziwe mikstury lecznicze (mają zdefiniowany heal_amount) - wyklucza
        // skrzynie, zwoje użytkowe oraz mikstury buffowe (siła/obrona/many), których
        // usePotion() w DungeonService i tak nie umie poprawnie obsłużyć w walce.
        $potions = ItemInstance::where('owner_character_id', $this->character->id)
            ->where('location', 'inventory')
            ->whereHas('template', function ($q) {
                $q->where('type', 'consumable')
                  ->where(function ($sub) {
                      $sub->whereNotNull('base_stats->heal_amount')
                          ->orWhereNotNull('base_stats->heal_pct');
                  });
            })
            ->with('template')
            ->get();

        return view('livewire.city.dungeon-run', [
            'run' => $run,
            'currentStageModel' => $currentStageModel,
            'monster' => $monster,
            'currentHp' => $currentHp,
            'maxHp' => $maxHp,
            'potions' => $potions,
        ]);
    }
}
