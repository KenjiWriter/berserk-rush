<?php

namespace App\Application\Combat;

use App\Application\Shared\Result;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Application\Loot\DropService;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Infrastructure\Persistence\WorldBossInstance;
use App\Infrastructure\Persistence\WorldBossDamageLog;
use App\Application\Rankings\WeeklyRankingService;

class EncounterService
{
    private array $activeCooldowns = [];
    private array $activeDots = [];
    private $equippedSkills = null;
    private array $activeBuffs = [];

    // Rozszerzenie systemu skilli (2026-07-28): pasywne umiejętności (aura obrażeń,
    // szansa na dodatkowy atak) oraz licznik unieruchomienia potwora (freeze/stun)
    // w walkach 1 na 1. W walkach grupowych (over-level) unieruchomienie liczone jest
    // per-potwór wewnątrz tablicy $monsters (klucz 'cc_turns'), patrz simulateMultiCombat().
    private array $activePassives = [];
    private int $monsterCcTurns = 0;
    private int $playerMana = 0;
    private int $playerMaxMana = 0;

    // Skille potworów (Faza 2 rebalansu, 2026-08-05): lustrzane odbicie stanu, który do
    // tej pory istniał tylko dla gracza -> potwora. `$playerDots` to DoT-y NAŁOŻONE NA
    // GRACZA przez skill potwora (tykają na turze potwora), `$playerCcTurns` to
    // ogłuszenie/zamrożenie GRACZA (gracz traci turę - lustro `$monsterCcTurns`),
    // `$monsterSkillCooldowns` to odnowienia skilli potwora (klucz = indeks skilla w
    // Monster::getCombatSkills()). Cały ten stan jest resetowany na starcie każdej walki.
    private array $playerDots = [];
    private int $playerCcTurns = 0;
    private array $monsterSkillCooldowns = [];

    // Procki z ekwipunku (2026-07-29): 'poison_chance'/'stun_chance' na broni dają
    // szansę (%) na dołożenie efektu przy KAŻDYM wylądowanym trafieniu (nie tylko
    // przy użyciu skilla), niezależnie od DoT-ów/CC ze skilli. Potwory nie mają
    // odporności ekwipunkowej, więc w PvE efektywna szansa = surowa wartość z gearu.
    // Wartości dobrane niżej niż najsłabsze skille (bo to pasywny, darmowy proc, a nie
    // świadomie odpalona umiejętność) - patrz CombatSkillSeeder (poison 0.02-0.15,
    // stun/freeze zawsze 1 tura na niskich poziomach).
    private const EQUIPMENT_POISON_DURATION = 3;
    private const EQUIPMENT_POISON_VALUE = 0.03;
    private const EQUIPMENT_STUN_DURATION = 1;


    /**
     * Rzuca procki specjalności broni (otrucie, krwawienie, ogłuszenie, podwójny cios, infuzję magiczną) z ekwipunku po trafieniu.
     * $resistEq to statystyki ekwipunku CELU (puste dla potworów w PvE - nie mają odporności).
     */
    private function rollEquipmentProcs(array $eq, array $resistEq = []): array
    {
        $poisonChance = max(0, ($eq['poison_chance'] ?? 0) - ($resistEq['resist_poison'] ?? 0));
        $bleedChance = max(0, $eq['bleed_chance'] ?? 0);
        $stunChance = max(0, ($eq['stun_chance'] ?? 0) - ($resistEq['resist_stun'] ?? 0));
        $doubleStrikeChance = max(0, $eq['double_strike_chance'] ?? 0);
        $magicInfusionChance = max(0, $eq['magic_infusion_chance'] ?? 0);

        $dot = null;
        $cc = null;
        $doubleStrike = false;
        $infusionArmorPen = 0;

        if ($poisonChance > 0 && mt_rand(1, 100) <= $poisonChance) {
            $dot = [
                'type' => 'poison',
                'name' => 'Zatrucie (Ekwipunek)',
                'description' => 'Zadaje obrażenia od otrucia co turę (% od max HP).',
                'value' => self::EQUIPMENT_POISON_VALUE,
                'duration' => self::EQUIPMENT_POISON_DURATION,
            ];
        } elseif ($bleedChance > 0 && mt_rand(1, 100) <= $bleedChance) {
            $dot = [
                'type' => 'bleed',
                'name' => 'Krwawienie (Ekwipunek)',
                'description' => 'Zadaje obrażenia od krwawienia co turę (% od aktualnego HP).',
                'value' => self::EQUIPMENT_POISON_VALUE,
                'duration' => self::EQUIPMENT_POISON_DURATION,
            ];
        }

        if ($stunChance > 0 && mt_rand(1, 100) <= $stunChance) {
            $cc = ['type' => 'stun', 'duration' => self::EQUIPMENT_STUN_DURATION];
        }

        if ($doubleStrikeChance > 0 && mt_rand(1, 100) <= $doubleStrikeChance) {
            $doubleStrike = true;
        }

        if ($magicInfusionChance > 0 && mt_rand(1, 100) <= $magicInfusionChance) {
            $infusionRoll = mt_rand(1, 4);
            if ($infusionRoll === 1 && !$dot) {
                $dot = [
                    'type' => 'bleed',
                    'name' => 'Infuzja Krwawienia',
                    'description' => 'Magiczna infuzja wywołuje krwawienie (% od aktualnego HP).',
                    'value' => self::EQUIPMENT_POISON_VALUE,
                    'duration' => self::EQUIPMENT_POISON_DURATION,
                ];
            } elseif ($infusionRoll === 2 && !$dot) {
                $dot = [
                    'type' => 'poison',
                    'name' => 'Infuzja Otrucia',
                    'description' => 'Magiczna infuzja wywołuje otrucie (% od max HP).',
                    'value' => self::EQUIPMENT_POISON_VALUE,
                    'duration' => self::EQUIPMENT_POISON_DURATION,
                ];
            } elseif ($infusionRoll === 3) {
                $doubleStrike = true;
            } elseif ($infusionRoll === 4) {
                $infusionArmorPen = 50;
            }
        }

        return [
            'dot' => $dot,
            'cc' => $cc,
            'double_strike' => $doubleStrike,
            'infusion_armor_pen' => $infusionArmorPen,
        ];
    }

    public function start(Character $character, Map $map, ?Monster $forcedMonster = null, string $targetStrategy = 'random', ?int $initialMana = null): Result
    {
        Log::info('EncounterService::start called', [
            'character_id' => $character->id,
            'character_name' => $character->name,
            'map_id' => $map->id,
            'map_name' => $map->name,
            'forced_monster_id' => $forcedMonster?->id,
            'target_strategy' => $targetStrategy,
            'initial_mana' => $initialMana,
        ]);

        try {
            return DB::transaction(function () use ($character, $map, $forcedMonster, $targetStrategy, $initialMana) {
                // Lock character row to serialize concurrent battle requests
                $char = Character::where('id', $character->id)->lockForUpdate()->first();
                if (!$char) {
                    return Result::error('CHARACTER_NOT_FOUND', 'Nie znaleziono postaci.');
                }

                // Auto-claim rewards for any completed encounters that haven't been applied yet
                $unappliedEncounters = Encounter::where('character_id', $char->id)
                    ->where('rewards_applied', false)
                    ->whereIn('state', ['win', 'finished', 'lose'])
                    ->get();

                foreach ($unappliedEncounters as $unappliedEnc) {
                    $this->applyRewards($unappliedEnc);
                }

                // Clean up stale 'ongoing' encounters older than 10 seconds
                Encounter::where('character_id', $char->id)
                    ->where('state', 'ongoing')
                    ->where('started_at', '<', now()->subSeconds(10))
                    ->update(['state' => 'cancelled']);

                // Check for active ongoing encounter (state == 'ongoing' within last 10s)
                $activeOngoing = Encounter::where('character_id', $char->id)
                    ->where('state', 'ongoing')
                    ->where('started_at', '>=', now()->subSeconds(10))
                    ->exists();

                if ($activeOngoing) {
                    return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater jest już w trakcie innej walki!');
                }

                // Anti-cheat: minimalny odstęp między kolejnymi walkami (patrz config/anticheat.php).
                // Używamy Cache z microtime zamiast kolumny started_at, bo ta ma tylko
                // precyzję sekundową w Postgresie - za mało dla progu liczonego w ms.
                $rateLimitKey = "anticheat:last_encounter_start:{$char->id}";
                $minIntervalMs = config('anticheat.min_encounter_interval_ms', 350);
                $lastStartedAtMicro = Cache::get($rateLimitKey);

                if ($lastStartedAtMicro !== null) {
                    $elapsedMs = (microtime(true) - $lastStartedAtMicro) * 1000;
                    if ($elapsedMs < $minIntervalMs) {
                        return Result::error('TOO_FAST', 'Zwolnij! Odczekaj chwilę przed kolejną walką.');
                    }
                }

                Cache::put($rateLimitKey, microtime(true), now()->addSeconds(5));

                // Check for active/recent PvP encounter (< 5s ago or pending/calculating)
                $recentPvP = PvpEncounter::where('attacker_character_id', $char->id)
                    ->where(function ($query) {
                        $query->whereIn('state', ['pending', 'calculating'])
                              ->orWhere('created_at', '>=', now()->subSeconds(5));
                    })
                    ->exists();

                if ($recentPvP) {
                    return Result::error('COMBAT_IN_PROGRESS', 'Twój bohater bierze obecnie udział w pojedynku PvP.');
                }

                // Check map level requirement (bypassed if attacking a World Boss appropriate for character's level bracket)
                $isWorldBossFight = false;
                if ($forcedMonster) {
                    $activeBoss = WorldBossInstance::where('map_id', $map->id)
                        ->where('monster_id', $forcedMonster->id)
                        ->whereNotNull('level_bracket')
                        ->latest('id')
                        ->first();

                    if ($activeBoss && WorldBossService::bracketForLevel($char->level) === $activeBoss->level_bracket) {
                        $isWorldBossFight = true;
                    }
                }

                if (!$isWorldBossFight && !$map->isAccessibleBy($char)) {
                    return Result::error('LEVEL_OUT_OF_RANGE', "Twój poziom ({$char->level}) wykracza poza przedział tej mapy (wymagany poziom: {$map->level_range}).");
                }

                $isTutorial = ($char->user && $char->user->game_stage <= 12);
                $isOverLevel = $map->isOverLevel($char);

                // Tier scaling: player's natural tier vs map's tier
                $playerTier = $isTutorial ? 1 : $map->getPlayerTier($char);
                $tierDiff = $isTutorial ? 0 : $map->getTierDiff($char);
                $tierMultiplier = $isTutorial ? 1.0 : $map->getMonsterTierMultiplier($char);
                $tierLabel = $tierDiff > 0 ? "[T{$playerTier}]" : null;

                $monster = $forcedMonster;
                $monstersList = [];

                // Pula potworów mapy do losowych spotkań. Wyklucza potwory przypisane do
                // etapów lochów - patrz Map::explorationMonsters() (mają map_id ustawione
                // tylko po to, by spełnić constraint NOT NULL, nie oznacza to że mają
                // wypadać na zwykłej mapie - patrz DungeonSeeder).
                $mapMonsters = $map->explorationMonsters;

                if (!$monster) {
                    if ($isTutorial) {
                        // W samouczku zawsze losuj Wilka Leśnego
                        $monster = $mapMonsters->first(function ($m) {
                            return str_contains(mb_strtolower($m->name), 'wilk');
                        }) ?? $mapMonsters->sortBy('level')->first();
                    } else {
                        // Get monsters for this map. Rank 'worldboss' ma osobny, dedykowany
                        // mechanizm spotkania (WorldBossInstance + ?world_boss= w MapStub) i
                        // NIE może wypaść z normalnej puli eksploracji. Rank 'boss' to zwykły,
                        // zabijalny boss mapy (patrz MonsterLootSeeder) - nie ma dla niego żadnej
                        // innej ścieżki spotkania, więc musi zostać w puli losowej tak jak 'elite'.
                        $monstersPool = $mapMonsters->whereNotIn('rank', [
                            \App\Domain\Combat\Enums\MonsterRank::WORLDBOSS,
                        ]);

                        if ($monstersPool->isEmpty()) {
                            return Result::error('NO_MONSTERS', 'Brak zwykłych potworów na tej mapie');
                        }

                        if ($isOverLevel) {
                            // Boss wyłączony z puli walk grupowych (over-level) - pity timer
                            // (patrz BossPityService) dotyczy tylko normalnego starcia 1 na 1
                            // poniżej; nie ma sensu mieszać go z walką 3-4 potworów naraz.
                            $overLevelPool = $monstersPool->where('rank', '!=', \App\Domain\Combat\Enums\MonsterRank::BOSS);
                            if ($overLevelPool->isEmpty()) {
                                $overLevelPool = $monstersPool;
                            }

                            $monsterCount = mt_rand(3, 4);
                            $selectedCounts = [];
                            for ($i = 0; $i < $monsterCount; $i++) {
                                // Filter pool to monsters picked fewer than 2 times
                                $availablePool = $overLevelPool->filter(function($m) use ($selectedCounts) {
                                    return ($selectedCounts[$m->id] ?? 0) < 2;
                                });

                                if ($availablePool->isEmpty()) {
                                    $availablePool = $overLevelPool;
                                }

                                $mObj = $availablePool->random();
                                $selectedCounts[$mObj->id] = ($selectedCounts[$mObj->id] ?? 0) + 1;

                                $scaled = $mObj->getScaledStats($char->level, false);

                                // Apply tier multiplier to all stats
                                if ($tierMultiplier > 1.0) {
                                    foreach (['hp', 'atk', 'def', 'agi', 'int'] as $statKey) {
                                        if (isset($scaled[$statKey])) {
                                            $scaled[$statKey] = (int)ceil($scaled[$statKey] * $tierMultiplier);
                                        }
                                    }
                                }

                                $monstersList[] = [
                                    'id' => $mObj->id,
                                    'name' => $mObj->name,
                                    'level' => $mObj->level,
                                    'maxHp' => $scaled['hp'],
                                    'hp' => $scaled['hp'],
                                    'stats' => $scaled,
                                    'avatar' => $mObj->avatar,
                                    'rank' => is_object($mObj->rank) ? $mObj->rank->value : (string)$mObj->rank,
                                    'tier_label' => $tierLabel,
                                ];
                            }
                            $firstMonsterId = $monstersList[0]['id'];
                            $monster = $mapMonsters->find($firstMonsterId) ?? $monstersPool->random();
                        } else {
                            // Pity timer na bossa (ustalone z użytkownikiem, 2026-07-29): bazowo
                            // 3% szansy na wylosowanie bossa zamiast zwykłego potwora, +0.5% za
                            // każde zwycięstwo nad nie-bossem na TEJ mapie od ostatniego pojawienia
                            // się bossa (patrz BossPityService::recordNonBossVictory() w simulate()
                            // poniżej). Licznik resetuje się, gdy boss faktycznie się pojawi - patrz
                            // dalej w tej metodzie, zaraz po ustaleniu $monster.
                            $bossPool = $monstersPool->where('rank', \App\Domain\Combat\Enums\MonsterRank::BOSS);
                            $regularPool = $monstersPool->where('rank', '!=', \App\Domain\Combat\Enums\MonsterRank::BOSS);

                            if ($bossPool->isNotEmpty() && app(\App\Application\Combat\BossPityService::class)->rollBoss($char, $map->id)) {
                                $monster = $bossPool->random();
                            } else {
                                $monster = $regularPool->isNotEmpty() ? $regularPool->random() : $monstersPool->random();
                            }
                        }
                    }
                }

                // Reset pity timera w momencie faktycznego pojawienia się bossa - niezależnie
                // od tego, którą ścieżką $monster został ustalony (losowo, forced, itd.) i
                // niezależnie od wyniku samej walki.
                if (!$isTutorial && $monster && $monster->rank === \App\Domain\Combat\Enums\MonsterRank::BOSS) {
                    app(\App\Application\Combat\BossPityService::class)->recordBossAppeared($char, $map->id);
                }

                Log::info('Selected monster for encounter', [
                    'monster_id' => $monster->id,
                    'monster_name' => $monster->name,
                    'monster_level' => $monster->level,
                    'is_overlevel' => $isOverLevel,
                    'player_tier' => $playerTier,
                    'tier_diff' => $tierDiff,
                    'tier_multiplier' => $tierMultiplier,
                ]);

                // Check if this is an active world boss. whereNotNull('level_bracket') odsiewa
                // ewentualne osierocone rekordy sprzed rework'u przedziałów (level_bracket =
                // null, migracja go nie backfilluje) - patrz analogiczny komentarz w
                // MapStub::mount().
                $activeBoss = WorldBossInstance::where('map_id', $map->id)
                    ->where('monster_id', $monster->id)
                    ->whereNotNull('level_bracket')
                    ->latest('id')
                    ->first();

                if ($activeBoss) {
                    // Map::isAccessibleBy() celowo sprawdza tylko dolny próg poziomu (przekroczenie
                    // level_max na zwykłej mapie to "over-level" z karą, a nie blokada - patrz
                    // Map::isOverLevel()). World bossowie MUSZĄ jednak być twardo ograniczeni do
                    // swojego przedziału (inaczej wysoko-poziomowa postać mogłaby zdominować
                    // ranking niskiego przedziału), więc walidujemy to osobno, tutaj po stronie
                    // serwera (żeby nie dało się tego obejść pomijając UI/link z blade).
                    if (WorldBossService::bracketForLevel($character->level) !== $activeBoss->level_bracket) {
                        return Result::error('WRONG_LEVEL_BRACKET', 'Ten World Boss nie jest przeznaczony dla Twojego poziomu.');
                    }

                    // current_hp <= 0 = boss realnie pokonany przez społeczność w tej godzinie.
                    // Sprawdzamy to bezpośrednio po HP (bez osobnej flagi typu is_defeated), żeby
                    // stan nigdy nie mógł rozjechać się z rzeczywistym HP - dokładnie to było
                    // źródłem historycznego bugu "world bossa nie da się zabić" (patrz
                    // docs/modules/world_boss.md). Boss zostaje zablokowany aż do godzinowego
                    // resetu (WorldBossRewardJob), który i tak rozda nagrody i wylosuje nowego.
                    if ($activeBoss->current_hp <= 0) {
                        return Result::error('WORLD_BOSS_DEFEATED', 'Ten World Boss został już pokonany! Poczekaj na reset za pełną godzinę.');
                    }

                    $hasParticipated = WorldBossDamageLog::where('world_boss_instance_id', $activeBoss->id)
                        ->where('character_id', $character->id)
                        ->exists();

                    if ($hasParticipated) {
                        return Result::error('ALREADY_PARTICIPATED', 'Już walczyłeś z tym World Bossem!');
                    }
                }

                // Determine turn order using scaled stats (apply tier multiplier to single monster too)
                $rankVal = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
                $startSetType = ($isWorldBossFight || $rankVal === 'worldboss') ? \App\Infrastructure\Persistence\CharacterEquipmentSetItem::SET_WORLD_BOSS : null;
                $totalAttributes = $character->getTotalAttributes($startSetType);
                $playerAgi = $totalAttributes['agi'] ?? 0;
                $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);

                // Apply tier multiplier to single-monster stats
                if ($tierMultiplier > 1.0 && !$isOverLevel) {
                    foreach (['hp', 'atk', 'def', 'agi', 'int'] as $statKey) {
                        if (isset($scaledMonsterStats[$statKey])) {
                            $scaledMonsterStats[$statKey] = (int)ceil($scaledMonsterStats[$statKey] * $tierMultiplier);
                        }
                    }
                }

                $monsterAgi = $scaledMonsterStats['agi'];
                $playerFirst = $playerAgi >= $monsterAgi;

                // Create encounter
                $encounter = Encounter::create([
                    'character_id' => $character->id,
                    'map_id' => $map->id,
                    'monster_id' => $monster->id,
                    'state' => 'ongoing',
                    'gold_reward' => 0,
                    'xp_reward' => 0,
                    'player_first' => $playerFirst,
                    'turns' => [],
                    'combat_data' => [
                        'player_agi' => $playerAgi,
                        'monster_agi' => $monsterAgi,
                        'created_at' => now()->toISOString(),
                        'is_overlevel' => ($isOverLevel && !$forcedMonster && !$isTutorial),
                        'target_strategy' => $targetStrategy,
                        'monsters' => $monstersList,
                        'player_tier' => $playerTier,
                        'tier_diff' => $tierDiff,
                        'tier_multiplier' => $tierMultiplier,
                        'tier_label' => $tierLabel,
                        'initial_mana' => $initialMana,
                        // Pre-scaled stats for single-monster fights
                        'scaled_monster_stats' => (!$isOverLevel && $tierMultiplier > 1.0) ? $scaledMonsterStats : null,
                    ],
                    'rewards_applied' => false,
                    'started_at' => now(),
                ]);

                Log::info('Encounter created successfully', [
                    'encounter_id' => $encounter->id,
                    'character_id' => $character->id,
                    'map_id' => $map->id,
                    'monster_id' => $monster->id
                ]);

                return Result::ok($encounter);
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::start failed', [
                'character_id' => $character->id,
                'map_id' => $map->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Result::error('ENCOUNTER_START_FAILED', 'Nie udało się rozpocząć walki');
        }
    }

    public function simulate(Encounter $encounter): Result
    {
        Log::info('EncounterService::simulate called', [
            'encounter_id' => $encounter->id,
        ]);

        try {
            return DB::transaction(function () use ($encounter) {
                // Load relations
                $encounter->load(['character.user', 'character.guild', 'monster']);

                $character = $encounter->character;
                $monster = $encounter->monster;

                if (!$character || !$monster) {
                    return Result::error('MISSING_DATA', 'Brak danych do symulacji walki');
                }

                Log::info('Starting combat simulation', [
                    'character_name' => $character->name,
                    'character_level' => $character->level,
                    'monster_name' => $monster->name,
                    'monster_level' => $monster->level,
                ]);

                $isTutorial = ($character->user && $character->user->game_stage <= 12);

                // Use pre-scaled stats if tier multiplier was applied in start()
                $scaledStatOverride = $encounter->combat_data['scaled_monster_stats'] ?? null;
                $scaledStats = $scaledStatOverride ?? $monster->getScaledStats($character->level, $isTutorial);

                $rankVal = is_object($monster->rank) ? $monster->rank->value : (string)$monster->rank;
                $isWorldBoss = ($rankVal === 'worldboss');
                $setType = $isWorldBoss ? \App\Infrastructure\Persistence\CharacterEquipmentSetItem::SET_WORLD_BOSS : null;

                // Initialize HP
                $playerHp = $character->getMaxHp($setType);
                $monsterHp = $scaledStats['hp'];
                $playerMaxHp = $playerHp;
                $monsterMaxHp = $monsterHp;

                Log::info('Combat stats initialized', [
                    'player_hp' => $playerHp,
                    'monster_hp' => $monsterHp,
                ]);

                // Simulate combat
                $isOverLevel = $encounter->combat_data['is_overlevel'] ?? false;
                if ($isWorldBoss) {
                    $monsterMaxHp = 999999999;
                    $monsterHp = $monsterMaxHp;
                }

                $goldRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];
                $xpRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];

                $isGroupFight = $isOverLevel && !empty($encounter->combat_data['monsters']);
                $groupMonsterModels = null;

                if ($isGroupFight) {
                    $monstersData = $encounter->combat_data['monsters'];
                    $tactic = $encounter->combat_data['target_strategy'] ?? 'random';
                    $initialMana = $encounter->combat_data['initial_mana'] ?? null;
                    $turns = $this->simulateMultiCombat($character, $monstersData, $playerHp, $tactic, $playerMaxHp, $initialMana);

                    // Modele potworów grupy (do nagród, dropu i eventów bestiariusza per-sztuka
                    // poniżej) - zbierane raz i cache'owane po id, bo ta sama sztuka potwora
                    // może wystąpić w grupie do 2 razy (patrz selectedCounts w start()).
                    $groupMonsterModels = Monster::whereIn('id', collect($monstersData)->pluck('id')->unique())
                        ->get()
                        ->keyBy('id');

                    $lastTurn = end($turns);
                    $finalPlayerHp = $lastTurn ? ($lastTurn['playerHp'] ?? 0) : $playerHp;
                    $allDead = true;
                    if ($lastTurn && !empty($lastTurn['monsters_state'])) {
                        foreach ($lastTurn['monsters_state'] as $ms) {
                            if (($ms['hp'] ?? 0) > 0) {
                                $allDead = false;
                                break;
                            }
                        }
                    }

                    $winner = ($finalPlayerHp > 0 && $allDead) ? 'player' : 'enemy';

                    if ($winner === 'player') {
                        // Suma nagród za KAŻDEGO z 3-4 potworów faktycznie pokonanych w grupie
                        // (wcześniej liczono tylko jednego "reprezentanta" grupy, więc zabicie 3-4
                        // potworów naraz dawało nagrodę jak za jednego - patrz docs/modules/combat.md).
                        // Dopiero na zsumowanej nagrodzie stosowana jest kara -66% za over-level.
                        $goldRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];
                        $xpRewardData = ['base' => 0, 'bonus' => 0, 'total' => 0, 'multiplier' => 1.0];

                        foreach ($monstersData as $mData) {
                            $mModel = $groupMonsterModels->get($mData['id'] ?? null);
                            if (!$mModel) {
                                continue;
                            }

                            $g = $this->calculateGoldReward($mModel, $character);
                            $x = $this->calculateXpReward($mModel, $character);

                            $goldRewardData['base'] += $g['base'];
                            $goldRewardData['total'] += $g['total'];
                            $xpRewardData['base'] += $x['base'];
                            $xpRewardData['total'] += $x['total'];
                            $goldRewardData['multiplier'] = $g['multiplier'];
                            $xpRewardData['multiplier'] = $x['multiplier'];
                        }

                        // Apply Over-Level Penalty (-66% rewards, so 0.33x multiplier)
                        $goldRewardData['base'] = (int)ceil($goldRewardData['base'] * 0.33);
                        $goldRewardData['total'] = (int)ceil($goldRewardData['total'] * 0.33);
                        $goldRewardData['bonus'] = $goldRewardData['total'] - $goldRewardData['base'];

                        $xpRewardData['base'] = (int)ceil($xpRewardData['base'] * 0.33);
                        $xpRewardData['total'] = (int)ceil($xpRewardData['total'] * 0.33);
                        $xpRewardData['bonus'] = $xpRewardData['total'] - $xpRewardData['base'];
                    }
                } else {
                    $initialMana = $encounter->combat_data['initial_mana'] ?? null;
                    $turns = $this->simulateCombat($character, $monster, $playerHp, $monsterHp, $isWorldBoss, $playerMaxHp, $initialMana, $setType);
                    $lastTurn = end($turns);
                    $finalMonsterHp = $lastTurn ? $lastTurn['enemyHp'] : $monsterHp;

                    if ($isWorldBoss) {
                        $winner = 'enemy'; // Worldboss always wins/survives
                        // Cast do int: $finalMonsterHp może wyjść jako float (np. z procentowych
                        // efektów %HP naliczanych w trakcie tur), a "damage" w
                        // world_boss_damage_logs jest kolumną bigint - PostgreSQL (produkcja),
                        // w przeciwieństwie do MySQL, odrzuca wtedy insert błędem 22P02.
                        $damageDealt = (int) round(max(0, $monsterMaxHp - $finalMonsterHp));
                        
                        // Skalowanie nagród używając spłaszczonej krzywej (np. pierwiastek) by zapobiec nieskończonemu wzrostowi
                        $baseGold = max(10, (int)ceil(pow($damageDealt, 0.7)));
                        $baseXp = max(80, (int)ceil(pow($damageDealt, 0.75) * 4));

                        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
                        $goldMult = $multiplierService->getGoldMultiplier($character);
                        $xpMult = $multiplierService->getExpMultiplier($character);

                        $goldRewardData = ['base' => $baseGold, 'bonus' => (int)($baseGold * $goldMult) - $baseGold, 'total' => (int)($baseGold * $goldMult), 'multiplier' => $goldMult];
                        $xpRewardData = ['base' => $baseXp, 'bonus' => (int)($baseXp * $xpMult) - $baseXp, 'total' => (int)($baseXp * $xpMult), 'multiplier' => $xpMult];

                        // Zapisz log damage
                        $bracket = WorldBossService::bracketForLevel($character->level);
                        $activeBoss = WorldBossInstance::where('map_id', $encounter->map_id)
                            ->where('monster_id', $monster->id)
                            ->where('level_bracket', $bracket)
                            ->latest('id')
                            ->first();

                        if (!$activeBoss) {
                            $activeBoss = WorldBossInstance::create([
                                'map_id' => $encounter->map_id,
                                'monster_id' => $monster->id,
                                'level_bracket' => $bracket,
                                'total_hp' => $monster->stats['hp'] ?? 1000000,
                                'current_hp' => $monster->stats['hp'] ?? 1000000,
                            ]);
                        }

                        WorldBossDamageLog::create([
                            'world_boss_instance_id' => $activeBoss->id,
                            'character_id' => $character->id,
                            'damage' => $damageDealt
                        ]);

                        // Ranking tygodniowy: łączny DMG w World Bossy
                        app(WeeklyRankingService::class)->incrementScore(
                            $character->id,
                            'world_boss_damage',
                            (int) $damageDealt
                        );

                        // UWAGA (fix 2026-07-30, doprecyzowane przez użytkownika): world boss NIE
                        // regeneruje współdzielonej puli current_hp - żadna pojedyncza walka gracza
                        // nie mogłaby jej i tak przypadkiem wyzerować, bo $damageDealt jest liczone
                        // względem osobnej, fikcyjnej puli 999 999 999 (patrz $monsterMaxHp wyżej) -
                        // to ona, a nie regeneracja, chroni przed "przypadkowym zabiciem w swojej
                        // turze". current_hp to więc czysta, monotonicznie malejąca suma zadanych
                        // obrażeń całej społeczności - gdy spadnie do 0 (wystarczająco duży łączny
                        // lub pojedynczy dmg), boss zostaje zablokowany: EncounterService::start()
                        // odrzuca kolejne ataki (WORLD_BOSS_DEFEATED) aż do godzinowego resetu,
                        // patrz docs/modules/world_boss.md.
                        $newHp = max(0, $activeBoss->current_hp - $damageDealt);
                        $activeBoss->update(['current_hp' => $newHp]);
                    } else {
                        $winner = $finalMonsterHp <= 0 ? 'player' : 'enemy';

                        if ($winner === 'player') {
                            $goldRewardData = $this->calculateGoldReward($monster, $character);
                            $xpRewardData = $this->calculateXpReward($monster, $character);

                            // Pity timer na bossa: zwycięstwo nad NIE-bossem podnosi szansę na
                            // pojawienie się bossa przy kolejnym starciu na tej mapie (patrz
                            // BossPityService i wybór $monster w EncounterService::start()).
                            // Zwycięstwo nad samym bossem nic tu nie zmienia - licznik i tak
                            // został wyzerowany w start() w momencie jego pojawienia się.
                            if ($monster->rank !== \App\Domain\Combat\Enums\MonsterRank::BOSS) {
                                app(\App\Application\Combat\BossPityService::class)
                                    ->recordNonBossVictory($character, $encounter->map_id);
                            }
                        }
                    }
                }
                
                $goldReward = $goldRewardData['total'];
                $xpReward = $xpRewardData['total'];

                Log::info('Combat simulation completed', [
                    'turns_count' => count($turns),
                    'winner' => $winner,
                    'gold_reward' => $goldReward,
                    'xp_reward' => $xpReward,
                ]);

                // UŻYJ METOD Z MODELU zamiast bezpośredniego update()
                if ($winner === 'player') {
                    $encounter->markAsWon();
                    $dropService = app(DropService::class);
                    $dropResult = $dropService->rollLoot($encounter);

                    $questService = app(\App\Application\Quests\QuestService::class);

                    if ($isGroupFight) {
                        // Progres misji i eventy bestiariusza/osiągnięć MUSZĄ polecieć raz na
                        // KAŻDEGO faktycznie pokonanego potwora w grupie (3-4 sztuki), inaczej
                        // bestiariusz/achievementy liczą walkę grupową jak zabicie jednego potwora
                        // - patrz docs/modules/achievements.md i docs/modules/combat.md.
                        foreach ($monstersData as $mData) {
                            $mModel = $groupMonsterModels->get($mData['id'] ?? null);
                            if (!$mModel) {
                                continue;
                            }

                            $questService->progressQuest($character, 'hunting', ['monster' => (string)$mModel->id, 'map' => (string)$encounter->map_id]);
                            event(new \App\Domain\Collections\Events\MonsterDefeated($character, $mModel, $encounter->map_id));

                            // Ranking tygodniowy: potwory/bossowie map (walka grupowa)
                            $rankingCategory = ($mModel->rank === \App\Domain\Combat\Enums\MonsterRank::BOSS) ? 'map_bosses_killed' : 'monsters_killed';
                            app(WeeklyRankingService::class)->incrementScore($character->id, $rankingCategory);
                        }
                    } else {
                        // Update hunting quests
                        $questService->progressQuest($character, 'hunting', ['monster' => (string)$monster->id, 'map' => (string)$encounter->map_id]);

                        // Fire MonsterDefeated event for Bestiary and Achievements
                        event(new \App\Domain\Collections\Events\MonsterDefeated($character, $monster, $encounter->map_id));

                        // Ranking tygodniowy: potwory/bossowie map (walka pojedyncza)
                        $rankingCategory = ($monster->rank === \App\Domain\Combat\Enums\MonsterRank::BOSS) ? 'map_bosses_killed' : 'monsters_killed';
                        app(WeeklyRankingService::class)->incrementScore($character->id, $rankingCategory);
                    }

                    if ($dropResult->isError()) {
                        Log::warning('Failed to apply loot drops', [
                            'encounter_id' => $encounter->id,
                            'error' => $dropResult->getErrorMessage()
                        ]);
                    }
                } else {
                    if ($isWorldBoss) {
                        $encounter->update(['state' => 'finished']); // don't mark as lost for world boss since we give rewards
                    } else {
                        $encounter->markAsLost();
                    }
                }

                // Ustaw nagrody i inne dane
                $encounter->setRewards($goldReward, $xpReward);
                $encounter->setTurns($turns);

                // Zbieranie powiadomień wygenerowanych podczas walki (np. awanse misji, osiągnięcia)
                $notifications = app(\App\Application\Shared\NotificationTracker::class)->flush();

                // Update combat_data
                $combatData = array_merge($encounter->combat_data ?? [], [
                    'player_max_hp' => $playerMaxHp,
                    'monster_max_hp' => $monsterMaxHp,
                    'completed_at' => now()->toISOString(),
                    'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    'rewards' => [
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                    ],
                    'notifications' => $notifications
                ]);
                $encounter->combat_data = $combatData;
                $encounter->save();

                // Create result for UI
                $combatResultData = [
                    'player' => [
                        'name' => $character->name,
                        'level' => $character->level,
                        'hp' => $playerMaxHp,
                        'maxHp' => $playerMaxHp,
                        'stats' => [
                            'str' => $character->getTotalAttributes()['str'] ?? 0,
                            'int' => $character->getTotalAttributes()['int'] ?? 0,
                            'vit' => $character->getTotalAttributes()['vit'] ?? 0,
                            'agi' => $character->getTotalAttributes()['agi'] ?? 0,
                        ]
                    ],
                    'enemy' => [
                        'name' => $monster->name,
                        'level' => $monster->level,
                        'hp' => $monsterMaxHp,
                        'maxHp' => $monsterMaxHp,
                        'stats' => [
                            'atk' => $scaledStats['atk'],
                            'def' => $scaledStats['def'],
                            'agi' => $scaledStats['agi'],
                            'hp' => $scaledStats['hp'],
                        ]
                    ],
                    'turns' => $turns,
                    'result' => $isWorldBoss ? 'finished' : ($winner === 'player' ? 'win' : 'lose'),
                    'rewards' => [
                        'gold' => $goldReward,
                        'xp' => $xpReward,
                        'gold_data' => $goldRewardData,
                        'xp_data' => $xpRewardData,
                        'damage_dealt' => $isWorldBoss ? $damageDealt : null,
                    ],
                    'notifications' => $notifications
                ];

                return Result::ok($combatResultData);
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::simulate failed', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Result::error('SIMULATION_FAILED', 'Symulacja walki nie powiodła się: ' . $e->getMessage());
        }
    }

    public function applyRewards(Encounter $encounter): Result
    {
        try {
            return DB::transaction(function () use ($encounter) {
                $enc = Encounter::where('id', $encounter->id)->lockForUpdate()->first();
                if (!$enc) {
                    return Result::error('NOT_FOUND', 'Spotkanie nie istnieje');
                }

                if ($enc->rewards_applied) {
                    Log::info('Rewards already applied for encounter', ['encounter_id' => $enc->id]);
                    return Result::ok($enc);
                }

                if (in_array($enc->state, ['win', 'finished'])) {
                    $character = $enc->character;
                    if ($character) {
                        if ($enc->gold_reward > 0) {
                            $character->increment('gold', $enc->gold_reward);
                        }
                        if ($enc->xp_reward > 0) {
                            $character->increment('xp', $enc->xp_reward);
                        }

                        app(\App\Application\Characters\LevelUpService::class)->checkAndApply($character);

                        $dropService = app(DropService::class);
                        $dropService->applyLoot($enc);
                    }
                }

                $enc->markRewardsApplied();
                return Result::ok($enc->fresh());
            });
        } catch (\Exception $e) {
            Log::error('EncounterService::applyRewards failed', [
                'encounter_id' => $encounter->id,
                'error' => $e->getMessage(),
            ]);
            return Result::error('APPLY_REWARDS_FAILED', 'Nie udało się przyznać nagród z walki.');
        }
    }

    private function simulateCombat(Character $character, Monster $monster, int $playerHp, int $monsterHp, bool $isWorldBoss = false, int $playerMaxHp = 0, ?int $initialMana = null, ?string $setType = null): array
    {
        if ($isWorldBoss && !$setType) {
            $setType = \App\Infrastructure\Persistence\CharacterEquipmentSetItem::SET_WORLD_BOSS;
        }

        // Reset state for new combat
        $this->activeCooldowns = [];
        $this->activeDots = [];
        $this->activeBuffs = [];
        $this->activePassives = [];
        $this->monsterCcTurns = 0;
        $this->playerDots = [];
        $this->playerCcTurns = 0;
        // Skille potworów gotowe "o turę wcześniej" (jak skille gracza), ale nie od
        // razu na turze 1 - potwór nie powinien nukować z automatu w pierwszej akcji.
        $this->monsterSkillCooldowns = [];
        foreach ($monster->getCombatSkills() as $i => $mSkill) {
            $this->monsterSkillCooldowns[$i] = max(0, (int) $mSkill['cooldown'] - 1);
        }
        $this->equippedSkills = $character->resolveEffectiveSkills($setType);

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $this->activeCooldowns[$cs->id] = max(0, $cs->getCooldown() - 1); // ready slightly earlier
            }
        }

        $this->playerMaxMana = $character->getMaxMana($setType);
        $this->playerMana = $this->playerMaxMana;

        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);

        $playerAgi = $character->getTotalAttributes($setType)['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'];
        $playerFirst = $playerAgi >= $monsterAgi;

        $turns = [];
        $turnCount = 0;
        $maxTurns = $isWorldBoss ? 20 : 50;

        $monsterMaxHp = $monsterHp;

        while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
            $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            if ($isPlayerTurn) {
                // Regeneracja many na początku tury gracza w PvE (5% maxMana, min 5 MP)
                $this->playerMana = min($this->playerMaxMana, $this->playerMana + max(5, (int)ceil($this->playerMaxMana * 0.05)));

                // Player's turn: decrease skill cooldowns
                foreach ($this->activeCooldowns as $id => $cd) {
                    if ($cd > 0) $this->activeCooldowns[$id]--;
                }
                // Decrease active buffs duration
                foreach ($this->activeBuffs as $k => $b) {
                    $this->activeBuffs[$k]['duration']--;
                    if ($this->activeBuffs[$k]['duration'] <= 0) unset($this->activeBuffs[$k]);
                }

                // Gracz ogłuszony/zamrożony przez skill potwora (Faza 2) - traci turę
                // ataku, ale many/cooldowny/buffy tykają normalnie (lustro logiki
                // `monsterCcTurns`, patrz docs/modules/combat.md pkt 8).
                if ($this->playerCcTurns > 0) {
                    $this->playerCcTurns--;
                    $turn = [
                        'actor' => 'player',
                        'type' => 'crowd_controlled',
                        'value' => 0,
                        'crit' => false,
                        'playerHp' => $playerHp,
                        'enemyHp' => $monsterHp,
                    ];
                    $turn['playerMana'] = $this->playerMana;
                    $turn['playerMaxMana'] = $this->playerMaxMana;
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                        'playerMana' => $this->playerMana,
                        'playerMaxMana' => $this->playerMaxMana,
                    ];
                    $turns[] = $turn;
                    $turnCount++;
                    continue;
                }

                // Pobranie many i ocena efektów pasywnych na tę turę
                $this->evaluatePassivesForTurn($character);

                $turn = $this->playerAttack($character, $monster, $playerHp, $monsterHp, $monsterMaxHp, $isWorldBoss, $playerMaxHp, $setType);
                $playerHp = $turn['playerHp'];
                $monsterHp = $turn['enemyHp'];

                if (!empty($turn['cc_applied'])) {
                    $this->monsterCcTurns = max($this->monsterCcTurns, (int) $turn['cc_applied']['duration']);
                }

                $turn['playerMana'] = $this->playerMana;
                $turn['playerMaxMana'] = $this->playerMaxMana;
                $turn['state'] = [
                    'dots' => $this->activeDots,
                    'buffs' => $this->activeBuffs,
                    'cooldowns' => $this->activeCooldowns,
                    'playerMana' => $this->playerMana,
                    'playerMaxMana' => $this->playerMaxMana,
                ];
                $turns[] = $turn;
                $turnCount++;

                // Pasywna szansa na natychmiastowy dodatkowy atak (np. "Furia Berserkera" - topór)
                if ($monsterHp > 0 && $this->rollExtraAttack()) {
                    $bonusTurn = $this->playerAttack($character, $monster, $playerHp, $monsterHp, $monsterMaxHp, $isWorldBoss, $playerMaxHp, $setType);
                    $bonusTurn['extra_attack'] = true;
                    $playerHp = $bonusTurn['playerHp'];
                    $monsterHp = $bonusTurn['enemyHp'];

                    if (!empty($bonusTurn['cc_applied'])) {
                        $this->monsterCcTurns = max($this->monsterCcTurns, (int) $bonusTurn['cc_applied']['duration']);
                    }

                    $bonusTurn['playerMana'] = $this->playerMana;
                    $bonusTurn['playerMaxMana'] = $this->playerMaxMana;
                    $bonusTurn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                        'playerMana' => $this->playerMana,
                        'playerMaxMana' => $this->playerMaxMana,
                    ];
                    $turns[] = $bonusTurn;
                    $turnCount++;
                }

                continue;
            }

            // --- Tura potwora ---
            // Najpierw (Faza 2): DoT-y nałożone na gracza tykają, a cooldowny skilli
            // potwora maleją - niezależnie od tego, czy potwór jest CC'owany.
            $playerDotDmg = 0;
            $playerDotType = null;
            if (!empty($this->playerDots)) {
                [$playerHp, $playerDotDmg, $playerDotType] = $this->tickPlayerDots($playerHp, $playerMaxHp);
            }
            foreach ($this->monsterSkillCooldowns as $i => $cd) {
                if ($cd > 0) $this->monsterSkillCooldowns[$i]--;
            }

            if ($playerHp <= 0) {
                // Gracz zginął od samego DoT-a (przed akcją potwora).
                $turn = [
                    'actor' => 'enemy',
                    'type' => 'player_dot',
                    'value' => 0,
                    'crit' => false,
                    'playerHp' => 0,
                    'enemyHp' => $monsterHp,
                ];
            } elseif ($this->monsterCcTurns > 0) {
                // Potwór jest zamrożony/ogłuszony - traci turę ataku
                $this->monsterCcTurns--;
                $turn = [
                    'actor' => 'enemy',
                    'type' => 'crowd_controlled',
                    'value' => 0,
                    'crit' => false,
                    'playerHp' => $playerHp,
                    'enemyHp' => $monsterHp,
                ];
            } else {
                $chosenSkill = $this->chooseMonsterSkill($monster);
                if ($chosenSkill !== null) {
                    $turn = $this->monsterCastSkill($monster, $character, $playerHp, $monsterHp, $monsterMaxHp, $chosenSkill, $setType);
                } else {
                    $turn = $this->monsterAttack($monster, $character, $playerHp, $monsterHp, $setType);
                }
                $playerHp = $turn['playerHp'];
                $monsterHp = $turn['enemyHp'] ?? $monsterHp;
            }

            // Obrażenia od DoT-a gracza doliczone do tury potwora (playerHp już je
            // uwzględnia - tu tylko surfacing do UI/logu).
            if ($playerDotDmg > 0) {
                $turn['playerDotDamage'] = $playerDotDmg;
                $turn['playerDotType'] = $playerDotType;
            }

            $turn['playerMana'] = $this->playerMana;
            $turn['playerMaxMana'] = $this->playerMaxMana;
            $turn['state'] = [
                'dots' => $this->activeDots,
                'buffs' => $this->activeBuffs,
                'cooldowns' => $this->activeCooldowns,
                'playerDots' => array_values($this->playerDots),
                'playerCc' => $this->playerCcTurns,
                'playerMana' => $this->playerMana,
                'playerMaxMana' => $this->playerMaxMana,
            ];

            $turns[] = $turn;
            $turnCount++;
        }

        return $turns;
    }

    /**
     * Wylicza aktywne efekty pasywnych umiejętności (equip_slot 1-3, type = 'passive')
     * z wyposażonego decku na daną turę. Pobiera koszt MP co turę/wywołanie.
     * W przypadku braku MP, efekt pasywny w danej turze zostaje pominięty.
     */
    private function evaluatePassivesForTurn(Character $character): void
    {
        $this->activePassives = [];
        if (!$this->equippedSkills) {
            return;
        }

        $equippedWeaponType = $character->getEquippedWeaponType();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type !== 'passive') {
                continue;
            }

            $reqWep = $cs->skill->required_weapon_type;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                continue;
            }

            $manaCost = $cs->getManaCost();
            if ($this->playerMana < $manaCost) {
                continue; // Brak wystarczającej ilości MP do aktywacji pasywa w tej turze
            }

            $this->playerMana -= $manaCost;

            $effVal = $cs->getEffectiveValue();

            if ($cs->skill->effect_type === 'passive_aura_dmg') {
                $this->activePassives['aura_dmg'] = ($this->activePassives['aura_dmg'] ?? 0) + $effVal;
            } elseif ($cs->skill->effect_type === 'passive_extra_attack') {
                // Cap zabezpieczający przed absurdalnymi wartościami z panelu admina
                $chance = min(0.75, max(0, $effVal));
                $this->activePassives['extra_attack_chance'] = max($this->activePassives['extra_attack_chance'] ?? 0, $chance);
            }
        }
    }

    /**
     * Rzuca kością na pasywną szansę "dodatkowego ataku" (np. Furia Berserkera).
     * Wspólne dla walk 1 na 1 oraz walk grupowych.
     */
    private function rollExtraAttack(): bool
    {
        $chance = $this->activePassives['extra_attack_chance'] ?? 0;
        if ($chance <= 0) {
            return false;
        }

        return mt_rand(1, 10000) <= (int) round($chance * 10000);
    }

    private function playerAttack(Character $character, Monster $monster, int $playerHp, int $monsterHp, int $monsterMaxHp, bool $isWorldBoss = false, int $playerMaxHp = 0, ?string $setType = null): array
    {
        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        $equippedWeaponType = $character->getEquippedWeaponType($setType);
        $eq = $character->getEquipmentStats($setType);

        $usedSkill = null;

        // Try to use skill
        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active' && ($this->activeCooldowns[$cs->id] ?? 0) <= 0) {
                // Check weapon requirement
                $reqWep = $cs->skill->required_weapon_type;
                if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                    continue;
                }

                // Check mana requirement
                $manaCost = $cs->getManaCost();
                if ($this->playerMana < $manaCost) {
                    continue; // Not enough mana to use skill
                }

                $effVal = $cs->getEffectiveValue();

                // HEAL check: Nie używaj skilla leczącego na pełnym HP ani gdy brak przestrzeni na leczenie (max 15% overheal)
                if ($cs->skill->effect_type === 'heal') {
                    $healAmount = max(1, (int) round($playerMaxHp * $effVal));
                    $maxAllowedHp = min($playerMaxHp - 1, $playerMaxHp - $healAmount + (int) round($playerMaxHp * 0.15));
                    if ($playerHp > $maxAllowedHp) {
                        continue; // Gracz ma zbyt dużo HP, pomijamy ten skill leczący w tej turze
                    }
                }

                // Consume mana & set cooldown
                $this->playerMana -= $manaCost;
                $this->activeCooldowns[$cs->id] = $cs->getCooldown();
                
                if ($cs->skill->effect_type === 'poison' || $cs->skill->effect_type === 'fire') {
                    $this->activeDots[] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'description' => $cs->skill->description ?? ($cs->skill->effect_type === 'poison' ? 'Zadaje obrażenia od otrucia co turę.' : 'Zadaje obrażenia od ognia co turę.'),
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                } else if ($cs->skill->effect_type === 'buff_phys_dmg') {
                    $this->activeBuffs['phys_dmg'] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'description' => $cs->skill->description ?? ('Zwiększa obrażenia fizyczne o ' . round($effVal * 100) . '%.'),
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                } else if ($cs->skill->effect_type === 'buff_defense') {
                    $this->activeBuffs['defense'] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'description' => $cs->skill->description ?? ('Redukuje obrażenia przychodzące o ' . round($effVal * 100) . '%.'),
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                }

                $usedSkill = [
                    'skill' => $cs->skill,
                    'effVal' => $effVal,
                ];
                break;
            }
        }

        // Process active DoTs on monster during this exchange
        $dotDamage = 0;
        $dotType = null;
        $baseHpForDot = $monster->stats['hp'] ?? 10000;

        foreach ($this->activeDots as $k => $dot) {
            if ($dot['type'] === 'poison') {
                $targetHp = $isWorldBoss ? $baseHpForDot : $monsterHp;
                $dmg = (int)($targetHp * $dot['value']);
            } else if ($dot['type'] === 'fire') {
                $targetHp = $isWorldBoss ? $baseHpForDot : $monsterMaxHp;
                $dmg = (int)($targetHp * $dot['value']);
            } else {
                $dmg = 0;
            }

            if ($isWorldBoss) {
                // Cap DoT damage per tick on World Boss to prevent scaling exploit
                $playerDmgData = $this->calculateDamage($character, $monster);
                $maxDotCap = max(100, (int)($playerDmgData['total'] * 5));
                $dmg = min($dmg, $maxDotCap);
            }

            $dmg = max(1, $dmg);
            $dotDamage += $dmg;
            $dotType = $dot['type'];

            $this->activeDots[$k]['duration']--;
            if ($this->activeDots[$k]['duration'] <= 0) unset($this->activeDots[$k]);
        }

        if ($usedSkill) {
            $csSkill = $usedSkill['skill'];
            $effVal = $usedSkill['effVal'];

            // --- HEAL: umiejętność lecząca zastępuje atak tej tury - nie zadaje
            // obrażeń przeciwnikowi (poza już aktywnymi DoT-ami), leczy postać gracza
            // o % maksymalnego HP (base_value, skalowane scaling_value/poziom). ---
            if ($csSkill->effect_type === 'heal') {
                $healAmount = max(1, (int) round($playerMaxHp * $effVal));
                $newPlayerHp = min($playerMaxHp, $playerHp + $healAmount);
                $newMonsterHp = max(0, $monsterHp - $dotDamage);

                return [
                    'actor' => 'player',
                    'type' => 'skill_heal',
                    'skill_name' => $csSkill->name,
                    'effect_type' => $csSkill->effect_type,
                    'is_magic' => (bool) $csSkill->is_magic,
                    'value' => $healAmount,
                    'healAmount' => $healAmount,
                    'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                    'dotType' => $dotDamage > 0 ? $dotType : null,
                    'crit' => false,
                    'playerHp' => $newPlayerHp,
                    'enemyHp' => $newMonsterHp,
                ];
            }

            $skillMultiplier = 1.0;
            if (in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                $skillMultiplier = $effVal;
            }

            $damageData = $this->calculateDamage($character, $monster, (bool) $csSkill->is_magic, [], $setType);
            $damage = (int)($damageData['total'] * $skillMultiplier);
            $baseDamage = (int)($damageData['base'] * $skillMultiplier);
            $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
            $magicDamage = (int)(($damageData['magic'] ?? 0) * $skillMultiplier);

            // Active Buffs Application (bonus z aktywnego buffa + stała pasywna aura, np. Aura Miecza)
            $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
            if ($physBuffValue > 0) {
                $damage = (int)($damage * (1 + $physBuffValue));
                $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
            }

            $isCrit = $this->rollCritical($character, $monster, $setType);
            if ($isCrit) {
                $damage = (int)($damage * 1.5);
                $baseDamage = (int)($baseDamage * 1.5);
                $bonusDamage = (int)($bonusDamage * 1.5);
                $magicDamage = (int)($magicDamage * 1.5);
            }

            // Przełącznik obrażeń magicznych: skille oznaczone is_magic (Różdżka/Dzwon)
            // pokazują CAŁOŚĆ wyliczonych obrażeń jako magicDamage zamiast
            // baseDamage/bonusDamage. To wyłącznie reklasyfikacja do UI/logu walki -
            // mitygacja (obrona przeciwnika) liczona jest identycznie jak dla obrażeń
            // fizycznych, zgodnie z celowym uproszczeniem opisanym w combat.md (brak
            // osobnej "obrony magicznej").
            if ($csSkill->is_magic) {
                $magicDamage += $baseDamage + $bonusDamage;
                $baseDamage = 0;
                $bonusDamage = 0;
            }

            $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

            $result = [
                'actor' => 'player',
                'type' => 'skill',
                'skill_name' => $csSkill->name,
                'effect_type' => $csSkill->effect_type,
                'is_magic' => (bool) $csSkill->is_magic,
                'value' => $damage,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => $isCrit,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
                'baseDamage' => $baseDamage,
                'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
                'magicDamage' => $magicDamage > 0 ? $magicDamage : null,
            ];

            if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
                // Unieruchamia cel na base_duration tur (traci turę/tury ataku).
                // Duration nie skaluje się z poziomem skilla - podobnie jak inne
                // pola base_duration w systemie (dot/buff) - balans ustawiany wprost
                // per-skill w seederze/panelu admina.
                $result['cc_applied'] = [
                    'type' => $csSkill->effect_type,
                    'duration' => max(1, (int) $csSkill->base_duration),
                ];
            }

            $procs = $this->rollEquipmentProcs($eq);
            if ($procs['dot']) {
                $this->activeDots[] = $procs['dot'];
            }
            if ($procs['cc']) {
                $result['cc_applied'] = [
                    'type' => $procs['cc']['type'],
                    'duration' => max($result['cc_applied']['duration'] ?? 0, $procs['cc']['duration']),
                ];
            }

            return $result;
        }

        // Standard attack
        $procs = $this->rollEquipmentProcs($eq);
        $damageData = $this->calculateDamage($character, $monster, false, $procs, $setType);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $bonusDamage = $damageData['bonus'];
        $magicDamage = $damageData['magic'] ?? 0;

        // Apply global buffs + passive aura (np. Aura Miecza) to normal attacks
        $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
            $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
        }

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $playerAgi = $character->getTotalAttributes($setType)['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $isCrit = $this->rollCritical($character, $monster, $setType);
        $isMiss = $this->rollDodge($monsterAgi, $playerAgi);

        if ($isMiss) {
            $newMonsterHp = max(0, $monsterHp - $dotDamage);
            return [
                'actor' => 'player',
                'type' => 'miss',
                'value' => 0,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $bonusDamage = (int)($bonusDamage * 1.5);
            $magicDamage = (int)($magicDamage * 1.5);
        }

        $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

        $turn = [
            'actor' => 'player',
            'type' => 'hit',
            'value' => $damage,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
            'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => $isCrit,
            'playerHp' => $playerHp,
            'enemyHp' => $newMonsterHp,
        ];

        if ($bonusDamage > 0 || $magicDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['bonusDamage'] = $bonusDamage > 0 ? $bonusDamage : null;
            $turn['magicDamage'] = $magicDamage > 0 ? $magicDamage : null;
        }

        $procs = $this->rollEquipmentProcs($eq);
        if ($procs['dot']) {
            $this->activeDots[] = $procs['dot'];
        }
        if ($procs['cc']) {
            $turn['cc_applied'] = [
                'type' => $procs['cc']['type'],
                'duration' => max($turn['cc_applied']['duration'] ?? 0, $procs['cc']['duration']),
            ];
        }

        return $turn;
    }

    private function monsterAttack(Monster $monster, Character $character, int $playerHp, int $monsterHp, ?string $setType = null): array
    {
        $damageData = $this->calculateMonsterDamage($monster, $character, $setType);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $resistDamage = $damageData['resist'];

        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $playerAgi = $character->getTotalAttributes($setType)['agi'] ?? 0;
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $isCrit = $this->rollMonsterCritical($monster, $character);
        $playerItemDodge = (float)($character->getEquipmentStats($setType)['dodge_chance'] ?? 0);
        $isMiss = $this->rollDodge($playerAgi, $monsterAgi, $playerItemDodge);

        if ($isMiss) {
            return [
                'actor' => 'enemy',
                'type' => 'miss',
                'value' => 0,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $monsterHp,
            ];
        }

        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $resistDamage = (int)($resistDamage * 1.5);
        }

        // Redukcja obrażeń przychodzących z aktywnego buffa buff_defense (np. "Postawa Tarczy") -
        // capowana na 75%, ten sam wzorzec bezpieczeństwa co passive_extra_attack.
        $defenseBuffValue = min(0.75, max(0, $this->activeBuffs['defense']['value'] ?? 0));
        if ($defenseBuffValue > 0) {
            $damage = max(1, (int)($damage * (1 - $defenseBuffValue)));
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        $turn = [
            'actor' => 'enemy',
            'type' => 'hit',
            'value' => $damage,
            'crit' => $isCrit,
            'playerHp' => $newPlayerHp,
            'enemyHp' => $monsterHp,
        ];

        if ($resistDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['resistDamage'] = $resistDamage;
        }

        return $turn;
    }

    // ==== Skille potworów (Faza 2 rebalansu, 2026-08-05) ====

    /**
     * Tyka DoT-y nałożone na gracza przez skille potworów. Semantyka identyczna jak
     * DoT-y gracza na potworze: `poison` = % AKTUALNEGO HP gracza, `fire` = % MAX HP
     * gracza. Zwraca [nowe playerHp, sumaryczne obrażenia, typ ostatniego DoT-a].
     */
    private function tickPlayerDots(int $playerHp, int $playerMaxHp): array
    {
        $total = 0;
        $lastType = null;

        foreach ($this->playerDots as $k => $dot) {
            $base = ($dot['type'] ?? 'poison') === 'fire' ? $playerMaxHp : $playerHp;
            $dmg = max(1, (int) round($base * (float) ($dot['value'] ?? 0)));
            $total += $dmg;
            $lastType = $dot['type'] ?? 'poison';

            $this->playerDots[$k]['duration']--;
            if ($this->playerDots[$k]['duration'] <= 0) {
                unset($this->playerDots[$k]);
            }
        }

        $newHp = max(0, $playerHp - $total);
        return [$newHp, $total, $lastType];
    }

    /**
     * Wybiera gotowy skill potwora do rzucenia w tej turze: pierwszy skill z cooldownem
     * 0, którego rzut na `chance` (%) się powiódł. Zwraca indeks w Monster::getCombatSkills()
     * albo null (potwór wykonuje zwykły atak). AI celowo niedeterministyczne (chance).
     */
    private function chooseMonsterSkill(Monster $monster): ?int
    {
        foreach ($monster->getCombatSkills() as $i => $skill) {
            if (($this->monsterSkillCooldowns[$i] ?? 0) > 0) {
                continue;
            }
            if ($skill['chance'] <= 0) {
                continue;
            }
            if (mt_rand(1, 100) <= $skill['chance']) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Potwór rzuca wybrany skill (nakłada cooldown). Obsługiwane effect_type:
     *  - `heal`        - potwór leczy się o `value` % swojego max HP, nie atakuje.
     *  - `poison`/`fire` - nakłada DoT na gracza (`value` %/turę, `duration` tur), bez
     *                    bezpośrednich obrażeń tej tury.
     *  - `direct_dmg`  - wzmocniony cios (mnożnik `value`), tag `magicDamage` gdy is_magic (Mag).
     *  - `stun`/`freeze` - cios (mnożnik `value`, min 1x) + unieruchomienie gracza na `duration` tur.
     * Skille potwora - jak skille gracza w PvE - zawsze trafiają (bez rzutu na unik).
     */
    private function monsterCastSkill(Monster $monster, Character $character, int $playerHp, int $monsterHp, int $monsterMaxHp, int $skillIndex, ?string $setType = null): array
    {
        $skill = $monster->getCombatSkills()[$skillIndex];
        $this->monsterSkillCooldowns[$skillIndex] = (int) $skill['cooldown'];

        // HEAL: potwór leczy się, nie atakuje.
        if ($skill['effect_type'] === 'heal') {
            $healPct = $skill['value'] > 0 ? $skill['value'] : 0.15;
            $healAmount = max(1, (int) round($monsterMaxHp * $healPct));
            $newMonsterHp = min($monsterMaxHp, $monsterHp + $healAmount);
            return [
                'actor' => 'enemy',
                'type' => 'skill_heal',
                'effectType' => 'heal',
                'skillName' => $skill['name'],
                'value' => $healAmount,
                'heal' => $healAmount,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $newMonsterHp,
            ];
        }

        // POISON/FIRE: nakłada DoT na gracza, bez bezpośrednich obrażeń tej tury.
        if (in_array($skill['effect_type'], ['poison', 'fire'], true)) {
            $dotValue = $skill['value'] > 0 ? $skill['value'] : 0.05;
            $this->playerDots[] = [
                'type' => $skill['effect_type'],
                'name' => $skill['name'],
                'value' => $dotValue,
                'duration' => max(1, (int) $skill['duration']),
            ];
            return [
                'actor' => 'enemy',
                'type' => 'skill',
                'effectType' => $skill['effect_type'],
                'skillName' => $skill['name'],
                'value' => 0,
                'crit' => false,
                'playerHp' => $playerHp,
                'enemyHp' => $monsterHp,
            ];
        }

        // direct_dmg / stun / freeze: cios (potencjalnie magiczny) + ewentualne CC gracza.
        $damageData = $this->calculateMonsterDamage($monster, $character, $setType);
        $mult = $skill['value'] > 0 ? $skill['value'] : 1.0;
        $damage = max(1, (int) round($damageData['total'] * $mult));

        // buff_defense gracza wciąż redukuje obrażenia (jak w monsterAttack).
        $defenseBuffValue = min(0.75, max(0, $this->activeBuffs['defense']['value'] ?? 0));
        if ($defenseBuffValue > 0) {
            $damage = max(1, (int) ($damage * (1 - $defenseBuffValue)));
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        $turn = [
            'actor' => 'enemy',
            'type' => 'hit',
            'effectType' => $skill['effect_type'],
            'skillName' => $skill['name'],
            'value' => $damage,
            'crit' => false,
            'playerHp' => $newPlayerHp,
            'enemyHp' => $monsterHp,
        ];

        if ($skill['is_magic']) {
            $turn['magicDamage'] = $damage;
            $turn['isMagic'] = true;
        }

        if (in_array($skill['effect_type'], ['stun', 'freeze'], true)) {
            $ccDuration = max(1, (int) $skill['duration']);
            $this->playerCcTurns = max($this->playerCcTurns, $ccDuration);
            $turn['ccType'] = $skill['effect_type'];
            $turn['ccDuration'] = $ccDuration;
        }

        return $turn;
    }



    private function calculateDamage(Character $character, Monster $monster, bool $isMagicSkill = false, array $procs = [], ?string $setType = null): array
    {
        $weaponType = $character->getEquippedWeaponType($setType);
        $eq = $character->getEquipmentStats($setType);

        if ($isMagicSkill) {
            $statBonus = $character->getAttributeAttackBonus($weaponType, $setType);
            if ($weaponType === 'wand') {
                $weaponAtkMin = (int) ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $weaponAtkMin = ($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0);
            } else {
                $weaponAtkMin = ($eq['magic_attack_min'] ?? 0) > 0 ? ($eq['magic_attack_min'] ?? 0) : ($eq['attack_min'] ?? 0);
                $weaponAtkMax = ($eq['magic_attack_max'] ?? 0) > 0 ? ($eq['magic_attack_max'] ?? 0) : ($eq['attack_max'] ?? 0);
            }
            $baseDamageMin = max(1, (int) round($statBonus + $weaponAtkMin));
            $baseDamageMax = max($baseDamageMin, (int) round($statBonus + $weaponAtkMax));
        } else {
            // Standard basic auto-attack (lub atak fizyczny)
            if ($weaponType === 'wand') {
                // Naprawiony bug (2026-08-05, follow-up rebalansu): auto-atak różdżką
                // czytał fizyczny przelicznik ('sword', STR+AGI) i attack_min/max
                // zamiast właściwej formuły magicznej ('wand', INT*2) i
                // magic_attack_min/max - różdżki nigdy nie mają attack_min/max
                // (patrz ItemTemplateSeeder), więc auto-atak zadawał obrażenia
                // bliskie zeru za każdym razem, gdy skill był na cooldownie.
                $statBonus = $character->getAttributeAttackBonus('wand', $setType);
                $weaponAtkMin = (int) ($eq['magic_attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['magic_attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $statBonus = $character->getAttributeAttackBonus('bell', $setType);
                $weaponAtkMin = (int) (($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0));
                $weaponAtkMax = (int) max($weaponAtkMin, ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0));
            } else {
                $statBonus = $character->getAttributeAttackBonus($weaponType, $setType);
                $weaponAtkMin = (int) ($eq['attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['attack_max'] ?? 0);
            }
            $baseDamageMin = max(1, (int) round($statBonus + $weaponAtkMin));
            $baseDamageMax = max($baseDamageMin, (int) round($statBonus + $weaponAtkMax));
        }

        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }

        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $defense = $scaledMonsterStats['def'];

        // Przebicie pancerza (Łuk / Infuzja różdżki)
        $armorPenPct = min(85, max(0, ($eq['armor_pen_pct'] ?? 0) + ($procs['infusion_armor_pen'] ?? 0)));
        if ($armorPenPct > 0) {
            $defense = (int) round($defense * (1.0 - ($armorPenPct / 100.0)));
        }

        $baseDamage = max(1, $damage - ($defense * 0.2));
        $bonusDamage = 0;

        $bonusPercentage = ($eq['bonus_vs_monsters'] ?? 0) + ($eq['strong_vs_monsters'] ?? 0) + ($eq['bonus_vs_monster'] ?? 0);

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $bonusKey = 'strong_vs_' . $typeStr;
            $altBonusKey = 'bonus_vs_' . $typeStr;
            $pluralBonusKey = 'strong_vs_' . $typeStr . 's';

            $bonusPercentage += ($eq[$bonusKey] ?? 0) + ($eq[$altBonusKey] ?? 0) + ($eq[$pluralBonusKey] ?? 0);
        }

        if (isset($monster->rank)) {
            $rankStr = strtolower(is_object($monster->rank) ? $monster->rank->value : $monster->rank);
            if (in_array($rankStr, ['boss', 'worldboss'], true)) {
                $bonusPercentage += ($eq['strong_vs_bosses'] ?? 0) + ($eq['strong_vs_boss'] ?? 0);
            }
        }

        if ($bonusPercentage > 0) {
            $bonusDamage = (int)($baseDamage * ($bonusPercentage / 100));
        }

        $magicBurstDamage = 0;
        $magicBurstChance = $eq['magic_burst_chance'] ?? 0;
        if ($magicBurstChance > 0 && mt_rand(1, 100) <= $magicBurstChance) {
            $burstMin = $eq['magic_burst_min'] ?? 0;
            $burstMax = max($burstMin, $eq['magic_burst_max'] ?? 0);
            if ($burstMax > 0) {
                $rawBurst = mt_rand((int) $burstMin, (int) $burstMax);
                $magicBurstDamage = max(1, (int) round($rawBurst - ($defense * 0.2)));
            }
        }

        // Podwójny Cios (Miecz / Infuzja różdżki)
        $doubleStrikeDamage = 0;
        if (!empty($procs['double_strike'])) {
            $doubleStrikeDamage = max(1, (int) round(($baseDamage + $bonusDamage) * 0.5));
        }

        return [
            'base' => $baseDamage,
            'bonus' => $bonusDamage,
            'magic' => $magicBurstDamage,
            'double_strike' => $doubleStrikeDamage,
            'total' => $baseDamage + $bonusDamage + $magicBurstDamage + $doubleStrikeDamage,
        ];
    }

    private function calculateMonsterDamage(Monster $monster, Character $character, ?string $setType = null): array
    {
        $isTutorial = ($character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character->level, $isTutorial);
        $baseDamage = $scaledMonsterStats['atk'];
        $vitality = $character->getTotalAttributes($setType)['vit'] ?? 1;
        $eq = $character->getEquipmentStats($setType);
        $defense = $vitality + ($character->level / 2) + ($eq['defense'] ?? 0);

        $damage = max(1, $baseDamage - ($defense * 0.2));
        $resistDamage = 0;

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $resistKey = 'resist_' . $typeStr;
            $pluralResistKey = 'resist_' . $typeStr . 's';
            
            $resistPercentage = ($eq[$resistKey] ?? 0) + ($eq[$pluralResistKey] ?? 0);
            if ($resistPercentage > 0) {
                $resistDamage = (int)($damage * ($resistPercentage / 100));
            }
        }

        return [
            'base' => (int)$damage,
            'resist' => $resistDamage,
            'total' => max(1, (int)$damage - $resistDamage)
        ];
    }

    private function rollCritical(Character $character, Monster $monster, ?string $setType = null): bool
    {
        $agility = $character->getTotalAttributes($setType)['agi'] ?? 1;
        $eq = $character->getEquipmentStats($setType);

        $baseCrit = 0.05 + ($agility * 0.0015) + (($eq['crit_chance'] ?? 0) / 100);
        // Twardy cap szansy krytyka na 100% (1.00) oraz dolny próg 3% (0.03) - bez redukcji z AGI przeciwnika
        $critChance = max(0.03, min(1.00, $baseCrit));

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollMonsterCritical(Monster $monster, ?Character $character = null): bool
    {
        $isTutorial = ($character && $character->user && $character->user->game_stage <= 12);
        $scaledMonsterStats = $monster->getScaledStats($character ? $character->level : $monster->level, $isTutorial);
        $monsterAgi = $scaledMonsterStats['agi'] ?? 0;

        $baseCrit = 0.03 + ($monsterAgi * 0.003);
        // Bez redukcji z AGI gracza
        $critChance = max(0.02, min(0.30, $baseCrit));

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollDodge(int $defenderAgi, int $attackerAgi = 0, float $defenderItemDodge = 0.0): bool
    {
        // Szansa na unik bazuje bezpośrednio na AGI obrońcy + ekwipunek, bez redukcji z AGI atakującego
        $dodgeChance = max(0.00, min(0.30, 0.03 + ($defenderAgi * 0.0006) + ($defenderItemDodge / 100.0)));

        return mt_rand(1, 1000) <= (int)round($dodgeChance * 1000);
    }

    private function calculateGoldReward(Monster $monster, Character $character): array
    {
        $baseGold = 10 + ($monster->level * 2);
        $variation = mt_rand(80, 120) / 100;
        $gold = (int)($baseGold * $variation);

        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
        $multiplier = $multiplierService->getGoldMultiplier($character);

        $total = (int)round($gold * $multiplier);

        // Biżuteria (naszyjnik/pierścień) - szansa (%) na podwojenie zdobytego złota
        // z tej walki, patrz EnchantmentStrategy::$accessoryBonuses['double_gold_chance'].
        $doubleGoldChance = $character->getEquipmentStats()['double_gold_chance'] ?? 0;
        if ($doubleGoldChance > 0 && mt_rand(1, 100) <= $doubleGoldChance) {
            $total *= 2;
        }

        $bonus = $total - $gold;

        return [
            'base' => $gold,
            'bonus' => $bonus,
            'total' => $total,
            'multiplier' => $multiplier
        ];
    }

    private function calculateXpReward(Monster $monster, Character $character): array
    {
        $levelDiff = $monster->level - $character->level;
        $baseXp = 25 * pow($monster->level, 1.2) + 30;

        if ($levelDiff > 0) {
            $baseXp *= (1 + ($levelDiff * 0.1));
        } elseif ($levelDiff < -5) {
            $baseXp *= max(0.1, 1 + ($levelDiff * 0.05));
        }
        
        $baseXp = (int) round($baseXp);

        $multiplierService = app(\App\Application\Combat\RewardMultiplierService::class);
        $multiplier = $multiplierService->getExpMultiplier($character);

        $total = (int)round($baseXp * $multiplier);

        // Biżuteria - szansa (%) na podwojenie zdobytego expa z tej walki, patrz
        // EnchantmentStrategy::$accessoryBonuses['double_exp_chance'].
        $doubleExpChance = $character->getEquipmentStats()['double_exp_chance'] ?? 0;
        if ($doubleExpChance > 0 && mt_rand(1, 100) <= $doubleExpChance) {
            $total *= 2;
        }

        $bonus = $total - $baseXp;

        return [
            'base' => $baseXp,
            'bonus' => $bonus,
            'total' => $total,
            'multiplier' => $multiplier
        ];
    }

    /**
     * Znajduje pierwszy gotowy (cooldown <= 0), pasujący do broni skill obszarowy
     * (is_aoe = true) z wyposażonego decku. Używane wyłącznie w starciach grupowych
     * (simulateMultiCombat) - np. "Rozproszenie Strzał" (łuk), "Kule Ognia" (różdżka).
     */
    private function resolveAoeSkill(Character $character): ?\App\Infrastructure\Persistence\CharacterCombatSkill
    {
        $equippedWeaponType = $character->getEquippedWeaponType();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type !== 'active' || ($this->activeCooldowns[$cs->id] ?? 0) > 0) {
                continue;
            }
            if (!$cs->skill->is_aoe) {
                continue;
            }
            if (!in_array($cs->skill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                continue;
            }

            $reqWep = $cs->skill->required_weapon_type;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                continue;
            }

            $manaCost = $cs->getManaCost();
            if ($this->playerMana < $manaCost) {
                continue;
            }

            return $cs;
        }

        return null;
    }

    /**
     * Wylicza obrażenia skilla obszarowego wobec JEDNEGO z celów w grupie. Analogiczne
     * do gałęzi obrażeń w playerAttack(), ale bez ponownego wyboru/konsumpcji cooldownu
     * (skill jest konsumowany raz przez wywołującego dla całej salwy AOE) i bez DoT
     * (efekty obszarowe działają na obrażeniach bezpośrednich/CC, nie na DoT).
     */
    private function playerAttackAoeTarget(Character $character, CombatSkill $csSkill, float $effVal, Monster $monsterModel, int $monsterHp): array
    {
        $skillMultiplier = in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true) ? $effVal : 1.0;

        $damageData = $this->calculateDamage($character, $monsterModel, (bool) $csSkill->is_magic);
        $damage = (int)($damageData['total'] * $skillMultiplier);
        $baseDamage = (int)($damageData['base'] * $skillMultiplier);
        $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
        $magicDamage = (int)(($damageData['magic'] ?? 0) * $skillMultiplier);

        $physBuffValue = ($this->activeBuffs['phys_dmg']['value'] ?? 0) + ($this->activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
            $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
        }

        $isCrit = $this->rollCritical($character, $monsterModel);
        if ($isCrit) {
            $damage = (int)($damage * 1.5);
            $baseDamage = (int)($baseDamage * 1.5);
            $bonusDamage = (int)($bonusDamage * 1.5);
            $magicDamage = (int)($magicDamage * 1.5);
        }

        if ($csSkill->is_magic) {
            $magicDamage += $baseDamage + $bonusDamage;
            $baseDamage = 0;
            $bonusDamage = 0;
        }

        $newMonsterHp = max(0, $monsterHp - $damage);

        $turn = [
            'actor' => 'player',
            'type' => 'skill',
            'aoe' => true,
            'skill_name' => $csSkill->name,
            'effect_type' => $csSkill->effect_type,
            'is_magic' => (bool) $csSkill->is_magic,
            'value' => $damage,
            'crit' => $isCrit,
            'enemyHp' => $newMonsterHp,
            'baseDamage' => $baseDamage,
            'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
            'magicDamage' => $magicDamage > 0 ? $magicDamage : null,
        ];

        if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
            $turn['cc_applied'] = [
                'type' => $csSkill->effect_type,
                'duration' => max(1, (int) $csSkill->base_duration),
            ];
        }

        // Tylko procek ogłuszenia - bez otrucia, zgodnie z zasadą "AOE bez DoT" powyżej.
        $stunProc = $this->rollEquipmentProcs($character->getEquipmentStats())['cc'];
        if ($stunProc) {
            $turn['cc_applied'] = [
                'type' => $stunProc['type'],
                'duration' => max($turn['cc_applied']['duration'] ?? 0, $stunProc['duration']),
            ];
        }

        return $turn;
    }

    private function simulateMultiCombat(Character $character, array $monsters, int $playerHp, string $tactic = 'random', int $playerMaxHp = 0, ?int $initialMana = null): array
    {
        $this->activeCooldowns = [];
        $this->activeDots = [];
        $this->activeBuffs = [];
        $this->activePassives = [];
        $this->equippedSkills = \App\Infrastructure\Persistence\CharacterCombatSkill::with('skill')
            ->where('character_id', $character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();

        foreach ($this->equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $this->activeCooldowns[$cs->id] = max(0, $cs->getCooldown() - 1);
            }
        }

        $this->playerMaxMana = $character->getMaxMana();
        $this->playerMana = $this->playerMaxMana;

        if ($playerMaxHp <= 0) {
            $playerMaxHp = $playerHp;
        }

        // Znaczniki unieruchomienia (freeze/stun) per-potwór
        foreach ($monsters as &$initM) {
            $initM['cc_turns'] = $initM['cc_turns'] ?? 0;
        }
        unset($initM);

        $turns = [];
        $turnCount = 0;
        $maxTurns = 80;

        $extraMonsters = count($monsters) - 1;
        // Monster damage reduction: 10% per extra monster
        $monsterDmgMult = max(0.1, 1.0 - ($extraMonsters * 0.10));

        while ($playerHp > 0 && $this->anyMonsterAlive($monsters) && $turnCount < $maxTurns) {
            // 1. Living monsters attack sequentially (chyba że są unieruchomione)
            foreach ($monsters as $mIdx => &$m) {
                if ($m['hp'] <= 0 || $playerHp <= 0) {
                    continue;
                }

                if (($m['cc_turns'] ?? 0) > 0) {
                    $m['cc_turns']--;
                    $turn = [
                        'actor' => 'enemy',
                        'type' => 'crowd_controlled',
                        'value' => 0,
                        'crit' => false,
                        'playerHp' => $playerHp,
                        'enemyHp' => $m['hp'],
                        'enemy_index' => $mIdx,
                        'enemy_name' => $m['name'],
                    ];
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['playerMana'] = $this->playerMana;
                    $turn['playerMaxMana'] = $this->playerMaxMana;
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                        'playerMana' => $this->playerMana,
                        'playerMaxMana' => $this->playerMaxMana,
                    ];
                    $turns[] = $turn;
                    $turnCount++;
                    continue;
                }

                $monsterModel = Monster::find($m['id']);
                if (!$monsterModel) {
                    continue;
                }

                $turn = $this->monsterAttack($monsterModel, $character, $playerHp, $m['hp']);

                if ($turn['type'] === 'hit' && ($turn['value'] ?? 0) > 0) {
                    $turn['value'] = max(1, (int)round($turn['value'] * $monsterDmgMult));
                    if (isset($turn['baseDamage'])) {
                        $turn['baseDamage'] = max(1, (int)round($turn['baseDamage'] * $monsterDmgMult));
                    }
                }

                $playerHp = max(0, $playerHp - $turn['value']);
                $turn['playerHp'] = $playerHp;
                $turn['enemy_index'] = $mIdx;
                $turn['enemy_name'] = $m['name'];
                $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                $turn['playerMana'] = $this->playerMana;
                $turn['playerMaxMana'] = $this->playerMaxMana;
                $turn['state'] = [
                    'dots' => $this->activeDots,
                    'buffs' => $this->activeBuffs,
                    'cooldowns' => $this->activeCooldowns,
                    'playerMana' => $this->playerMana,
                    'playerMaxMana' => $this->playerMaxMana,
                ];

                $turns[] = $turn;
                $turnCount++;
            }
            unset($m);

            if ($playerHp <= 0 || !$this->anyMonsterAlive($monsters)) {
                break;
            }

            // 2. Player attacks: skill obszarowy bije wszystkich, w innym wypadku 1 cel wg taktyki
            foreach ($this->activeCooldowns as $id => $cd) {
                if ($cd > 0) $this->activeCooldowns[$id]--;
            }
            foreach ($this->activeBuffs as $k => $b) {
                $this->activeBuffs[$k]['duration']--;
                if ($this->activeBuffs[$k]['duration'] <= 0) unset($this->activeBuffs[$k]);
            }

            // Pobranie many i ocena efektów pasywnych na tę turę
            $this->evaluatePassivesForTurn($character);

            $aoeSkillCs = $this->resolveAoeSkill($character);

            if ($aoeSkillCs) {
                $this->playerMana -= $aoeSkillCs->getManaCost();
                $this->activeCooldowns[$aoeSkillCs->id] = $aoeSkillCs->getCooldown();
                $effVal = $aoeSkillCs->getEffectiveValue();

                foreach ($monsters as $mIdx => &$m) {
                    if (($m['hp'] ?? 0) <= 0) {
                        continue;
                    }
                    $monsterModel = Monster::find($m['id']);
                    if (!$monsterModel) {
                        continue;
                    }

                    $turn = $this->playerAttackAoeTarget($character, $aoeSkillCs->skill, $effVal, $monsterModel, $m['hp']);
                    $m['hp'] = $turn['enemyHp'];

                    if (!empty($turn['cc_applied'])) {
                        $m['cc_turns'] = max($m['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
                    }

                    $turn['target_index'] = $mIdx;
                    $turn['target_name'] = $m['name'];
                    $turn['playerHp'] = $playerHp;
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['playerMana'] = $this->playerMana;
                    $turn['playerMaxMana'] = $this->playerMaxMana;
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                        'playerMana' => $this->playerMana,
                        'playerMaxMana' => $this->playerMaxMana,
                    ];

                    $turns[] = $turn;
                    $turnCount++;
                }
                unset($m);
            } else {
                $targetIdx = $this->selectTargetMonster($monsters, $tactic);
                if ($targetIdx === null) {
                    break;
                }

                $targetMonster = &$monsters[$targetIdx];
                $monsterModel = Monster::find($targetMonster['id']);
                if ($monsterModel) {
                    $turn = $this->playerAttack($character, $monsterModel, $playerHp, $targetMonster['hp'], $targetMonster['maxHp'], false, $playerMaxHp);
                    $playerHp = $turn['playerHp'];
                    $targetMonster['hp'] = $turn['enemyHp'];

                    if (!empty($turn['cc_applied'])) {
                        $targetMonster['cc_turns'] = max($targetMonster['cc_turns'] ?? 0, (int) $turn['cc_applied']['duration']);
                    }

                    $turn['target_index'] = $targetIdx;
                    $turn['target_name'] = $targetMonster['name'];
                    $turn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                    $turn['playerMana'] = $this->playerMana;
                    $turn['playerMaxMana'] = $this->playerMaxMana;
                    $turn['state'] = [
                        'dots' => $this->activeDots,
                        'buffs' => $this->activeBuffs,
                        'cooldowns' => $this->activeCooldowns,
                        'playerMana' => $this->playerMana,
                        'playerMaxMana' => $this->playerMaxMana,
                    ];

                    $turns[] = $turn;
                    $turnCount++;
                }
                unset($targetMonster);
            }

            // 3. Pasywna szansa na natychmiastowy dodatkowy atak (np. Furia Berserkera)
            if ($playerHp > 0 && $this->anyMonsterAlive($monsters) && $this->rollExtraAttack()) {
                $bonusIdx = $this->selectTargetMonster($monsters, $tactic);
                if ($bonusIdx !== null) {
                    $bonusTarget = &$monsters[$bonusIdx];
                    $bonusMonsterModel = Monster::find($bonusTarget['id']);
                    if ($bonusMonsterModel) {
                        $bonusTurn = $this->playerAttack($character, $bonusMonsterModel, $playerHp, $bonusTarget['hp'], $bonusTarget['maxHp'], false, $playerMaxHp);
                        $bonusTurn['extra_attack'] = true;
                        $playerHp = $bonusTurn['playerHp'];
                        $bonusTarget['hp'] = $bonusTurn['enemyHp'];

                        if (!empty($bonusTurn['cc_applied'])) {
                            $bonusTarget['cc_turns'] = max($bonusTarget['cc_turns'] ?? 0, (int) $bonusTurn['cc_applied']['duration']);
                        }

                        $bonusTurn['target_index'] = $bonusIdx;
                        $bonusTurn['target_name'] = $bonusTarget['name'];
                        $bonusTurn['monsters_state'] = $this->getMonstersStateSummary($monsters);
                        $bonusTurn['state'] = [
                            'dots' => $this->activeDots,
                            'buffs' => $this->activeBuffs,
                            'cooldowns' => $this->activeCooldowns,
                        ];

                        $turns[] = $bonusTurn;
                        $turnCount++;
                    }
                    unset($bonusTarget);
                }
            }
        }

        return $turns;
    }

    private function anyMonsterAlive(array $monsters): bool
    {
        foreach ($monsters as $m) {
            if (($m['hp'] ?? 0) > 0) return true;
        }
        return false;
    }

    private function getMonstersStateSummary(array $monsters): array
    {
        return array_map(function($m) {
            return [
                'id' => $m['id'],
                'name' => $m['name'],
                'hp' => $m['hp'],
                'maxHp' => $m['maxHp'],
                'level' => $m['level'],
                'stats' => $m['stats'] ?? [],
                'avatar' => $m['avatar'] ?? null,
                'rank' => $m['rank'] ?? 'regular',
                'tier_label' => $m['tier_label'] ?? null,
                'cc_turns' => $m['cc_turns'] ?? 0,
            ];
        }, $monsters);
    }

    private function selectTargetMonster(array $monsters, string $tactic): ?int
    {
        $aliveIndices = [];
        foreach ($monsters as $idx => $m) {
            if (($m['hp'] ?? 0) > 0) {
                $aliveIndices[] = $idx;
            }
        }

        if (empty($aliveIndices)) {
            return null;
        }

        if ($tactic === 'highest_hp') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'lowest_hp') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                return $monsters[$a]['hp'] <=> $monsters[$b]['hp'];
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'highest_att') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                $atkA = $monsters[$a]['stats']['atk'] ?? 0;
                $atkB = $monsters[$b]['stats']['atk'] ?? 0;
                if ($atkA === $atkB) {
                    return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
                }
                return $atkB <=> $atkA;
            });
            return $aliveIndices[0];
        }

        if ($tactic === 'highest_def') {
            usort($aliveIndices, function($a, $b) use ($monsters) {
                $defA = $monsters[$a]['stats']['def'] ?? 0;
                $defB = $monsters[$b]['stats']['def'] ?? 0;
                if ($defA === $defB) {
                    return $monsters[$b]['hp'] <=> $monsters[$a]['hp'];
                }
                return $defB <=> $defA;
            });
            return $aliveIndices[0];
        }

        // Default 'random'
        return $aliveIndices[array_rand($aliveIndices)];
    }
}
