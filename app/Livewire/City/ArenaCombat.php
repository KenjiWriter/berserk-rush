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
            'equipment_stats' => $mySnap['equipment_stats'] ?? [],
            'weapon_type' => $mySnap['weapon_type'] ?? 'barehands',
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
            'equipment_stats' => $enemySnap['equipment_stats'] ?? [],
            'weapon_type' => $enemySnap['weapon_type'] ?? 'barehands',
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

    public bool $isAttackerView = true;

    private function loadGvGData(): void
    {
        $fight = GuildWarFight::with(['guildWar.challengerGuild', 'guildWar.defenderGuild'])->findOrFail($this->guildWarFightId);
        $war = $fight->guildWar;
        
        $challengerSnaps = $fight->challenger_snapshot ?? [];
        $defenderSnaps = $fight->defender_snapshot ?? [];

        // Normalize single snapshot vs array of snapshots
        if (isset($challengerSnaps['name'])) {
            $challengerSnaps = [$challengerSnaps];
        }
        if (isset($defenderSnaps['name'])) {
            $defenderSnaps = [$defenderSnaps];
        }

        // Determine view perspective
        $isAttacker = true;
        $inDefenderRoster = false;
        foreach ($defenderSnaps as $snap) {
            if (($snap['id'] ?? $snap['character_id'] ?? null) === $this->character->id) {
                $inDefenderRoster = true;
                break;
            }
        }

        if ($inDefenderRoster || ($war && $this->character->guild_id === $war->defender_guild_id) || ($fight->defender_character_id && $this->character->id === $fight->defender_character_id)) {
            $isAttacker = false;
        }

        $this->isAttackerView = $isAttacker;

        $mySnaps = $isAttacker ? $challengerSnaps : $defenderSnaps;
        $enemySnaps = $isAttacker ? $defenderSnaps : $challengerSnaps;

        // Representative snapshot
        $primaryMySnap = $mySnaps[0] ?? [
            'name' => 'Drużyna',
            'level' => 1,
            'max_hp' => 100,
            'attributes' => ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
        ];

        foreach ($mySnaps as $snap) {
            if (($snap['id'] ?? $snap['character_id'] ?? null) === $this->character->id) {
                $primaryMySnap = $snap;
                break;
            }
        }

        $primaryEnemySnap = $enemySnaps[0] ?? [
            'name' => 'Wroga Drużyna',
            'level' => 1,
            'max_hp' => 100,
            'attributes' => ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
        ];

        $myCharId = $primaryMySnap['id'] ?? $primaryMySnap['character_id'] ?? null;
        $enemyCharId = $primaryEnemySnap['id'] ?? $primaryEnemySnap['character_id'] ?? null;

        $myChar = $myCharId ? Character::find($myCharId) : null;
        $enemyChar = $enemyCharId ? Character::find($enemyCharId) : null;

        $myGuild = $war ? ($isAttacker ? $war->challengerGuild : $war->defenderGuild) : null;
        $enemyGuild = $war ? ($isAttacker ? $war->defenderGuild : $war->challengerGuild) : null;

        $myTotalMaxHp = array_sum(array_map(fn($s) => $s['max_hp'] ?? 0, $mySnaps)) ?: ($primaryMySnap['max_hp'] ?? 100);
        $enemyTotalMaxHp = array_sum(array_map(fn($s) => $s['max_hp'] ?? 0, $enemySnaps)) ?: ($primaryEnemySnap['max_hp'] ?? 100);

        $this->player = [
            'name' => $myGuild ? $myGuild->name : ($primaryMySnap['name'] ?? 'Drużyna'),
            'level' => $primaryMySnap['level'] ?? 1,
            'avatar' => ($myChar && $myChar->avatar) ? asset("img/avatars/{$myChar->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $myTotalMaxHp,
            'hp' => $myTotalMaxHp,
            'stats' => $primaryMySnap['attributes'] ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
            'equipment_stats' => $primaryMySnap['equipment_stats'] ?? [],
            'weapon_type' => $primaryMySnap['weapon_type'] ?? 'barehands',
            'skills' => $primaryMySnap['skills'] ?? [],
            'team' => $mySnaps,
        ];

        $this->enemy = [
            'name' => $enemyGuild ? $enemyGuild->name : ($primaryEnemySnap['name'] ?? 'Wroga Drużyna'),
            'level' => $primaryEnemySnap['level'] ?? 1,
            'avatar' => ($enemyChar && $enemyChar->avatar) ? asset("img/avatars/{$enemyChar->avatar}.png") : asset('img/avatars/default.png'),
            'maxHp' => $enemyTotalMaxHp,
            'hp' => $enemyTotalMaxHp,
            'stats' => $primaryEnemySnap['attributes'] ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
            'equipment_stats' => $primaryEnemySnap['equipment_stats'] ?? [],
            'weapon_type' => $primaryEnemySnap['weapon_type'] ?? 'barehands',
            'skills' => $primaryEnemySnap['skills'] ?? [],
            'team' => $enemySnaps,
        ];

        $this->isCalculating = false;

        if (!empty($fight->turns)) {
            $this->allTurns = $this->transformGvGTurnsToPerspective($fight->turns, $isAttacker);
            $this->playerFirst = true;
            
            if ($war && $war->winner_guild_id) {
                $iAmWinner = ($war->winner_guild_id === $this->character->guild_id || ($isAttacker && $war->winner_guild_id === $war->challenger_guild_id));
            } else {
                $challengerSurvivors = $fight->challenger_survivors ?? 0;
                $defenderSurvivors = $fight->defender_survivors ?? 0;
                $iAmWinner = $isAttacker ? ($challengerSurvivors >= $defenderSurvivors) : ($defenderSurvivors >= $challengerSurvivors);
            }

            $this->result = $iAmWinner ? 'win' : 'lose';
            $this->isPlaying = true;
        }
    }

    private function transformGvGTurnsToPerspective(array $turns, bool $isAttackerView): array
    {
        $mySideName = $isAttackerView ? 'challenger' : 'defender';

        return array_map(function ($turn) use ($mySideName) {
            $newTurn = $turn;
            
            $actorSide = $turn['actor_side'] ?? (($turn['actor'] ?? '') === 'attacker' ? 'challenger' : 'defender');
            $newTurn['actor'] = ($actorSide === $mySideName) ? 'player' : 'enemy';

            if (!empty($turn['team_state'])) {
                $myTeamHp = 0;
                $enemyTeamHp = 0;
                foreach ($turn['team_state'] as $member) {
                    if ($member['side'] === $mySideName) {
                        $myTeamHp += max(0, $member['hp']);
                    } else {
                        $enemyTeamHp += max(0, $member['hp']);
                    }
                }
                $newTurn['playerHp'] = $myTeamHp;
                $newTurn['enemyHp'] = $enemyTeamHp;
            } else {
                $newTurn['playerHp'] = $turn['playerHp'] ?? ($newTurn['actor'] === 'player' ? ($turn['attackerHp'] ?? 0) : ($turn['defenderHp'] ?? 0));
                $newTurn['enemyHp'] = $turn['enemyHp'] ?? ($newTurn['actor'] === 'enemy' ? ($turn['attackerHp'] ?? 0) : ($turn['defenderHp'] ?? 0));
            }

            return $newTurn;
        }, $turns);
    }

    public function getCurrentTeamMembers(string $side): array
    {
        if ($this->type !== 'gvg') {
            return [];
        }

        $mySideName = $this->isAttackerView ? 'challenger' : 'defender';
        $targetSideName = ($side === 'player') ? $mySideName : ($mySideName === 'challenger' ? 'defender' : 'challenger');

        $teamSnaps = ($side === 'player') ? ($this->player['team'] ?? []) : ($this->enemy['team'] ?? []);

        $lastTurn = !empty($this->visibleTurns) ? end($this->visibleTurns) : null;
        $teamStateMap = [];
        if ($lastTurn && !empty($lastTurn['team_state'])) {
            foreach ($lastTurn['team_state'] as $m) {
                if ($m['side'] === $targetSideName) {
                    $teamStateMap[$m['idx']] = $m;
                }
            }
        }

        $activeActorIdx = null;
        $activeTargetIdx = null;
        if ($lastTurn) {
            if (($lastTurn['actor_side'] ?? null) === $targetSideName) {
                $activeActorIdx = $lastTurn['actor_idx'] ?? null;
            }
            if (($lastTurn['target_side'] ?? null) === $targetSideName) {
                $activeTargetIdx = $lastTurn['target_idx'] ?? null;
            }
        }

        $result = [];
        foreach ($teamSnaps as $idx => $snap) {
            $charId = $snap['id'] ?? $snap['character_id'] ?? null;
            $char = $charId ? Character::find($charId) : null;
            $avatarUrl = ($char && $char->avatar)
                ? asset("img/avatars/{$char->avatar}.png")
                : (isset($snap['avatar']) && $snap['avatar'] ? asset("img/avatars/{$snap['avatar']}.png") : asset('img/avatars/default.png'));

            $hpState = $teamStateMap[$idx] ?? null;
            $currentHp = $hpState ? $hpState['hp'] : ($snap['max_hp'] ?? 100);
            $maxHp = $hpState ? $hpState['maxHp'] : ($snap['max_hp'] ?? 100);
            $isAlive = $hpState ? $hpState['alive'] : ($currentHp > 0);

            $result[] = [
                'side' => $targetSideName,
                'idx' => $idx,
                'id' => $charId,
                'name' => $snap['name'] ?? "Bohater #" . ($idx + 1),
                'level' => $snap['level'] ?? 1,
                'avatar' => $avatarUrl,
                'hp' => max(0, $currentHp),
                'maxHp' => max(1, $maxHp),
                'hpPercent' => max(0, min(100, ($currentHp / max(1, $maxHp)) * 100)),
                'alive' => $isAlive,
                'stats' => $snap['attributes'] ?? ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
                'equipment_stats' => $snap['equipment_stats'] ?? [],
                'weapon_type' => $snap['weapon_type'] ?? 'barehands',
                'skills' => $snap['skills'] ?? [],
                'is_active_actor' => ($activeActorIdx !== null && (int)$activeActorIdx === (int)$idx),
                'is_active_target' => ($activeTargetIdx !== null && (int)$activeTargetIdx === (int)$idx),
            ];
        }

        // Sort so the currently attacking actor (or active target) goes to the TOP OF THE STACK (index 0)
        usort($result, function ($a, $b) {
            if ($a['is_active_actor']) return -1;
            if ($b['is_active_actor']) return 1;
            if ($a['is_active_target']) return -1;
            if ($b['is_active_target']) return 1;
            if ($a['alive'] !== $b['alive']) {
                return $a['alive'] ? -1 : 1;
            }
            return $a['idx'] <=> $b['idx'];
        });

        return $result;
    }

    public function getSkillsWithIcons(array $participant): array
    {
        $skills = $participant['skills'] ?? [];
        if (empty($skills)) return [];

        $missingIconIds = [];
        foreach ($skills as $s) {
            if (empty($s['icon']) && !empty($s['id'])) {
                $missingIconIds[] = $s['id'];
            }
        }

        if (!empty($missingIconIds)) {
            $skillModels = \App\Infrastructure\Persistence\CombatSkill::whereIn('id', array_unique($missingIconIds))->pluck('icon', 'id');
            foreach ($skills as &$s) {
                if (empty($s['icon']) && !empty($s['id']) && isset($skillModels[$s['id']])) {
                    $s['icon'] = $skillModels[$s['id']];
                }
            }
        }

        return $skills;
    }

    public function getCombatStats(array $participant, array $opponent = []): array
    {
        if (empty($participant)) return [];

        $attrs = $participant['stats'] ?? [];
        $eqStats = $participant['equipment_stats'] ?? [];
        $weaponType = $participant['weapon_type'] ?? 'barehands';
        $level = $participant['level'] ?? 1;

        $str = $attrs['str'] ?? 0;
        $int = $attrs['int'] ?? 0;
        $vit = $attrs['vit'] ?? 0;
        $agi = $attrs['agi'] ?? 0;

        $oppAttrs = $opponent['stats'] ?? [];
        $oppAgi = $oppAttrs['agi'] ?? 0;

        $statBonus = match ($weaponType) {
            'bow', 'sword', 'dagger' => $str + $agi,
            'bell' => $str + $int,
            'wand' => $int * 2,
            'axe' => $str * 2,
            default => $str * 2,
        };

        $weaponAtkMin = ($eqStats['attack_min'] ?? 0) + ($eqStats['magic_attack_min'] ?? 0);
        $weaponAtkMax = ($eqStats['attack_max'] ?? 0) + ($eqStats['magic_attack_max'] ?? 0);

        $baseDmgMin = 10 + $statBonus + ($level * 1) + $weaponAtkMin;
        $baseDmgMax = 10 + $statBonus + ($level * 1) + $weaponAtkMax;

        $baseCrit = 5 + ($agi * 0.5) + ($eqStats['crit_chance'] ?? 0);
        $critPenalty = $oppAgi * 0.15;
        $effectiveCrit = max(0, min(50, $baseCrit - $critPenalty));

        $baseDodge = 2 + ($agi * 0.3);
        $dodgePenalty = $oppAgi * 0.10;
        $effectiveDodge = max(0, min(50, $baseDodge - $dodgePenalty));

        $defense = $vit + (int)($level / 2) + ($eqStats['defense'] ?? 0);

        return [
            'crit_chance' => round($effectiveCrit, 1),
            'dodge_chance' => round($effectiveDodge, 1),
            'atk_min' => $baseDmgMin,
            'atk_max' => max($baseDmgMin, $baseDmgMax),
            'defense' => $defense,
        ];
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
