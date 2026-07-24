<?php

namespace App\Livewire\City;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Infrastructure\Persistence\GuildWarFight;
use Livewire\Attributes\Computed;

class ArenaCombat extends Component
{
    public Character $character;
    
    // Encounter data (can be either PvP or GvG)
    public ?string $pvpEncounterId = null;
    public ?int $guildWarFightId = null;
    public string $type = 'pvp'; // 'pvp' or 'gvg'

    // Combat state
    public array $player = [];
    public array $enemy = [];
    public array $allTurns = [];
    public array $visibleTurns = [];
    public string $result = '';
    public bool $playerFirst = true;

    // Playback controls
    public bool $isPlaying = false;
    public int $playbackSpeed = 1;
    public int $currentTurnIndex = 0;
    public bool $isCalculating = false;
    public bool $battleCompleted = false;

    // Rewards
    public int $eloChange = 0;
    public int $tokensReward = 0;

    #[Computed]
    public function equippedSkills()
    {
        return \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $this->character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();
    }

    public function mount(Character $character, ?string $pvpId = null, ?int $gvgId = null): void
    {
        if (Auth::user()->id !== $character->user_id) {
            abort(403, 'Nie możesz wejść do postaci innego gracza.');
        }

        $this->playbackSpeed = session('combat_playback_speed', 1);
        $this->character = $character;
        
        if ($pvpId) {
            $this->type = 'pvp';
            $this->pvpEncounterId = $pvpId;
            $this->loadPvPData();
        } elseif ($gvgId) {
            $this->type = 'gvg';
            $this->guildWarFightId = $gvgId;
            $this->loadGvGData();
        } else {
            abort(404, 'Brak walki do odtworzenia.');
        }
    }

    private function loadPvPData(): void
    {
        $encounter = PvpEncounter::findOrFail($this->pvpEncounterId);
        
        // Determine who is player and who is enemy
        $isAttacker = $encounter->attacker_character_id === $this->character->id;
        
        $mySnap = $isAttacker ? $encounter->attacker_snapshot : $encounter->defender_snapshot;
        $enemySnap = $isAttacker ? $encounter->defender_snapshot : $encounter->attacker_snapshot;

        // Setup Player
        $this->player = [
            'name' => $mySnap['name'],
            'level' => $mySnap['level'],
            'avatar' => $this->character->avatar ? asset("img/avatars/{$this->character->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $mySnap['max_hp'],
            'hp' => $mySnap['max_hp'],
            'stats' => $mySnap['attributes'],
            'skills' => $mySnap['skills'] ?? []
        ];

        // Enemy Character to get their actual avatar
        $enemyChar = Character::find($isAttacker ? $encounter->defender_character_id : $encounter->attacker_character_id);

        // Setup Enemy (Widmo gracza)
        $this->enemy = [
            'name' => $enemySnap['name'],
            'level' => $enemySnap['level'],
            'avatar' => ($enemyChar && $enemyChar->avatar) ? asset("img/avatars/{$enemyChar->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $enemySnap['max_hp'],
            'hp' => $enemySnap['max_hp'],
            'stats' => $enemySnap['attributes'],
            'skills' => $enemySnap['skills'] ?? []
        ];

        if ($encounter->state === 'calculating') {
            $this->isCalculating = true;
        } elseif ($encounter->state === 'finished') {
            $this->isCalculating = false;
            $this->setupFinishedCombat($encounter->turns, clone $encounter, clone $encounter, $isAttacker);
            
            // Auto start playback for PvP - triggered via wire:init
            $this->isPlaying = true;
        } else {
            $this->isCalculating = false;
        }
    }

    private function loadGvGData(): void
    {
        $fight = GuildWarFight::findOrFail($this->guildWarFightId);
        
        // Determine view perspective.
        // If the viewer is one of the fighters, they are "player". Otherwise, challenger is "player".
        if ($this->character->id === $fight->defender_character_id) {
            $isAttacker = false;
            $mySnap = $fight->defender_snapshot;
            $enemySnap = $fight->challenger_snapshot;
            $myChar = Character::find($fight->defender_character_id);
            $enemyChar = Character::find($fight->challenger_character_id);
        } else {
            $isAttacker = true;
            $mySnap = $fight->challenger_snapshot;
            $enemySnap = $fight->defender_snapshot;
            $myChar = Character::find($fight->challenger_character_id);
            $enemyChar = Character::find($fight->defender_character_id);
        }

        $this->player = [
            'name' => $mySnap['name'],
            'level' => $mySnap['level'],
            'avatar' => ($myChar && $myChar->avatar) ? asset("img/avatars/{$myChar->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $mySnap['max_hp'],
            'hp' => $mySnap['max_hp'],
            'stats' => $mySnap['attributes'],
            'skills' => $mySnap['skills'] ?? []
        ];

        $this->enemy = [
            'name' => $enemySnap['name'],
            'level' => $enemySnap['level'],
            'avatar' => ($enemyChar && $enemyChar->avatar) ? asset("img/avatars/{$enemyChar->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $enemySnap['max_hp'],
            'hp' => $enemySnap['max_hp'],
            'stats' => $enemySnap['attributes'],
            'skills' => $enemySnap['skills'] ?? []
        ];

        $this->isCalculating = false;
        if ($fight->turns) {
            $this->allTurns = $this->transformTurnsToPerspective($fight->turns, $isAttacker);
            $this->playerFirst = $fight->combat_data['attacker_first'] ?? true;
            if (!$isAttacker) $this->playerFirst = !$this->playerFirst;
            
            $iAmWinner = ($fight->winner_character_id === ($isAttacker ? $fight->challenger_character_id : $fight->defender_character_id));
            $this->result = $iAmWinner ? 'win' : 'lose';
            
            // Auto start playback for GvG - triggered via wire:init
            $this->isPlaying = true;
        }
    }

    public function checkCombatStatus(): void
    {
        if (!$this->isCalculating || !$this->pvpEncounterId) {
            return;
        }

        $encounter = PvpEncounter::find($this->pvpEncounterId);
        
        if ($encounter && $encounter->state === 'finished') {
            $this->isCalculating = false;
            $isAttacker = $encounter->attacker_character_id === $this->character->id;
            $this->setupFinishedCombat($encounter->turns, clone $encounter, clone $encounter, $isAttacker);
            
            $this->isPlaying = true;
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        }
    }

    private function setupFinishedCombat(array $turns, $encounter, $originalEncounter, bool $isAttacker): void
    {
        $this->allTurns = $this->transformTurnsToPerspective($turns, $isAttacker);
        
        $this->playerFirst = $encounter->combat_data['attacker_first'] ?? true;
        if (!$isAttacker) {
            $this->playerFirst = !$this->playerFirst;
        }

        $iAmWinner = ($encounter->winner_character_id === $this->character->id);
        $this->result = $iAmWinner ? 'win' : 'lose';

        if ($this->type === 'pvp') {
            $this->eloChange = $isAttacker ? $encounter->attacker_elo_change : $encounter->defender_elo_change;
            $this->tokensReward = $iAmWinner ? $encounter->arena_tokens_reward : 3;
        }
    }

    /**
     * Map 'attacker' / 'defender' to 'player' / 'enemy' depending on who is viewing
     */
    private function transformTurnsToPerspective(array $turns, bool $isAttackerView): array
    {
        return array_map(function ($turn) use ($isAttackerView) {
            $newTurn = $turn;
            
            if ($isAttackerView) {
                // Attacker is 'player', Defender is 'enemy'
                $newTurn['actor'] = $turn['actor'] === 'attacker' ? 'player' : 'enemy';
                $newTurn['playerHp'] = $turn['attackerHp'];
                $newTurn['enemyHp'] = $turn['defenderHp'];
            } else {
                // Defender is 'player', Attacker is 'enemy'
                $newTurn['actor'] = $turn['actor'] === 'defender' ? 'player' : 'enemy';
                $newTurn['playerHp'] = $turn['defenderHp'];
                $newTurn['enemyHp'] = $turn['attackerHp'];
            }
            
            return $newTurn;
        }, $turns);
    }

    public function startPlayback(): void
    {
        if ($this->isPlaying && $this->currentTurnIndex < count($this->allTurns)) {
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        }
    }

    public function togglePlayback(): void
    {
        $this->isPlaying = !$this->isPlaying;
        if ($this->isPlaying && $this->currentTurnIndex < count($this->allTurns)) {
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        } else {
            $this->dispatch('stop-playback');
        }
    }

    public function setPlaybackSpeed(int $speed): void
    {
        $this->playbackSpeed = $speed;
        session(['combat_playback_speed' => $speed]);
        if ($this->isPlaying) {
            $this->dispatch('update-playback-speed', speed: $speed);
        }
    }

    public function nextTurn(): void
    {
        if ($this->currentTurnIndex < count($this->allTurns)) {
            $turn = $this->allTurns[$this->currentTurnIndex];
            $this->visibleTurns[] = $turn;
            $this->currentTurnIndex++;
            
            $audioType = $turn['type'] === 'miss' ? 'dodge' : (!empty($turn['crit']) ? 'crit' : 'hit');

            $this->dispatch('turn-played', 
                actor: $turn['actor'], 
                type: $turn['type'], 
                value: $turn['value'] ?? 0,
                dotDamage: $turn['dotDamage'] ?? 0,
                dotType: $turn['dotType'] ?? null,
                crit: !empty($turn['crit']),
                skillName: $turn['skill_name'] ?? null,
                effectType: $turn['effect_type'] ?? null,
                audioType: $audioType
            );

            if ($this->currentTurnIndex >= count($this->allTurns)) {
                $this->completeBattle();
            }
        }
    }

    private function completeBattle(): void
    {
        $this->isPlaying = false;
        $this->battleCompleted = true;
        $this->dispatch('stop-playback');
    }

    public function backToArena(): void
    {
        $this->redirect(route('city.arena', $this->character), navigate: true);
    }
    
    public function backToGuild(): void
    {
        $this->redirect(route('city.guild', $this->character), navigate: true);
    }

    public function getCurrentPlayerHp(): int
    {
        if (empty($this->visibleTurns)) return $this->player['hp'] ?? 0;
        return end($this->visibleTurns)['playerHp'] ?? 0;
    }

    public function getCurrentEnemyHp(): int
    {
        if (empty($this->visibleTurns)) return $this->enemy['hp'] ?? 0;
        return end($this->visibleTurns)['enemyHp'] ?? 0;
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
        if (empty($this->visibleTurns)) return $this->playerFirst;
        return end($this->visibleTurns)['actor'] === 'player';
    }

    public function isEnemyTurn(): bool
    {
        if (empty($this->visibleTurns)) return !$this->playerFirst;
        return end($this->visibleTurns)['actor'] === 'enemy';
    }

    public function getCurrentState(): ?array
    {
        if (empty($this->visibleTurns)) return null;
        return end($this->visibleTurns)['state'] ?? null;
    }

    public function render()
    {
        return view('livewire.city.arena-combat');
    }
}
