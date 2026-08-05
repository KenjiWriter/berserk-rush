<?php

namespace App\Livewire\Adventure;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Infrastructure\Persistence\Map;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Encounter;
use App\Application\Characters\LevelUpService;
use App\Application\LocationEvents\LocationEventService;
use App\Infrastructure\Persistence\LocationEvent;
use App\Infrastructure\Persistence\LocationEventUpgradeLevel;
use App\Infrastructure\Persistence\CharacterLocationEventRun;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

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
    public int $currentMana = -1;

    // Session Tracking
    public int $sessionMonstersDefeated = 0;
    public int $sessionGoldEarned = 0;
    public int $sessionXpEarned = 0;
    public int $sessionGemsEarned = 0;
    public int $sessionStartTime = 0;
    public array $sessionItemsCollected = [];

    // Playback controls
    public bool $isPlaying = false;
    public int $playbackSpeed = 1;
    public bool $autoChain = true;
    public int $currentTurnIndex = 0;
    public bool $isCalculating = false;

    // Multi-monster & Targeting Tactic state
    public string $targetStrategy = 'random';
    public bool $isOverLevelCombat = false;
    public array $enemies = [];

    // Tier scaling state
    public int $playerTier = 1;
    public int $tierDiff = 0;
    public float $tierMultiplier = 1.0;
    public ?string $tierLabel = null;

    // Multi-tab anti-cheat session token
    public string $tabSessionToken = '';
    public bool $isInactiveTab = false;

    #[Computed]
    public function equippedSkills()
    {
        return \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $this->character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();
    }

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
    public ?int $worldBossMonsterId = null;
    public array $pendingNotifications = [];

    // Location Event state (Faza 2) - równoległy tryb walki obok normalnej eksploracji,
    // patrz docs/modules/location_events.md. Cały przepływ (async job + polling) jest
    // odizolowany od powyższego stanu normalnej walki (synchronicznej) - jedyne punkty
    // styku to guard w startBattle() i gałąź w nextTurn()/finishAllTurns().
    public ?int $pendingEventId = null;
    public ?int $pendingUpgradeLevelId = null;
    public ?array $pendingEventPreview = null;
    public ?int $activeEventRunId = null;
    public bool $inLocationEvent = false;
    public bool $isEventCalculating = false;
    public ?string $eventStageResult = null;
    public ?string $eventRunResult = null;
    public array $eventRunFinalLoot = [];
    public array $eventMonsterInfo = [];
    public int $eventRunProgressCurrent = 0;
    public int $eventRunProgressTotal = 0;
    public bool $eventAutoAdvancePaused = false;
    public ?string $eventName = null;
    public bool $eventIsHardcore = false;
    public ?string $otherMapEventMapName = null;
    public bool $eventsEnabled = true;

    #[On('tutorial-completed')]
    public function refreshOnTutorial()
    {
        // Force refresh
        $this->character->refresh();
    }

    public function isActiveTab(): bool
    {
        if (empty($this->tabSessionToken)) {
            return true;
        }
        $activeToken = \Illuminate\Support\Facades\Cache::get("adventure_active_tab:{$this->character->id}");
        return $activeToken === null || $activeToken === $this->tabSessionToken;
    }

    public function claimActiveTab(): void
    {
        if (empty($this->tabSessionToken)) {
            $this->tabSessionToken = \Illuminate\Support\Str::uuid()->toString();
        }
        \Illuminate\Support\Facades\Cache::put("adventure_active_tab:{$this->character->id}", $this->tabSessionToken, now()->addMinutes(10));
        $this->isInactiveTab = false;
        $this->resetErrorBag('battle');
        $this->autoChain = session('combat_auto_chain', true);
    }

    public function mount(Character $character, Map $map): void
    {
        $this->sessionStartTime = time();
        $this->character = $character;

        $rawSpeed = session('combat_playback_speed', 1);
        if ($rawSpeed === 5 && !$this->canUseSpeed5()) {
            $rawSpeed = 2;
        }
        $this->playbackSpeed = in_array($rawSpeed, [1, 2, 5], true) ? $rawSpeed : 1;
        $this->autoChain = session('combat_auto_chain', true);
        $this->targetStrategy = session('combat_target_strategy', 'random');
        $this->eventsEnabled = session('combat_events_enabled', true);

        if ($this->character->hasActiveMirror() && !request()->has('world_boss')) {
            session()->flash('error', 'Lustro jest aktywne! Zwykłe Mapy są zablokowane podczas trwania lustra.');
            $this->redirect(route('city.adventure', ['character' => $this->character, 'tab' => 'dungeons']), navigate: true);
            return;
        }

        $this->character->syncMissingPoints();
        $this->map = $map;
        $this->background = $this->backgroundFor($map);
        $this->initPlayerData($character);
        $this->claimActiveTab();

        if (Auth::user()->game_stage <= 12) {
            $this->autoChain = false;
            session(['combat_auto_chain' => false]);
        }

        $isValidWorldBossRequest = false;
        if (request()->has('world_boss')) {
            $worldBossId = (int)request()->query('world_boss');
            
            // Sprawdź czy boss w ogóle istnieje na tej mapie jako aktywny boss. Filtrujemy
            // whereNotNull('level_bracket'), żeby ewentualne osierocone rekordy sprzed
            // rework'u przedziałów (level_bracket = null, migracja go nie backfilluje) nie
            // zostały omyłkowo dopasowane zamiast właściwej, aktywnej instancji - w razie
            // takich śmieci w bazie uruchom `php artisan app:world-boss-reset`.
            $worldBossInstance = \App\Infrastructure\Persistence\WorldBossInstance::where('map_id', $this->map->id)
                ->where('monster_id', $worldBossId)
                ->whereNotNull('level_bracket')
                ->latest('id')
                ->first();

            if ($worldBossInstance) {
                $characterBracket = \App\Application\Combat\WorldBossService::bracketForLevel($this->character->level);
                $hasParticipated = \App\Infrastructure\Persistence\WorldBossDamageLog::where('world_boss_instance_id', $worldBossInstance->id)
                    ->where('character_id', $this->character->id)
                    ->exists();

                if ($characterBracket !== $worldBossInstance->level_bracket) {
                    $this->isWorldBoss = false;
                    $this->worldBossMonsterId = null;
                    session()->flash('warning', 'Ten World Boss nie jest przeznaczony dla Twojego poziomu.');
                    $this->redirect(route('city.adventure', $this->character), navigate: true);
                    return;
                } elseif ($worldBossInstance->current_hp <= 0) {
                    $this->isWorldBoss = false;
                    $this->worldBossMonsterId = null;
                    session()->flash('warning', 'Ten World Boss został już pokonany! Poczekaj na reset za pełną godzinę.');
                    $this->redirect(route('city.adventure', $this->character), navigate: true);
                    return;
                } elseif ($hasParticipated) {
                    $this->isWorldBoss = false;
                    $this->worldBossMonsterId = null;
                    session()->flash('warning', 'Już brałeś udział w walce z tym World Bossem!');
                    $this->redirect(route('city.adventure', $this->character), navigate: true);
                    return;
                } else {
                    $this->isWorldBoss = true;
                    $this->worldBossMonsterId = $worldBossId;
                    $isValidWorldBossRequest = true;
                }
            } else {
                $this->isWorldBoss = false;
                $this->worldBossMonsterId = null;
                session()->flash('warning', 'Ten World Boss nie jest obecnie aktywny na tej mapie.');
                $this->redirect(route('city.adventure', $this->character), navigate: true);
                return;
            }
        }

        // Hard block level requirement for normal map exploration (World Boss accessibility is governed by level bracket)
        if (!$isValidWorldBossRequest && !$map->isAccessibleBy($character)) {
            session()->flash('error', "Twój poziom ({$character->level}) wykracza poza przedział tej mapy (wymagany poziom: {$map->level_range}).");
            $this->redirect(route('city.adventure', $character), navigate: true);
            return;
        }

        $this->currentMana = $character->getMaxMana();

        $this->resumeActiveEventRunIfAny();
    }

    /**
     * Wznawia stan eventu lokacji po odświeżeniu strony/ponownym wejściu na mapę -
     * wzorem DungeonRun::mount(). Aktywny run na INNEJ mapie tylko sygnalizuje banner
     * (nie auto-resume tam), bo gracz jest teraz na innej mapie.
     */
    private function resumeActiveEventRunIfAny(): void
    {
        $activeRun = CharacterLocationEventRun::where('character_id', $this->character->id)
            ->where('is_completed', false)
            ->where('is_failed', false)
            ->first();

        if (!$activeRun) {
            return;
        }

        if ($activeRun->map_id !== $this->map->id) {
            $this->otherMapEventMapName = $activeRun->map->name ?? null;
            return;
        }

        $this->activeEventRunId = $activeRun->id;
        $this->inLocationEvent = true;
        $this->eventRunProgressCurrent = $activeRun->current_monster_index;
        $this->eventRunProgressTotal = $activeRun->total_monsters;
        $this->eventIsHardcore = $activeRun->is_hardcore;
        $this->eventName = $activeRun->locationEvent->name ?? null;

        if ($activeRun->combat_state === 'calculating') {
            $this->isEventCalculating = true;
        } elseif ($activeRun->combat_state === 'completed' && $activeRun->combat_data) {
            $this->setupEventBattleData($activeRun);
        }
    }

    public function setTargetStrategy(string $strategy): void
    {
        if (in_array($strategy, ['random', 'highest_hp', 'lowest_hp', 'highest_att', 'highest_def'])) {
            $this->targetStrategy = $strategy;
            session(['combat_target_strategy' => $strategy]);
        }
    }

    public function startBattle(?int $monsterId = null): void
    {
        if ($this->inLocationEvent) {
            // Gracz jest w trakcie eventu lokacji - normalna eksploracja jest wstrzymana
            // do końca runu (event_complete/lose + dismissEventRun()). UI i tak ukrywa
            // przycisk "Walcz" w tym stanie, to tylko dodatkowe zabezpieczenie server-side.
            return;
        }

        if ($this->character->hasActiveMirror() && !$this->isWorldBoss) {
            $this->autoChain = false;
            session(['combat_auto_chain' => false]);
            session()->flash('error', 'Lustro jest aktywne! Zwykłe Mapy są zablokowane podczas trwania lustra.');
            $this->redirect(route('city.adventure', ['character' => $this->character, 'tab' => 'dungeons']), navigate: true);
            return;
        }

        if (!$this->isActiveTab()) {
            $this->isInactiveTab = true;
            $this->autoChain = false;
            $this->addError('battle', 'Przygoda została aktywowana w innej karcie przeglądarki.');
            $this->isCalculating = false;
            $this->isPlaying = false;
            return;
        }

        \Illuminate\Support\Facades\Cache::put("adventure_active_tab:{$this->character->id}", $this->tabSessionToken, now()->addMinutes(10));

        $this->resetBattleState();

        // Rzut eventu lokacji - tylko dla naturalnej eksploracji (nie world boss, nie
        // wymuszony potwór). Jeśli wypadnie, wstrzymujemy normalną walkę i pokazujemy
        // modal wyboru trybu (Normalny/Hardcore) - patrz chooseEventMode()/declineEvent().
        if ($this->eventsEnabled && !$this->isWorldBoss && $monsterId === null) {
            $trigger = app(LocationEventService::class)->rollEventTrigger();
            if ($trigger) {
                /** @var LocationEvent $event */
                $event = $trigger['event'];
                /** @var LocationEventUpgradeLevel $upgradeLevel */
                $upgradeLevel = $trigger['upgrade_level'];

                $this->pendingEventId = $event->id;
                $this->pendingUpgradeLevelId = $upgradeLevel->id;
                $this->pendingEventPreview = [
                    'name' => $event->name,
                    'monster_count_min' => $event->monster_count_min + $upgradeLevel->monster_count_delta_min,
                    'monster_count_max' => $event->monster_count_max + $upgradeLevel->monster_count_delta_max,
                    'reward_multiplier' => round($event->reward_multiplier * $upgradeLevel->exp_multiplier, 2),
                ];
                $this->isCalculating = false;
                return;
            }
        }

        // Start new encounter
        $encounterService = app(EncounterService::class);
        
        // Zabezpieczenie: tylko przy aktywnym statusie World Boss pozwalamy na wymuszone ID potwora
        if ($this->isWorldBoss) {
            $monsterId = $monsterId ?? $this->worldBossMonsterId;
        } else {
            $monsterId = null;
        }

        $forcedMonster = $monsterId ? \App\Infrastructure\Persistence\Monster::find($monsterId) : null;
        
        $startResult = $encounterService->start(
            $this->character,
            $this->map,
            $forcedMonster,
            $this->targetStrategy
        );

        if ($startResult->isError()) {
            $this->addError('battle', $startResult->getErrorMessage());
            $this->isCalculating = false;
            $this->enemy = [];
            return;
        }

        if ($this->isWorldBoss) {
            $this->isWorldBoss = false;
            $this->worldBossMonsterId = null;
        }

        $encounter = $startResult->getPayload();
        $this->currentEncounterId = $encounter->id;

        // Synchronous simulation: combat is calculated in ~2ms directly in PHP!
        $simulateResult = $encounterService->simulate($encounter);

        if ($simulateResult->isError()) {
            $this->addError('battle', $simulateResult->getErrorMessage());
            $this->isCalculating = false;
            return;
        }

        $encounter->refresh();
        $combatResult = $simulateResult->getPayload();
        $this->setupBattleData($encounter, $combatResult);

        $this->isCalculating = false;
        $this->isPlaying = true;
        $this->dispatch('start-playback', speed: $this->playbackSpeed);
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

    public function chooseEventMode(bool $isHardcore): void
    {
        if (!$this->pendingEventId || !$this->pendingUpgradeLevelId) {
            return;
        }

        $event = LocationEvent::find($this->pendingEventId);
        $upgradeLevel = LocationEventUpgradeLevel::find($this->pendingUpgradeLevelId);
        $this->pendingEventId = null;
        $this->pendingUpgradeLevelId = null;
        $this->pendingEventPreview = null;

        if (!$event || !$upgradeLevel) {
            return;
        }

        $result = app(LocationEventService::class)->startRun($this->character, $this->map, $event, $upgradeLevel, $isHardcore);

        if ($result->isError()) {
            $this->addError('battle', $result->getErrorMessage());
            return;
        }

        $run = $result->getPayload();
        $this->activeEventRunId = $run->id;
        $this->inLocationEvent = true;
        $this->eventRunProgressCurrent = 0;
        $this->eventRunProgressTotal = $run->total_monsters;
        $this->eventIsHardcore = $run->is_hardcore;
        $this->eventName = $event->name;
        $this->eventRunResult = null;
        $this->eventRunFinalLoot = [];

        $this->fightNextEventStage();
    }

    public function declineEvent(): void
    {
        $this->pendingEventId = null;
        $this->pendingUpgradeLevelId = null;
        $this->pendingEventPreview = null;
    }

    public function fightNextEventStage(): void
    {
        if (!$this->activeEventRunId) {
            return;
        }

        $run = CharacterLocationEventRun::find($this->activeEventRunId);
        if (!$run || $run->is_completed || $run->is_failed) {
            return;
        }

        $this->resetBattleState();
        $this->isEventCalculating = true;

        app(LocationEventService::class)->fightNextMonster($run);
    }

    public function checkEventCombatStatus(): void
    {
        if (!$this->isEventCalculating || !$this->activeEventRunId) {
            return;
        }

        $run = CharacterLocationEventRun::find($this->activeEventRunId);

        if (!$run) {
            $this->addError('battle', 'Event nie istnieje.');
            $this->isEventCalculating = false;
            return;
        }

        if ($run->combat_state === 'error') {
            $this->addError('battle', 'Wystąpił błąd podczas obliczania walki eventu.');
            $this->isEventCalculating = false;
            return;
        }

        if ($run->combat_state === 'completed') {
            $this->isEventCalculating = false;
            $this->setupEventBattleData($run);
            $this->isPlaying = true;
            $this->dispatch('start-playback', speed: $this->playbackSpeed);
        }
    }

    private function setupEventBattleData(CharacterLocationEventRun $run): void
    {
        $data = $run->combat_data ?? [];

        $this->allTurns = $data['turns'] ?? [];
        $this->eventStageResult = $data['result'] ?? null;
        $this->result = ($this->eventStageResult === 'lose') ? 'lose' : 'win';
        $this->eventMonsterInfo = $data['monster'] ?? [];
        $this->eventName = $data['event_name'] ?? $this->eventName;
        $this->eventIsHardcore = $data['is_hardcore'] ?? $this->eventIsHardcore;

        $monsterMaxHp = $data['monster_max_hp'] ?? 0;
        $this->enemy = [
            'name' => $this->eventMonsterInfo['name'] ?? '',
            'level' => $this->eventMonsterInfo['level'] ?? 0,
            'maxHp' => $monsterMaxHp,
            'hp' => $monsterMaxHp,
            'avatar' => $this->eventMonsterInfo['avatar'] ?? null,
            'stats' => [],
        ];

        $this->player = [
            'name' => $this->character->name,
            'level' => $this->character->level,
            'avatar' => $this->character->getEffectiveAvatarUrl(),
            'maxHp' => $this->character->getMaxHp(),
            'hp' => $data['start_player_hp'] ?? $run->current_hp,
            'stats' => $this->character->getTotalAttributes(),
        ];

        $slotIndex = $data['slot'] ?? $run->current_monster_index;
        $this->eventRunProgressCurrent = min($run->total_monsters, $slotIndex + 1);
        $this->eventRunProgressTotal = $run->total_monsters;
    }

    /**
     * Wywoływane z nextTurn()/finishAllTurns() zamiast completeBattle() podczas
     * aktywnego eventu lokacji. Złoto/exp/itemy są już przyznane server-side
     * (LocationEventService::grantAccumulatedLoot(), wołane wewnątrz simulateStage()) -
     * tu tylko aktualizujemy UI.
     */
    private function completeEventStage(): void
    {
        $this->isPlaying = false;
        $this->dispatch('stop-playback');

        if ($this->eventStageResult === 'slot_clear') {
            $this->dispatch('play-audio', type: 'victory');
            if (!$this->eventAutoAdvancePaused) {
                $this->dispatch('auto-chain-next-event-stage', delay: 700);
            }
            return;
        }

        $run = $this->activeEventRunId ? CharacterLocationEventRun::find($this->activeEventRunId) : null;

        if ($this->eventStageResult === 'event_complete') {
            $this->dispatch('play-audio', type: 'victory');

            $oldLevel = $this->character->level;
            $this->character = $this->character->fresh();
            $this->eventRunFinalLoot = $run?->accumulated_loot ?? [];
            $this->eventRunResult = 'event_complete';

            if ($this->character->level > $oldLevel) {
                $this->dispatch('play-audio', type: 'levelup');
                $this->dispatch('open-level-up-modal', level: $this->character->level);
            }
            $this->dispatch('stats-updated', level: $this->character->level, xp: $this->character->xp, gold: $this->character->gold);
        } elseif ($this->eventStageResult === 'lose') {
            $this->dispatch('play-audio', type: 'defeat');
            $this->eventRunResult = 'lose';
        }
    }

    public function dismissEventRun(): void
    {
        $this->inLocationEvent = false;
        $this->activeEventRunId = null;
        $this->eventRunResult = null;
        $this->eventStageResult = null;
        $this->eventRunFinalLoot = [];
        $this->eventMonsterInfo = [];
        $this->eventRunProgressCurrent = 0;
        $this->eventRunProgressTotal = 0;
        $this->eventAutoAdvancePaused = false;
        $this->eventName = null;
        $this->resetBattleState();
    }

    public function pauseEventAutoAdvance(): void
    {
        $this->eventAutoAdvancePaused = true;
    }

    public function resumeEventAutoAdvance(): void
    {
        $this->eventAutoAdvancePaused = false;
        $this->fightNextEventStage();
    }

    public function useEventPotion(string $itemInstanceId): void
    {
        if (!$this->activeEventRunId || $this->isEventCalculating || $this->isPlaying) {
            return;
        }

        $run = CharacterLocationEventRun::find($this->activeEventRunId);
        if (!$run) {
            return;
        }

        $result = app(LocationEventService::class)->usePotion($run, $itemInstanceId);
        if ($result->isError()) {
            $this->addError('battle', $result->getErrorMessage());
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

        if ($this->isPlaying) {
            $this->dispatch('update-playback-speed', speed: $speed);
        }
    }

    public function finishAllTurns(): void
    {
        if (!$this->isPlaying) {
            return;
        }

        $this->visibleTurns = $this->allTurns;
        $this->currentTurnIndex = count($this->allTurns);

        if ($this->inLocationEvent) {
            $this->completeEventStage();
        } else {
            $this->completeBattle();
        }
    }

    public function toggleAutoChain(?bool $status = null): void
    {
        if (Auth::user()->game_stage <= 12) {
            $this->autoChain = false;
            session(['combat_auto_chain' => false]);
            return;
        }

        if ($status !== null) {
            $this->autoChain = (bool)$status;
        } else {
            $this->autoChain = !$this->autoChain;
        }

        session(['combat_auto_chain' => $this->autoChain]);
    }

    public function toggleEventsEnabled(?bool $status = null): void
    {
        $this->eventsEnabled = $status !== null ? (bool) $status : !$this->eventsEnabled;
        session(['combat_events_enabled' => $this->eventsEnabled]);
    }

    public function stopAuto(): void
    {
        $this->autoChain = false;
        session(['combat_auto_chain' => false]);
        $this->pause();
    }

    public function nextTurn(): void
    {
        if (!$this->isPlaying) {
            return;
        }

        if ($this->currentTurnIndex < count($this->allTurns)) {
            $turn = $this->allTurns[$this->currentTurnIndex];
            $this->visibleTurns[] = $turn;
            $this->currentTurnIndex++;
            
            $audioType = $turn['type'] === 'miss' ? 'dodge' : (!empty($turn['crit']) ? 'crit' : 'hit');

            // Dispatch event for UI animations & synchronized audio
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
                if ($this->inLocationEvent) {
                    $this->completeEventStage();
                } else {
                    $this->completeBattle();
                }
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

        $rankVal = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
        $setType = ($rankVal === 'worldboss' || $this->isWorldBoss) ? \App\Infrastructure\Persistence\CharacterEquipmentSetItem::SET_WORLD_BOSS : null;

        // Get total calculated attributes
        $playerAttributes = $character->getTotalAttributes($setType);

        // Calculate HP based on attributes
        $playerMaxHp = $character->getMaxHp($setType);

        $monsterMaxHp = $combatResult['enemy']['maxHp'] ?? ($monster->stats['hp'] ?? $monster->level * 20);
        $monsterStats = $combatResult['enemy']['stats'] ?? $monster->stats ?? [];

        // Set player data with explicitly mapped attributes
        $this->player = [
            'name' => $character->name,
            'level' => $character->level,
            'avatar' => $character->getEffectiveAvatarUrl(),
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
            'rank' => is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank,
        ];

        $this->isOverLevelCombat = $encounter->combat_data['is_overlevel'] ?? false;
        if ($this->isOverLevelCombat && !empty($encounter->combat_data['monsters'])) {
            $this->enemies = $encounter->combat_data['monsters'];
        } else {
            $this->enemies = [];
        }

        // Load tier scaling info
        $this->playerTier = (int)($encounter->combat_data['player_tier'] ?? 1);
        $this->tierDiff = (int)($encounter->combat_data['tier_diff'] ?? 0);
        $this->tierMultiplier = (float)($encounter->combat_data['tier_multiplier'] ?? 1.0);
        $this->tierLabel = $encounter->combat_data['tier_label'] ?? null;

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

    public function getCurrentMonstersState(): array
    {
        if (empty($this->visibleTurns)) {
            return $this->enemies;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['monsters_state'] ?? $this->enemies;
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
                $this->dispatch('notify', type: $notification['type'], message: $notification['message'], category: 'quest_achievement');
            }
            $this->pendingNotifications = []; // clear after sending
        }

        // Auto-chain next battle (not for worldboss 'finished').
        // Przegrana: 3s kary zamiast zatrzymywania automatu, żeby bezpiecznie farmić AFK.
        if ($this->autoChain && in_array($this->result, ['win', 'lose'], true) && empty($this->levelUps)) {
            $delay = $this->result === 'lose' ? 3000 : 700;
            $this->dispatch('auto-chain-next-battle', delay: $delay);
        } else {
            if (!empty($this->levelUps)) {
                $this->autoChain = false; // Zatrzymaj automat na stałe
                session(['combat_auto_chain' => false]);
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

        $oldLevel = $this->character->level;

        $encounterService = app(EncounterService::class);
        $res = $encounterService->applyRewards($encounter);

        if ($res->isOk()) {
            $this->sessionGoldEarned += $this->goldGained;
            $this->sessionXpEarned += $this->xpGained;
            $this->trackSessionDrops();
            $this->character = $this->character->fresh();

            // Record / update player's actual exp and gold per minute on this map
            $elapsedSec = time() - $this->sessionStartTime;
            if ($elapsedSec >= 15 && $this->map) {
                $expPerMin = (int) round(($this->sessionXpEarned / (float) $elapsedSec) * 60.0);
                $goldPerMin = (int) round(($this->sessionGoldEarned / (float) $elapsedSec) * 60.0);

                if ($expPerMin > 0 || $goldPerMin > 0) {
                    app(\App\Application\Mirror\MirrorService::class)->updateMapRates(
                        $this->character,
                        $this->map,
                        $expPerMin,
                        $goldPerMin
                    );
                }
            }

            if ($this->character->level > $oldLevel) {
                $this->dispatch('play-audio', type: 'levelup');
                $this->levelUps[] = [
                    'from' => $oldLevel,
                    'to' => $this->character->level,
                    'attribute_points' => ($this->character->level - $oldLevel) * 3,
                ];
                $this->dispatch('open-level-up-modal', level: $this->character->level);
                $this->dispatch('stats-updated', level: $this->character->level, xp: $this->character->xp, gold: $this->character->gold);
            } else {
                $this->dispatch('stats-updated', level: $this->character->level, xp: $this->character->xp, gold: $this->character->gold);
            }
        }
    }

    private function trackSessionDrops(): void
    {
        if (!empty($this->drops['gems'])) {
            $this->sessionGemsEarned += (int) $this->drops['gems'];
        }

        $typedDrops = array_merge(
            array_map(fn ($item) => $item + ['type' => 'item'], $this->drops['items'] ?? []),
            array_map(fn ($material) => $material + ['type' => 'material'], $this->drops['materials'] ?? [])
        );

        foreach ($typedDrops as $drop) {
            $key = $drop['template_id'] ?? $drop['name'];

            if (isset($this->sessionItemsCollected[$key])) {
                $this->sessionItemsCollected[$key]['quantity'] += $drop['quantity'];
            } else {
                $this->sessionItemsCollected[$key] = [
                    'name' => $drop['name'],
                    'rarity' => $drop['rarity'] ?? 'common',
                    'type' => $drop['type'],
                    'quantity' => $drop['quantity'],
                ];
            }
        }
    }

    #[On('level-up-modal-closed')]
    #[On('resume-auto-battle')]
    public function resumeAutoBattle(): void
    {
        if ($this->inLocationEvent) {
            // Awans po ukończeniu eventu lokacji - normalna eksploracja i tak jest
            // zablokowana dopóki gracz nie zamknie ekranu podsumowania (dismissEventRun()).
            return;
        }

        if (Auth::user()->game_stage > 12) {
            $this->autoChain = true;
            if ($this->battleCompleted || !$this->isPlaying) {
                $this->startBattle();
            }
        }
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

    public function getCurrentPlayerMana(): int
    {
        $char = $this->character ?? Auth::user()?->character;
        $maxMana = $char ? $char->getMaxMana() : 50;

        if (empty($this->visibleTurns)) {
            return $maxMana;
        }

        $lastTurn = end($this->visibleTurns);
        return $lastTurn['playerMana'] ?? $lastTurn['state']['playerMana'] ?? $maxMana;
    }

    public function getPlayerManaPercent(): float
    {
        $char = $this->character ?? Auth::user()?->character;
        $maxMana = $char ? $char->getMaxMana() : 50;

        return min(100, max(0, ($this->getCurrentPlayerMana() / max(1, $maxMana)) * 100));
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

    public function getCurrentState(): ?array
    {
        if (empty($this->visibleTurns)) return null;
        return end($this->visibleTurns)['state'] ?? null;
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

    private function initPlayerData(Character $character): void
    {
        $playerAttributes = $character->getTotalAttributes();
        $playerMaxHp = $character->getMaxHp();

        $this->player = [
            'name' => $character->name,
            'level' => $character->level,
            'avatar' => $character->getEffectiveAvatarUrl(),
            'maxHp' => $playerMaxHp,
            'hp' => $playerMaxHp,
            'stats' => $playerAttributes
        ];
    }

    public function getPlayerCombatStats(): array
    {
        $character = $this->character;
        $playerAttributes = $this->player['stats'] ?? $character->getTotalAttributes();
        $eqStats = $character->getEquipmentStats();
        $level = $character->level;
        $agi = $playerAttributes['agi'] ?? 0;
        $vit = $playerAttributes['vit'] ?? 0;

        $enemyAgi = $this->enemy['stats']['agi'] ?? 0;

        $weaponType = $character->getEquippedWeaponType();
        if ($weaponType === 'wand') {
            // Naprawiony bug (2026-08-05, follow-up rebalansu) - patrz identyczna
            // notatka w EncounterService::calculateDamage(). To tylko podgląd w UI,
            // ale bez naprawy pokazywał graczowi zaniżone, mylące obrażenia.
            $statBonus = $character->getAttributeAttackBonus('wand');
            $weaponAtkMin = (int) ($eqStats['magic_attack_min'] ?? 0);
            $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['magic_attack_max'] ?? 0);
        } elseif ($weaponType === 'bell') {
            $statBonus = $character->getAttributeAttackBonus('bell');
            $weaponAtkMin = (int) ($eqStats['attack_min'] ?? 0);
            $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['attack_max'] ?? 0);
        } else {
            $statBonus = $character->getAttributeAttackBonus($weaponType);
            $weaponAtkMin = (int) ($eqStats['attack_min'] ?? 0);
            $weaponAtkMax = (int) max($weaponAtkMin, $eqStats['attack_max'] ?? 0);
        }
        $baseDmg = 10 + $statBonus + ($level * 1);

        // Balanced Crit: Minimum 3% floor, max 100% cap (bez redukcji z AGI przeciwnika)
        $baseCrit = 5 + ($agi * 0.15) + ($eqStats['crit_chance'] ?? 0);
        $effectiveCrit = max(3.0, min(100.0, $baseCrit));

        // Balanced Dodge: Base 3% + AGI bonus + item dodge bonus (max 30% cap, bez redukcji z AGI przeciwnika)
        $itemDodge = (float)($eqStats['dodge_chance'] ?? 0);
        $effectiveDodge = min(30.0, max(0.0, 3.0 + ($agi * 0.06) + $itemDodge));

        return [
            'crit_chance' => round($effectiveCrit, 1),
            'dodge_chance' => round($effectiveDodge, 1),
            'atk_min' => $baseDmg + $weaponAtkMin,
            'atk_max' => $baseDmg + $weaponAtkMax,
            'defense' => $vit + (int)($level / 2) + ($eqStats['defense'] ?? 0),
        ];
    }

    public function getActiveMonsterIndex(): int
    {
        $monsters = $this->getCurrentMonstersState();

        // Helper: find first alive monster index
        $firstAlive = function() use ($monsters): int {
            foreach ($monsters as $idx => $m) {
                if (($m['hp'] ?? 0) > 0) return $idx;
            }
            return 0;
        };

        if (empty($this->visibleTurns)) {
            return $firstAlive();
        }

        $lastTurn = end($this->visibleTurns);

        // Prefer target_index (player attacked this monster) over enemy_index (monster attacked player)
        // because target_index tells us which monster the player is currently focusing
        foreach (['target_index', 'enemy_index'] as $key) {
            if (isset($lastTurn[$key])) {
                $idx = (int)$lastTurn[$key];
                // If that monster is already dead, fall back to first alive
                if (!empty($monsters[$idx]) && ($monsters[$idx]['hp'] ?? 0) > 0) {
                    return $idx;
                }
                return $firstAlive();
            }
        }

        return $firstAlive();
    }

    public function getActiveMonster(): array
    {
        $monsters = $this->getCurrentMonstersState();
        if (empty($monsters)) {
            return $this->enemy;
        }

        $idx = $this->getActiveMonsterIndex();
        return $monsters[$idx] ?? $monsters[0] ?? $this->enemy;
    }

    public function getEnemyCombatStats(): array
    {
        $activeEnemy = $this->isOverLevelCombat ? $this->getActiveMonster() : $this->enemy;
        if (empty($activeEnemy)) return [];

        $enemyStats = $activeEnemy['stats'] ?? [];
        $enemyAgi = $enemyStats['agi'] ?? 0;

        $baseCrit = 3 + ($enemyAgi * 0.3);
        $effectiveCrit = max(2.0, min(30.0, $baseCrit));

        $effectiveDodge = min(18.0, max(0.0, 3.0 + ($enemyAgi * 0.15)));

        return [
            'crit_chance' => round($effectiveCrit, 1),
            'dodge_chance' => round($effectiveDodge, 1),
            'atk' => $enemyStats['atk'] ?? 0,
            'def' => $enemyStats['def'] ?? 0,
        ];
    }

    public function render()
    {
        return view('livewire.adventure.map-stub');
    }
}
