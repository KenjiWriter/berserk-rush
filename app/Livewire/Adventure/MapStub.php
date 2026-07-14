<?php

namespace App\Livewire\Adventure;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Map;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Encounter;
use App\Application\Characters\LevelUpService;
use Livewire\Attributes\On;

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

    // Session Tracking
    public int $sessionMonstersDefeated = 0;
    public int $sessionStartTime = 0;

    // Playback controls
    public bool $isPlaying = false;
    public int $playbackSpeed = 1;
    public bool $autoChain = true;
    public int $currentTurnIndex = 0;
    public bool $isCalculating = false;

    // Rewards
    public int $goldGained = 0;
    public int $xpGained = 0;
    public array $goldData = [];
    public array $xpData = [];
    public array $drops = [];
    public array $levelUps = [];
    public bool $battleCompleted = false;
    public int $damageDealt = 0;
    public bool $isWorldBoss = false;
    public array $pendingNotifications = [];

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        // Force refresh
        $this->character->refresh();
    }

    public function mount(Character $character, Map $map): void
    {
        $this->sessionStartTime = time();

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

        if (Auth::user()->game_stage <= 12) {
            $this->autoChain = false;
        }

        if (request()->has('world_boss')) {
            $this->isWorldBoss = true;
            $worldBossId = (int)request()->query('world_boss');
            
            // Sprawdź czy boss w ogóle istnieje na tej mapie jako aktywny boss
            $worldBossInstance = \App\Infrastructure\Persistence\WorldBossInstance::where('map_id', $this->map->id)
                ->where('monster_id', $worldBossId)
                ->where('is_defeated', false)
                ->first();

            if ($worldBossInstance) {
                $hasParticipated = \App\Infrastructure\Persistence\WorldBossDamageLog::where('world_boss_instance_id', $worldBossInstance->id)
                    ->where('character_id', $this->character->id)
                    ->exists();

                if ($hasParticipated) {
                    $this->isWorldBoss = false;
                    session()->flash('warning', 'Już brałeś udział w walce z tym World Bossem!');
                } else {
                    $this->startBattle($worldBossId);
                }
            } else {
                $this->isWorldBoss = false;
                session()->flash('warning', 'Ten World Boss nie jest obecnie aktywny na tej mapie.');
                $this->startBattle();
            }
        }
    }

    public function startBattle(?int $monsterId = null): void
    {
        $this->resetBattleState();

        // Start new encounter
        $encounterService = app(EncounterService::class);
        
        // Zabezpieczenie: tylko przy aktywnym statusie World Boss pozwalamy na wymuszone ID potwora
        if (!$this->isWorldBoss) {
            $monsterId = null;
        }
        
        $forcedMonster = $monsterId ? \App\Infrastructure\Persistence\Monster::find($monsterId) : null;
        $startResult = $encounterService->start($this->character, $this->map, $forcedMonster);

        if ($startResult->isError()) {
            $this->addError('battle', $startResult->getErrorMessage());
            $this->isCalculating = false;
            $this->enemy = [];
            return;
        }

        $encounter = $startResult->getPayload();
        $this->currentEncounterId = $encounter->id;
        $this->isCalculating = true;

        // Set enemy data early so the UI can show who we are fighting
        $monster = clone $encounter->monster;
        $this->enemy = [
            'name' => $monster->name,
            'level' => $monster->level,
            'maxHp' => $monster->stats['hp'] ?? $monster->level * 20,
            'hp' => $monster->stats['hp'] ?? $monster->level * 20,
            'stats' => $monster->stats,
            'avatar' => $monster->avatar,
        ];

        // Dispatch combat simulation to worker
        dispatch(new \App\Jobs\SimulateCombatJob($encounter->id));
    }

    public function checkCombatStatus(): void
    {
        if (!$this->isCalculating || !$this->currentEncounterId) {
            return;
        }

        $encounter = Encounter::find($this->currentEncounterId);
        
        if (!$encounter) {
            $this->addError('battle', 'Encounter nie istnieje.');
            $this->isCalculating = false;
            return;
        }

        if ($encounter->state === 'error') {
            $this->addError('battle', 'Wystąpił błąd podczas obliczania walki.');
            $this->isCalculating = false;
            return;
        }

        if (in_array($encounter->state, ['win', 'lose', 'finished'])) {
            $this->isCalculating = false;
            
            // Reconstruct combatResult from DB
            $combatResult = $this->reconstructCombatResult($encounter);
            
            $this->setupBattleData($encounter, $combatResult);
            
            // Start playback
            $this->isPlaying = true;
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        }
    }

    public function cancelBattle(): void
    {
        if ($this->currentEncounterId && $this->isCalculating) {
            $encounter = Encounter::find($this->currentEncounterId);
            if ($encounter && $encounter->state === 'ongoing') {
                $encounter->update(['state' => 'cancelled']);
            }
        }
        
        $this->isCalculating = false;
        $this->currentEncounterId = null;
        $this->enemy = [];
        
        $this->resetBattleState();
    }

    private function reconstructCombatResult(Encounter $encounter): array
    {
        $character = $encounter->character;
        $monster = $encounter->monster;
        $combatData = $encounter->combat_data ?? [];
        
        return [
            'enemy' => [
                'maxHp' => $combatData['monster_max_hp'] ?? ($monster->stats['hp'] ?? $monster->level * 20),
                'stats' => $monster->stats ?? []
            ],
            'turns' => $encounter->turns ?? [],
            'result' => $encounter->state,
            'rewards' => [
                'gold' => $encounter->gold_reward,
                'xp' => $encounter->xp_reward,
                'gold_data' => $combatData['rewards']['gold_data'] ?? [],
                'xp_data' => $combatData['rewards']['xp_data'] ?? [],
                'damage_dealt' => $combatData['damage_dealt'] ?? 0,
            ],
            'notifications' => $combatData['notifications'] ?? []
        ];
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
        if (Auth::user()->game_stage <= 12) {
            return;
        }
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
            $turn = $this->allTurns[$this->currentTurnIndex];
            $this->visibleTurns[] = $turn;
            $this->currentTurnIndex++;
            
            // Dispatch event for UI animations
            $this->dispatch('turn-played', actor: $turn['actor'], type: $turn['type'], value: $turn['value'] ?? 0);

            $audioType = $turn['type'] === 'miss' ? 'dodge' : (!empty($turn['crit']) ? 'crit' : 'hit');
            $this->dispatch('play-audio', type: $audioType);

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
        $this->goldData = [];
        $this->xpData = [];
        $this->levelUps = [];
        $this->result = '';
        $this->damageDealt = 0;
    }

    private function setupBattleData(Encounter $encounter, array $combatResult): void
    {
        $character = $encounter->character;
        $monster = $encounter->monster;

        // Debug to verify attributes exist
        logger('Character attributes:', ['attributes' => $character->getAttribute('attributes')]);

        // Force load attributes if they weren't loaded with the relation
        if (!isset($character->attributes)) {
            $character = $character->fresh();
        }

        // Get total calculated attributes
        $playerAttributes = $character->getTotalAttributes();

        // Calculate HP based on attributes
        $playerMaxHp = $character->getMaxHp();

        $monsterMaxHp = $combatResult['enemy']['maxHp'] ?? ($monster->stats['hp'] ?? $monster->level * 20);
        $monsterStats = $combatResult['enemy']['stats'] ?? $monster->stats ?? [];

        // Set player data with explicitly mapped attributes
        $this->player = [
            'name' => $character->name,
            'level' => $character->level,
            'avatar' => $character->avatar ? asset("img/avatars/{$character->avatar}.png") : asset('img/avatars/default.png'),
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
            'stats' => $monsterStats,
            'avatar' => $monster->avatar,
        ];

        $this->allTurns = $combatResult['turns'];
        $this->result = $combatResult['result'];
        $this->playerFirst = $encounter->player_first;
        $this->drops = $encounter->result['drops'] ?? [];
        $this->goldGained = $combatResult['rewards']['gold'] ?? 0;
        $this->xpGained = $combatResult['rewards']['xp'] ?? 0;
        $this->goldData = $combatResult['rewards']['gold_data'] ?? [];
        $this->xpData = $combatResult['rewards']['xp_data'] ?? [];
        $this->damageDealt = $combatResult['rewards']['damage_dealt'] ?? 0;
        $this->pendingNotifications = $combatResult['notifications'] ?? [];
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



    private function completeBattle(): void
    {
        $this->isPlaying = false;
        $this->battleCompleted = true;
        $this->dispatch('stop-playback');
        
        if ($this->result === 'win' || $this->result === 'finished') {
            $this->sessionMonstersDefeated++;
            $this->dispatch('play-audio', type: 'victory');
        } elseif ($this->result === 'lose' || $this->result === 'dead') {
            $this->dispatch('play-audio', type: 'defeat');
        }

        if ($this->result === 'win' || $this->result === 'finished') {
            $this->applyRewards();
        }

        // Emit pending notifications
        if (!empty($this->pendingNotifications)) {
            foreach ($this->pendingNotifications as $notification) {
                $this->dispatch('notify', type: $notification['type'], message: $notification['message']);
            }
            $this->pendingNotifications = []; // clear after sending
        }

        // Auto-chain next battle (not for worldboss 'finished')
        if ($this->autoChain && $this->result === 'win' && empty($this->levelUps)) {
            $this->dispatch('auto-chain-next-battle');
        } else {
            if (!empty($this->levelUps)) {
                $this->autoChain = false; // Zatrzymaj automat na stałe
            }
            $this->dispatch('encounter-finished', result: $this->result);
        }
    }

    private function applyRewards(): void
    {
        $encounter = Encounter::find($this->currentEncounterId);

        if (!$encounter || !in_array($encounter->state, ['win', 'finished'])) {
            return;
        }

        // Apply gold and XP rewards to character
        $this->character->update([
            'gold' => $this->character->gold + $this->goldGained,
            'xp' => $this->character->xp + $this->xpGained,
        ]);

        $this->character = $this->character->fresh();

        $levelUpService = app(\App\Application\Characters\LevelUpService::class);
        $result = $levelUpService->checkAndApply($this->character);
        
        if ($result->isOk() && $result->getPayload()->hadLevelUp) {
            $this->dispatch('play-audio', type: 'levelup');
            foreach ($result->getPayload()->levelUps as $levelUp) {
                 $this->levelUps[] = [
                     'from' => $levelUp['from'],
                     'to' => $levelUp['to'],
                     'attribute_points' => 3,
                 ];
            }
            $this->character = $this->character->fresh();
            
            // Pokaż okno awansu dla najwyższego zdobytego poziomu
            $highestLevel = end($this->levelUps)['to'];
            $this->dispatch('open-level-up-modal', level: $highestLevel);
        }

        // Mark encounter rewards as applied
        $encounter->markRewardsApplied();
    }

    private function getXpRequiredForLevel(int $level): int
    {
        return app(\App\Application\Characters\LevelUpService::class)->xpToNext($level - 1);
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
