<?php

namespace App\Livewire\Adventure;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Map;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Encounter;
use App\Application\Characters\LevelUpService;

class MapStub extends Component
{
    public Character $character;
    public Map $map;
    public string $background;

    // Combat state
    public ?string $currentEncounterId = null;
    public array $player = [];
    public array $enemy = [];
    public array $allTurns = [];
    public array $visibleTurns = [];
    public string $result = '';
    public bool $playerFirst = true;

    // Playback controls
    public bool $isPlaying = false;
    public int $playbackSpeed = 1;
    public bool $autoChain = true;
    public int $currentTurnIndex = 0;

    // Rewards
    public int $goldGained = 0;
    public int $xpGained = 0;
    public array $levelUps = [];
    public bool $battleCompleted = false;

    public function mount(Character $character, Map $map): void
    {
        // Authorization check
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        // Level range warning
        if (!$map->isAccessibleBy($character)) {
            session()->flash('warning', "Ostrzeżenie: Twój poziom ({$character->level}) może nie być odpowiedni dla tej mapy (poziom {$map->level_range}).");
        }

        $this->character = $character;
        $this->map = $map;
        $this->background = $this->backgroundFor($map);
    }

    public function startBattle(): void
    {
        $this->resetBattleState();

        // Start new encounter
        $encounterService = app(EncounterService::class);
        $startResult = $encounterService->start($this->character, $this->map);

        if ($startResult->isError()) {
            $this->addError('battle', $startResult->getErrorMessage());
            return;
        }

        $encounter = $startResult->getPayload();
        $this->currentEncounterId = $encounter->id;

        // Simulate combat
        $simulateResult = $encounterService->simulate($encounter);

        if ($simulateResult->isError()) {
            $this->addError('battle', $simulateResult->getErrorMessage());
            return;
        }

        $combatResult = $simulateResult->getPayload();
        $this->setupBattleData($encounter, $combatResult);

        // Start playback
        $this->isPlaying = true;
        $this->dispatch('start-playback', speed: $this->playbackSpeed);
    }

    public function pause(): void
    {
        $this->isPlaying = false;
        $this->dispatch('stop-playback');
    }

    public function resume(): void
    {
        if ($this->currentTurnIndex < count($this->allTurns)) {
            $this->isPlaying = true;
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        }
    }

    public function togglePlayback(): void
    {
        if ($this->isPlaying) {
            $this->pause();
        } else {
            $this->resume();
        }
    }

    public function setPlaybackSpeed(int $speed): void
    {
        $this->playbackSpeed = $speed;

        if ($this->isPlaying) {
            $this->dispatch('update-playback-speed', speed: $speed);
        }
    }

    public function toggleAutoChain(): void
    {
        $this->autoChain = !$this->autoChain;
    }

    public function stopAuto(): void
    {
        $this->autoChain = false;
        $this->pause();
    }

    public function nextTurn(): void
    {
        if ($this->currentTurnIndex < count($this->allTurns)) {
            $this->visibleTurns[] = $this->allTurns[$this->currentTurnIndex];
            $this->currentTurnIndex++;

            if ($this->currentTurnIndex >= count($this->allTurns)) {
                $this->completeBattle();
            }
        }
    }

    public function resetEncounter(): void
    {
        $this->startBattle();
    }

    public function backToAdventure(): void
    {
        $this->redirect(route('city.adventure', $this->character), navigate: true);
    }

    public function backToHub(): void
    {
        $this->redirect(route('city.hub', $this->character), navigate: true);
    }

    private function resetBattleState(): void
    {
        $this->visibleTurns = [];
        $this->allTurns = [];
        $this->currentTurnIndex = 0;
        $this->isPlaying = false;
        $this->battleCompleted = false;
        $this->goldGained = 0;
        $this->xpGained = 0;
        $this->levelUps = [];
        $this->result = '';
    }

    private function setupBattleData(Encounter $encounter, array $combatResult): void
    {
        $character = $encounter->character;
        $monster = $encounter->monster;

        // Debug to verify attributes exist
        logger('Character attributes:', ['attributes' => $character->attributes]);

        // Force load attributes if they weren't loaded with the relation
        if (!isset($character->attributes)) {
            $character = $character->fresh();
        }

        // Extract character attributes directly from the model
        $character_attributes = json_decode($character->attributes, true);
        $playerAttributes = [
            'str' => $character_attributes['str'] ?? 0,
            'int' => $character_attributes['int'] ?? 0,
            'vit' => $character_attributes['vit'] ?? 0,
            'agi' => $character_attributes['agi'] ?? 0
        ];

        // Calculate HP based on attributes
        $playerMaxHp = $this->calculateMaxHp($character);

        $monsterMaxHp = $combatResult['enemy']['maxHp'] ?? ($monster->stats['hp'] ?? $monster->level * 20);
        $monsterStats = $combatResult['enemy']['stats'] ?? $monster->stats ?? [];

        // Set player data with explicitly mapped attributes
        $this->player = [
            'name' => $character->name,
            'level' => $character->level,
            'avatar' => $character->avatar_url ?? asset('img/avatars/default.png'),
            'maxHp' => $playerMaxHp,
            'hp' => $playerMaxHp,
            'stats' => $playerAttributes // Use our explicitly mapped attributes
        ];

        // Fix for enemy stats
        $this->enemy = [
            'name' => $monster->name,
            'level' => $monster->level,
            'maxHp' => $monsterMaxHp,
            'hp' => $monsterMaxHp,
            'stats' => $monsterStats
        ];

        $this->allTurns = $combatResult['turns'];
        $this->result = $combatResult['result'];
        $this->playerFirst = $encounter->player_first;
        $this->goldGained = $combatResult['rewards']['gold'] ?? 0;
        $this->xpGained = $combatResult['rewards']['xp'] ?? 0;
    }

    /**
     * Get the XP required for the next level
     */
    public function getXpToNextLevel(): int
    {
        return $this->getXpRequiredForLevel($this->character->level + 1);
    }

    /**
     * Calculate the percentage of XP progress towards the next level
     */
    public function getXpPercentage(): float
    {
        $xpToNext = $this->getXpToNextLevel();
        $currentXp = $this->character->xp;
        return min(100, ($currentXp / max(1, $xpToNext)) * 100);
    }

    private function calculateMaxHp(Character $character): int
    {
        $vitality = $character->attributes['vit'] ?? 1;
        return 100 + ($vitality * 10) + ($character->level * 5);
    }

    private function completeBattle(): void
    {
        $this->isPlaying = false;
        $this->battleCompleted = true;
        $this->dispatch('stop-playback');

        if ($this->result === 'win') {
            $this->applyRewards();
        }

        // Auto-chain next battle
        if ($this->autoChain && $this->result === 'win') {
            $this->dispatch('auto-chain-next-battle');
        } else {
            $this->dispatch('encounter-finished', result: $this->result);
        }
    }

    private function applyRewards(): void
    {
        $encounter = Encounter::find($this->currentEncounterId);

        if (!$encounter || $encounter->state !== 'win') {
            return;
        }

        // Check for level up BEFORE applying rewards
        $oldLevel = $this->character->level;
        $this->checkLevelUp();

        // Apply gold and XP rewards to character
        $this->character->update([
            'gold' => $this->character->gold + $this->goldGained,
            'xp' => $this->character->xp + $this->xpGained,
        ]);

        // Mark encounter rewards as applied
        $encounter->markRewardsApplied();
    }

    private function checkLevelUp(): void
    {
        // Track original level for UI messages
        $originalLevel = $this->character->level;

        // Apply XP gain first
        $this->character->xp += $this->xpGained;
        $this->character->save();

        // Use LevelUpService to handle level progression and points
        $levelUpService = app(\App\Application\Characters\LevelUpService::class);
        $result = $levelUpService->checkAndApply($this->character);

        if ($result->isOk()) {
            $levelUpResult = $result->getPayload();

            // Update UI display
            if ($levelUpResult->hadLevelUp) {
                $this->levelUps = array_map(function ($levelUp) {
                    return [
                        'from' => $levelUp['from'],
                        'to' => $levelUp['to'],
                        'attribute_points' => 3,
                    ];
                }, $levelUpResult->levelUps);
            }

            // Refresh character to get updated values
            $this->character = $this->character->fresh();
        }
    }

    private function getXpRequiredForLevel(int $level): int
    {
        // Simple formula: level * 100 XP required for next level
        return $level * 100;
    }

    // Helper methods for UI state
    public function getCurrentPlayerHp(): int
    {
        if (empty($this->visibleTurns)) {
            return $this->player['hp'] ?? 0;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['playerHp'] ?? 0;
    }

    public function getCurrentEnemyHp(): int
    {
        if (empty($this->visibleTurns)) {
            return $this->enemy['hp'] ?? 0;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['enemyHp'] ?? 0;
    }

    public function getPlayerHpPercent(): float
    {
        if (empty($this->player)) return 0;
        return ($this->getCurrentPlayerHp() / max(1, $this->player['maxHp'])) * 100;
    }

    public function getEnemyHpPercent(): float
    {
        if (empty($this->enemy)) return 0;
        return ($this->getCurrentEnemyHp() / max(1, $this->enemy['maxHp'])) * 100;
    }

    public function isPlayerTurn(): bool
    {
        if (empty($this->visibleTurns)) {
            return $this->playerFirst;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['actor'] === 'player';
    }

    public function isEnemyTurn(): bool
    {
        if (empty($this->visibleTurns)) {
            return !$this->playerFirst;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['actor'] === 'enemy';
    }

    private function backgroundFor(Map $map): string
    {
        return match ($map->name) {
            'Mroczny Las' => asset('img/maps/dark-forest.png'),
            'Stare Ruiny' => asset('img/maps/old-ruins.png'),
            'Jaskinia Trolli' => asset('img/maps/troll-cave.png'),
            'Pustkowia Orków' => asset('img/maps/orc-wasteland.png'),
            'Bagna Grozy' => asset('img/maps/horror-swamps.png'),
            'Góry Cienia' => asset('img/maps/shadow-mountains.png'),
            'Wieża Magów' => asset('img/maps/shadow-mountains.png'),
            'Skażone Miasto' => asset('img/maps/corrupted-city.png'),
            default => asset('img/maps/default.jpg'),
        };
    }

    public function render()
    {
        return view('livewire.adventure.map-stub');
    }
}
