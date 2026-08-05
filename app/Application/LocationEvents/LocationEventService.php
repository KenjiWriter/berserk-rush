<?php

namespace App\Application\LocationEvents;

use App\Application\Shared\Result;
use App\Application\Combat\RewardMultiplierService;
use App\Application\Rankings\WeeklyRankingService;
use App\Application\Loot\WeightedPicker;
use App\Infrastructure\RNG\RandomProvider;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LocationEvent;
use App\Infrastructure\Persistence\LocationEventUpgradeLevel;
use App\Infrastructure\Persistence\CharacterLocationEventRun;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Domain\Combat\Enums\MonsterRank;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Silnik eventów lokacji: rzadkie, tematyczne łańcuchy 2-10 potworów (ostatni to
 * boss), wylosowane podczas eksploracji mapy (patrz docs/modules/location_events.md).
 * Struktura serwisu jest świadomym mirrorem `App\Application\Dungeon\DungeonService`
 * (własny, w pełni inline silnik walki - bez tworzenia rekordów `Encounter`, HP/mana
 * przenoszone w rekordzie runu, async symulacja przez Job), zgodnie z konwencją
 * "parytetu silników" opisaną w docs/modules/combat.md pkt 9 - tak jak
 * EncounterService/PvPEncounterService/GuildWarService/DungeonService, ten silnik
 * celowo duplikuje logikę walki zamiast współdzielić ją przez wspólną abstrakcję.
 */
class LocationEventService
{
    private const EQUIPMENT_POISON_DURATION = 3;
    private const EQUIPMENT_POISON_VALUE = 0.03;
    private const EQUIPMENT_STUN_DURATION = 1;

    /**
     * Bonus nagród (gold/exp/drop) trybu hardcore względem trybu normalnego tej samej
     * rangi/poziomu ulepszenia. Wartość domyślna (+50%) - świadomie przestrajalna
     * stała, analogicznie do XP_CURVE_MULTIPLIER w LevelUpService, patrz
     * docs/rebalance_2026_08_progress.md.
     */
    private const HARDCORE_REWARD_BONUS_MULTIPLIER = 1.5;

    /** Mapa nazw lokacji -> tematyczna skrzynia (1:1 z LootChestSeeder). */
    private const MAP_CHEST_NAMES = [
        'Mroczny Las' => 'Skrzynia Mrocznego Lasu',
        'Stare Ruiny' => 'Skrzynia Starych Ruin',
        'Jaskinia Trolli' => 'Skrzynia Jaskini Trolli',
        'Pustkowia Orków' => 'Skrzynia Pustkowi Orków',
        'Bagna Grozy' => 'Skrzynia Bagien Grozy',
        'Góry Cienia' => 'Skrzynia Gór Cienia',
        'Wieża Magów' => 'Skrzynia Wieży Magów',
        'Skażone Miasto' => 'Skrzynia Skażonego Miasta',
    ];

    /**
     * Losuje, czy podczas eksploracji danej mapy wyzwala się event lokacji, a jeśli
     * tak - jaka ranga (T1-T6) i jaki poziom ulepszenia. Wywoływane z eksploracji
     * PRZED normalnym EncounterService::start().
     *
     * @return array{event: LocationEvent, upgrade_level: LocationEventUpgradeLevel}|null
     */
    public function rollEventTrigger(): ?array
    {
        $events = LocationEvent::orderBy('rank')->get();
        if ($events->isEmpty()) {
            return null;
        }

        $roll = mt_rand(1, 10000); // precyzja 0.01%
        $cumulative = 0;
        $chosenEvent = null;

        foreach ($events as $event) {
            $cumulative += (int) round($event->spawn_chance_pct * 100);
            if ($roll <= $cumulative) {
                $chosenEvent = $event;
                break;
            }
        }

        if (!$chosenEvent) {
            return null; // pozostała pula % - brak eventu, normalna eksploracja
        }

        return [
            'event' => $chosenEvent,
            'upgrade_level' => $this->rollUpgradeLevel(),
        ];
    }

    private function rollUpgradeLevel(): LocationEventUpgradeLevel
    {
        $levels = LocationEventUpgradeLevel::orderBy('level')->get();
        $roll = mt_rand(1, 10000);
        $cumulative = 0;

        foreach ($levels as $level) {
            $cumulative += (int) round($level->roll_chance_pct * 100);
            if ($roll <= $cumulative) {
                return $level;
            }
        }

        return $levels->first();
    }

    /**
     * Rozpoczyna nowy run eventu lokacji. Wymaga wcześniejszego rzutu przez
     * rollEventTrigger() oraz wyboru trybu (normalny/hardcore) przez gracza.
     */
    public function startRun(Character $character, Map $map, LocationEvent $event, LocationEventUpgradeLevel $upgradeLevel, bool $isHardcore): Result
    {
        $activeRun = CharacterLocationEventRun::where('character_id', $character->id)
            ->where('is_completed', false)
            ->where('is_failed', false)
            ->first();

        if ($activeRun) {
            return Result::error('ACTIVE_RUN', 'Masz już aktywny event lokacji.');
        }

        $explorationMonsters = $map->explorationMonsters()->get();
        if ($explorationMonsters->isEmpty()) {
            return Result::error('NO_MONSTERS', 'Brak potworów na tej mapie do rozegrania eventu.');
        }

        $bossPool = $explorationMonsters->where('rank', MonsterRank::BOSS);
        $regularPool = $explorationMonsters->where('rank', '!=', MonsterRank::BOSS);
        if ($regularPool->isEmpty()) {
            $regularPool = $explorationMonsters;
        }

        $bossMonster = $bossPool->isNotEmpty()
            ? $bossPool->random()
            : $regularPool->sortByDesc(fn ($m) => $m->stats['hp'] ?? 0)->first();

        $minCount = max(1, $event->monster_count_min + $upgradeLevel->monster_count_delta_min);
        $maxCount = max($minCount, $event->monster_count_max + $upgradeLevel->monster_count_delta_max);
        $totalMonsters = mt_rand($minCount, $maxCount);

        $groupChancePct = $event->group_chance_pct + $upgradeLevel->bonus_group_chance_pct;

        $monstersQueue = [];
        for ($i = 0; $i < $totalMonsters; $i++) {
            $isBossSlot = ($i === $totalMonsters - 1);
            $monster = $isBossSlot ? $bossMonster : $regularPool->random();

            $isGroup = false;
            $groupSize = 1;
            if (!$isBossSlot && $event->group_max_size > 0 && mt_rand(1, 10000) <= $groupChancePct * 100) {
                $isGroup = true;
                $groupSize = mt_rand(2, max(2, $event->group_max_size));
            }

            $monstersQueue[] = [
                'monster_id' => $monster->id,
                'is_boss' => $isBossSlot,
                'is_group' => $isGroup,
                'group_size' => $groupSize,
            ];
        }

        $run = CharacterLocationEventRun::create([
            'character_id' => $character->id,
            'map_id' => $map->id,
            'location_event_id' => $event->id,
            'upgrade_level' => $upgradeLevel->level,
            'is_hardcore' => $isHardcore,
            'monsters_queue' => $monstersQueue,
            'current_monster_index' => 0,
            'total_monsters' => $totalMonsters,
            'current_hp' => $character->getMaxHp(),
            'current_mana' => $character->getMaxMana(),
            'is_completed' => false,
            'is_failed' => false,
        ]);

        return Result::ok($run);
    }

    /**
     * Zleca (asynchronicznie) symulację walki z aktualnym slotem w kolejce.
     */
    public function fightNextMonster(CharacterLocationEventRun $run): Result
    {
        if ($run->is_completed || $run->is_failed) {
            return Result::error('RUN_ENDED', 'Ten event się już zakończył.');
        }

        if ($run->combat_state === 'calculating') {
            return Result::error('ALREADY_CALCULATING', 'Walka jest już w trakcie obliczania.');
        }

        $run->combat_state = 'calculating';
        $run->combat_data = null;
        $run->save();

        dispatch(new \App\Jobs\SimulateLocationEventStageJob($run->id));

        return Result::ok(['status' => 'calculating']);
    }

    /**
     * Wewnętrzna symulacja walki ze slotem, wywoływana przez SimulateLocationEventStageJob.
     */
    public function simulateStage(CharacterLocationEventRun $run): Result
    {
        $slot = $run->getCurrentSlot();
        if (!$slot) {
            return Result::error('NO_SLOT', 'Nie znaleziono kolejnego przeciwnika w evencie.');
        }

        $character = $run->character;
        $monster = Monster::find($slot['monster_id']);
        $event = $run->locationEvent;
        $upgradeLevel = LocationEventUpgradeLevel::where('level', $run->upgrade_level)->first();

        if (!$character || !$monster || !$event || !$upgradeLevel) {
            return Result::error('MISSING_DATA', 'Brak danych do walki.');
        }

        $equippedSkills = CharacterCombatSkill::with('skill')
            ->where('character_id', $character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();

        $activeCooldowns = [];
        foreach ($equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $activeCooldowns[$cs->id] = max(0, $cs->getCooldown() - 1);
            }
        }
        $activeDots = [];
        $activeBuffs = [];
        $activePassives = [];
        $monsterCcTurns = 0;

        // HP/mana startowe tej walki: w evencie normalnym resetujemy do pełna
        // (jak zwykła eksploracja), w hardcore przenosimy 1:1 z poprzedniej walki w
        // łańcuchu (brak regeneracji poza skillami/miksturami) - patrz
        // docs/modules/location_events.md, sekcja "Tryb Hardcore".
        $playerMaxHp = $character->getMaxHp();
        $playerHp = $run->is_hardcore ? $run->current_hp : $playerMaxHp;
        $startPlayerHp = $playerHp;

        $playerMaxMana = $character->getMaxMana();
        $playerMana = $playerMaxMana; // mana zawsze pełna na start walki, jak w EncounterService/DungeonService

        $atkMultiplier = $event->attack_multiplier * $upgradeLevel->attack_multiplier;
        $hpMultiplier = $upgradeLevel->hp_multiplier;

        $scaledMonster = $this->getScaledMonsterStats($monster, $character, $atkMultiplier, $hpMultiplier);
        $maxTurns = 50;

        $turns = [];
        $turnCount = 0;

        // Dane do wyświetlenia w UI (Faza 2) - nie wpływają na symulację, dokładane do
        // wszystkich 3 gałęzi zwracanego payloadu poniżej.
        $monsterDisplay = [
            'name' => $monster->name,
            'avatar' => $monster->avatar,
            'level' => $monster->level,
            'is_group' => (bool) $slot['is_group'],
            'group_size' => (int) $slot['group_size'],
        ];
        $monsterMaxHpDisplay = $slot['is_group']
            ? $scaledMonster['hp'] * max(2, (int) $slot['group_size'])
            : $scaledMonster['hp'];

        if ($slot['is_group']) {
            $groupSize = max(2, (int) $slot['group_size']);
            $mobs = [];
            for ($m = 0; $m < $groupSize; $m++) {
                $mobs[] = ['id' => $m + 1, 'hp' => $scaledMonster['hp'], 'maxHp' => $scaledMonster['hp'], 'cc_turns' => 0];
            }

            $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
            $playerFirst = $playerAgi >= $scaledMonster['agi'];

            while ($playerHp > 0 && count(array_filter($mobs, fn ($mb) => $mb['hp'] > 0)) > 0 && $turnCount < $maxTurns) {
                $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);
                $aliveMobs = array_values(array_filter($mobs, fn ($mb) => $mb['hp'] > 0));
                $totalCurrentMonsterHp = array_sum(array_column($mobs, 'hp'));

                if ($isPlayerTurn && !empty($aliveMobs)) {
                    $playerMana = min($playerMaxMana, $playerMana + max(5, (int) ceil($playerMaxMana * 0.05)));
                    foreach ($activeCooldowns as $id => $cd) {
                        if ($cd > 0) $activeCooldowns[$id]--;
                    }
                    foreach ($activeBuffs as $k => $b) {
                        $activeBuffs[$k]['duration']--;
                        if ($activeBuffs[$k]['duration'] <= 0) unset($activeBuffs[$k]);
                    }
                    $activePassives = $this->evaluatePassivesForTurn($character, $equippedSkills, $playerMana);

                    $targetMobId = $aliveMobs[0]['id'];
                    $targetMobHp = $aliveMobs[0]['hp'];

                    $turn = $this->playerAttackStep($character, $monster, $scaledMonster, $playerHp, $targetMobHp, $scaledMonster['hp'], $playerMaxHp, $equippedSkills, $activeCooldowns, $activeDots, $activeBuffs, $activePassives, $playerMana, $playerMaxMana);
                    $damageDealt = max(0, $targetMobHp - $turn['enemyHp']);
                    $playerHp = $turn['playerHp'];
                    $playerMana = $turn['playerMana'];

                    $isAoe = ($turn['type'] === 'skill' && !empty($turn['effect_type']) && in_array($turn['effect_type'], ['aoe_dmg', 'direct_dmg']) && $equippedSkills->firstWhere('skill.name', $turn['skill_name'])?->skill->is_aoe);

                    foreach ($mobs as &$mb) {
                        if ($mb['hp'] <= 0) continue;
                        if ($isAoe) {
                            $mb['hp'] = max(0, $mb['hp'] - $turn['value']);
                        } elseif ($mb['id'] === $targetMobId) {
                            $mb['hp'] = max(0, $mb['hp'] - $damageDealt);
                        }
                        if (!empty($turn['cc_applied']) && ($isAoe || $mb['id'] === $targetMobId)) {
                            $mb['cc_turns'] = max($mb['cc_turns'], (int) $turn['cc_applied']['duration']);
                        }
                    }
                    unset($mb);

                    $turn['enemyHp'] = array_sum(array_column($mobs, 'hp'));
                    $turns[] = $turn;
                    $turnCount++;

                    if ($turn['enemyHp'] > 0 && $this->rollExtraAttack($activePassives)) {
                        $aliveAfter = array_values(array_filter($mobs, fn ($mb) => $mb['hp'] > 0));
                        if (!empty($aliveAfter)) {
                            foreach ($mobs as $mk => $mv) {
                                if ($mv['id'] === $aliveAfter[0]['id']) {
                                    $bonusMob = &$mobs[$mk];
                                    $extraTurn = $this->playerAttackStep($character, $monster, $scaledMonster, $playerHp, $bonusMob['hp'], $scaledMonster['hp'], $playerMaxHp, $equippedSkills, $activeCooldowns, $activeDots, $activeBuffs, $activePassives, $playerMana, $playerMaxMana);
                                    $extraTurn['extra_attack'] = true;
                                    $dmg = max(0, $bonusMob['hp'] - $extraTurn['enemyHp']);
                                    $bonusMob['hp'] = max(0, $bonusMob['hp'] - $dmg);
                                    $playerHp = $extraTurn['playerHp'];
                                    $playerMana = $extraTurn['playerMana'];
                                    $extraTurn['enemyHp'] = array_sum(array_column($mobs, 'hp'));
                                    $turns[] = $extraTurn;
                                    $turnCount++;
                                    unset($bonusMob);
                                    break;
                                }
                            }
                        }
                    }
                } else {
                    foreach ($mobs as &$aliveMob) {
                        if ($aliveMob['hp'] <= 0 || $playerHp <= 0) continue;
                        if ($aliveMob['cc_turns'] > 0) {
                            $aliveMob['cc_turns']--;
                            $turns[] = ['actor' => 'enemy', 'type' => 'crowd_controlled', 'value' => 0, 'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $totalCurrentMonsterHp];
                        } else {
                            $turn = $this->monsterAttackStep($monster, $scaledMonster, $character, $playerHp, $totalCurrentMonsterHp, $activeBuffs);
                            $playerHp = $turn['playerHp'];
                            $turns[] = $turn;
                        }
                    }
                    unset($aliveMob);
                    $turnCount++;
                }
            }

            $monsterHp = array_sum(array_column($mobs, 'hp'));
            $won = $monsterHp <= 0;
        } else {
            $monsterHp = $scaledMonster['hp'];
            $monsterMaxHp = $monsterHp;

            $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
            $playerFirst = $playerAgi >= $scaledMonster['agi'];

            while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
                $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

                if ($isPlayerTurn) {
                    $playerMana = min($playerMaxMana, $playerMana + max(5, (int) ceil($playerMaxMana * 0.05)));
                    foreach ($activeCooldowns as $id => $cd) {
                        if ($cd > 0) $activeCooldowns[$id]--;
                    }
                    foreach ($activeBuffs as $k => $b) {
                        $activeBuffs[$k]['duration']--;
                        if ($activeBuffs[$k]['duration'] <= 0) unset($activeBuffs[$k]);
                    }
                    $activePassives = $this->evaluatePassivesForTurn($character, $equippedSkills, $playerMana);

                    $turn = $this->playerAttackStep($character, $monster, $scaledMonster, $playerHp, $monsterHp, $monsterMaxHp, $playerMaxHp, $equippedSkills, $activeCooldowns, $activeDots, $activeBuffs, $activePassives, $playerMana, $playerMaxMana);
                    $playerHp = $turn['playerHp'];
                    $monsterHp = $turn['enemyHp'];
                    $playerMana = $turn['playerMana'];

                    if (!empty($turn['cc_applied'])) {
                        $monsterCcTurns = max($monsterCcTurns, (int) $turn['cc_applied']['duration']);
                    }

                    $turns[] = $turn;
                    $turnCount++;

                    if ($monsterHp > 0 && $this->rollExtraAttack($activePassives)) {
                        $extraTurn = $this->playerAttackStep($character, $monster, $scaledMonster, $playerHp, $monsterHp, $monsterMaxHp, $playerMaxHp, $equippedSkills, $activeCooldowns, $activeDots, $activeBuffs, $activePassives, $playerMana, $playerMaxMana);
                        $extraTurn['extra_attack'] = true;
                        $playerHp = $extraTurn['playerHp'];
                        $monsterHp = $extraTurn['enemyHp'];
                        $playerMana = $extraTurn['playerMana'];
                        if (!empty($extraTurn['cc_applied'])) {
                            $monsterCcTurns = max($monsterCcTurns, (int) $extraTurn['cc_applied']['duration']);
                        }
                        $turns[] = $extraTurn;
                        $turnCount++;
                    }
                    continue;
                }

                if ($monsterCcTurns > 0) {
                    $monsterCcTurns--;
                    $turn = ['actor' => 'enemy', 'type' => 'crowd_controlled', 'value' => 0, 'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
                } else {
                    $turn = $this->monsterAttackStep($monster, $scaledMonster, $character, $playerHp, $monsterHp, $activeBuffs);
                    $playerHp = $turn['playerHp'];
                }

                $turns[] = $turn;
                $turnCount++;
            }

            $won = $monsterHp <= 0;
        }

        // Zapis stanu HP/mana. Normalny event: reset do pełna przed kolejnym potworem
        // (tak jak zwykła eksploracja). Hardcore: stan przenosi się 1:1 (bez resetu) -
        // to jedyna różnica mechaniczna między trybami.
        $run->current_hp = $run->is_hardcore ? $playerHp : $playerMaxHp;
        $run->current_mana = $playerMana;

        if (!$won || $playerHp <= 0) {
            $run->is_failed = true;
            $run->save();

            return Result::ok([
                'turns' => $turns,
                'result' => 'lose',
                'slot' => $run->current_monster_index,
                'player_hp' => $playerHp,
                'start_player_hp' => $startPlayerHp,
                'monster' => $monsterDisplay,
                'monster_max_hp' => $monsterMaxHpDisplay,
                'event_name' => $event->name,
                'is_hardcore' => $run->is_hardcore,
            ]);
        }

        $rankingCategory = $slot['is_boss'] ? 'map_bosses_killed' : 'monsters_killed';
        $killCount = $slot['is_group'] ? max(1, (int) $slot['group_size']) : 1;
        app(WeeklyRankingService::class)->incrementScore($character->id, $rankingCategory, $killCount);

        $accumulatedLoot = $run->accumulated_loot ?? ['gold' => 0, 'xp' => 0, 'items' => []];
        $slotLoot = $this->calculateSlotLoot($character, $monster, $event, $upgradeLevel, $run->is_hardcore, $slot['is_boss'], $killCount);
        $accumulatedLoot['gold'] += $slotLoot['gold'];
        $accumulatedLoot['xp'] += $slotLoot['xp'];
        foreach ($slotLoot['items'] as $item) {
            $accumulatedLoot['items'][] = $item;
        }
        $run->accumulated_loot = $accumulatedLoot;

        if ($run->isLastSlot()) {
            // Ostatni slot (boss) pokonany - event ukończony. Rzut bonusowych skrzyń
            // (bazowe z rangi + bonus z poziomu ulepszenia) i przyznanie całego runu.
            $chestQuantity = mt_rand($event->chest_min, max($event->chest_min, $event->chest_max))
                + mt_rand($upgradeLevel->chest_bonus_min, max($upgradeLevel->chest_bonus_min, $upgradeLevel->chest_bonus_max));

            if ($chestQuantity > 0) {
                $chestTemplate = $this->getChestForMap($run->map);
                if ($chestTemplate) {
                    $accumulatedLoot['items'][] = [
                        'type' => 'item',
                        'name' => $chestTemplate->name,
                        'quantity' => $chestQuantity,
                        'ref_ulid' => $chestTemplate->id,
                        'icon' => $chestTemplate->icon,
                    ];
                }
            }

            $run->accumulated_loot = $accumulatedLoot;
            $run->is_completed = true;
            $run->save();

            $this->grantAccumulatedLoot($run);

            return Result::ok([
                'turns' => $turns,
                'result' => 'event_complete',
                'slot' => $run->current_monster_index,
                'player_hp' => $playerHp,
                'start_player_hp' => $startPlayerHp,
                'loot' => $slotLoot,
                'total_loot' => $accumulatedLoot,
                'chests_awarded' => $chestQuantity,
                'monster' => $monsterDisplay,
                'monster_max_hp' => $monsterMaxHpDisplay,
                'event_name' => $event->name,
                'is_hardcore' => $run->is_hardcore,
            ]);
        }

        $run->current_monster_index++;
        $run->save();

        return Result::ok([
            'turns' => $turns,
            'result' => 'slot_clear',
            'slot' => $run->current_monster_index - 1,
            'next_slot' => $run->current_monster_index,
            'player_hp' => $playerHp,
            'start_player_hp' => $startPlayerHp,
            'loot' => $slotLoot,
            'monster' => $monsterDisplay,
            'monster_max_hp' => $monsterMaxHpDisplay,
            'event_name' => $event->name,
            'is_hardcore' => $run->is_hardcore,
        ]);
    }

    /**
     * Skalowane staty potwora dla tego eventu: atak/def wg atkMultiplier (ranga x
     * poziom ulepszenia), hp wg hpMultiplier (tylko poziom ulepszenia - patrz arkusz,
     * baza rangi nie ma osobnego mnożnika HP). Agi/crit/dodge nietknięte.
     */
    private function getScaledMonsterStats(Monster $monster, Character $character, float $atkMultiplier, float $hpMultiplier): array
    {
        $base = $monster->getScaledStats($character->level);

        return [
            'hp' => max(1, (int) round($base['hp'] * $hpMultiplier)),
            'atk' => max(1, (int) round($base['atk'] * $atkMultiplier)),
            'def' => (int) round($base['def'] * $atkMultiplier),
            'agi' => $base['agi'],
            'crit' => $base['crit'],
            'dodge' => $base['dodge'],
        ];
    }

    public function usePotion(CharacterLocationEventRun $run, string $itemInstanceId): Result
    {
        if ($run->is_completed || $run->is_failed) {
            return Result::error('RUN_ENDED', 'Ten event się już zakończył.');
        }

        $character = $run->character;
        $potion = ItemInstance::where('id', $itemInstanceId)
            ->where('owner_character_id', $character->id)
            ->where('location', 'inventory')
            ->first();

        if (!$potion) {
            return Result::error('NO_POTION', 'Nie posiadasz tej mikstury.');
        }

        $template = $potion->template;
        $stats = $template->base_stats ?? [];
        if (!$template || $template->type !== 'consumable' || (!isset($stats['heal_amount']) && !isset($stats['heal_pct']))) {
            return Result::error('NOT_CONSUMABLE', 'Ten przedmiot nie jest miksturą.');
        }

        $maxHp = $character->getMaxHp();
        $healAmount = isset($stats['heal_pct'])
            ? (int) ceil($maxHp * ($stats['heal_pct'] / 100))
            : (int) ($stats['heal_amount'] ?? 0);

        $run->current_hp = min($maxHp, $run->current_hp + $healAmount);
        $run->save();

        if ($potion->stack_size > 1) {
            $potion->decrement('stack_size');
        } else {
            $potion->delete();
        }

        return Result::ok([
            'healed' => $healAmount,
            'current_hp' => $run->current_hp,
            'max_hp' => $maxHp,
        ]);
    }

    private function playerAttackStep(
        Character $character,
        Monster $monster,
        array $scaledMonster,
        int $playerHp,
        int $monsterHp,
        int $monsterMaxHp,
        int $playerMaxHp,
        $equippedSkills,
        array &$activeCooldowns,
        array &$activeDots,
        array &$activeBuffs,
        array $activePassives,
        int $playerMana,
        int $playerMaxMana
    ): array {
        $equippedWeaponType = $character->getEquippedWeaponType();
        $eq = $character->getEquipmentStats();

        $usedSkill = null;

        foreach ($equippedSkills as $cs) {
            if ($cs->skill->type === 'active' && ($activeCooldowns[$cs->id] ?? 0) <= 0) {
                $reqWep = $cs->skill->required_weapon_type;
                if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                    continue;
                }

                $manaCost = $cs->getManaCost();
                if ($playerMana < $manaCost) {
                    continue;
                }

                $effVal = $cs->getEffectiveValue();

                if ($cs->skill->effect_type === 'heal') {
                    $healAmount = max(1, (int) round($playerMaxHp * $effVal));
                    $maxAllowedHp = min($playerMaxHp - 1, $playerMaxHp - $healAmount + (int) round($playerMaxHp * 0.15));
                    if ($playerHp > $maxAllowedHp) {
                        continue;
                    }
                }

                $playerMana -= $manaCost;
                $activeCooldowns[$cs->id] = $cs->getCooldown();

                if ($cs->skill->effect_type === 'poison' || $cs->skill->effect_type === 'fire') {
                    $activeDots[] = ['type' => $cs->skill->effect_type, 'name' => $cs->skill->name, 'icon' => $cs->skill->icon, 'value' => $effVal, 'duration' => $cs->skill->base_duration];
                } elseif ($cs->skill->effect_type === 'buff_phys_dmg') {
                    $activeBuffs['phys_dmg'] = ['type' => $cs->skill->effect_type, 'name' => $cs->skill->name, 'icon' => $cs->skill->icon, 'value' => $effVal, 'duration' => $cs->skill->base_duration];
                } elseif ($cs->skill->effect_type === 'buff_defense') {
                    $activeBuffs['defense'] = ['type' => $cs->skill->effect_type, 'name' => $cs->skill->name, 'icon' => $cs->skill->icon, 'value' => $effVal, 'duration' => $cs->skill->base_duration];
                }

                $usedSkill = ['skill' => $cs->skill, 'effVal' => $effVal];
                break;
            }
        }

        $dotDamage = 0;
        $dotType = null;
        foreach ($activeDots as $k => $dot) {
            if ($dot['type'] === 'poison') {
                $dmg = (int) ($monsterMaxHp * $dot['value']);
            } elseif ($dot['type'] === 'bleed') {
                $dmg = (int) ($monsterHp * $dot['value']);
            } elseif ($dot['type'] === 'fire') {
                $dmg = (int) ($monsterMaxHp * $dot['value']);
            } else {
                $dmg = 0;
            }
            $dmg = max(1, $dmg);
            $dotDamage += $dmg;
            $dotType = $dot['type'];

            $activeDots[$k]['duration']--;
            if ($activeDots[$k]['duration'] <= 0) unset($activeDots[$k]);
        }

        if ($usedSkill) {
            $csSkill = $usedSkill['skill'];
            $effVal = $usedSkill['effVal'];

            if ($csSkill->effect_type === 'heal') {
                $healAmount = max(1, (int) round($playerMaxHp * $effVal));
                $newPlayerHp = min($playerMaxHp, $playerHp + $healAmount);
                $newMonsterHp = max(0, $monsterHp - $dotDamage);

                return [
                    'actor' => 'player', 'type' => 'skill_heal', 'skill_name' => $csSkill->name,
                    'effect_type' => $csSkill->effect_type, 'is_magic' => (bool) $csSkill->is_magic,
                    'value' => $healAmount, 'healAmount' => $healAmount,
                    'dotDamage' => $dotDamage > 0 ? $dotDamage : null, 'dotType' => $dotDamage > 0 ? $dotType : null,
                    'crit' => false, 'playerHp' => $newPlayerHp, 'enemyHp' => $newMonsterHp,
                    'playerMana' => $playerMana, 'playerMaxMana' => $playerMaxMana,
                ];
            }

            $skillMultiplier = 1.0;
            if (in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                $skillMultiplier = $effVal;
            }

            $damageData = $this->calculatePlayerDamage($character, $monster, $scaledMonster, (bool) $csSkill->is_magic);
            $damage = (int) ($damageData['total'] * $skillMultiplier);
            $baseDamage = (int) ($damageData['base'] * $skillMultiplier);
            $bonusDamage = (int) ($damageData['bonus'] * $skillMultiplier);
            $magicDamage = (int) (($damageData['magic'] ?? 0) * $skillMultiplier);

            $physBuffValue = ($activeBuffs['phys_dmg']['value'] ?? 0) + ($activePassives['aura_dmg'] ?? 0);
            if ($physBuffValue > 0) {
                $damage = (int) ($damage * (1 + $physBuffValue));
                $baseDamage = (int) ($baseDamage * (1 + $physBuffValue));
            }

            $isCrit = $this->rollCritical($character);
            if ($isCrit) {
                $damage = (int) ($damage * 1.5);
                $baseDamage = (int) ($baseDamage * 1.5);
                $bonusDamage = (int) ($bonusDamage * 1.5);
                $magicDamage = (int) ($magicDamage * 1.5);
            }

            if ($csSkill->is_magic) {
                $magicDamage += $baseDamage + $bonusDamage;
                $baseDamage = 0;
                $bonusDamage = 0;
            }

            $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

            $result = [
                'actor' => 'player', 'type' => 'skill', 'skill_name' => $csSkill->name,
                'effect_type' => $csSkill->effect_type, 'is_magic' => (bool) $csSkill->is_magic,
                'value' => $damage, 'dotDamage' => $dotDamage > 0 ? $dotDamage : null,
                'dotType' => $dotDamage > 0 ? $dotType : null, 'crit' => $isCrit,
                'playerHp' => $playerHp, 'enemyHp' => $newMonsterHp,
                'baseDamage' => $baseDamage, 'bonusDamage' => $bonusDamage > 0 ? $bonusDamage : null,
                'magicDamage' => $magicDamage > 0 ? $magicDamage : null,
                'playerMana' => $playerMana, 'playerMaxMana' => $playerMaxMana,
            ];

            if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
                $result['cc_applied'] = ['type' => $csSkill->effect_type, 'duration' => max(1, (int) $csSkill->base_duration)];
            }

            $procs = $this->rollEquipmentProcs($eq);
            if ($procs['dot']) {
                $activeDots[] = $procs['dot'];
            }
            if ($procs['cc']) {
                $result['cc_applied'] = ['type' => $procs['cc']['type'], 'duration' => max($result['cc_applied']['duration'] ?? 0, $procs['cc']['duration'])];
            }

            return $result;
        }

        $damageData = $this->calculatePlayerDamage($character, $monster, $scaledMonster);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $bonusDamage = $damageData['bonus'];
        $magicDamage = $damageData['magic'] ?? 0;

        $physBuffValue = ($activeBuffs['phys_dmg']['value'] ?? 0) + ($activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int) ($damage * (1 + $physBuffValue));
            $baseDamage = (int) ($baseDamage * (1 + $physBuffValue));
        }

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $isCrit = $this->rollCritical($character);
        $isMiss = $this->rollDodge($scaledMonster['agi'], $playerAgi);

        if ($isMiss) {
            $newMonsterHp = max(0, $monsterHp - $dotDamage);
            return [
                'actor' => 'player', 'type' => 'miss', 'value' => 0,
                'dotDamage' => $dotDamage > 0 ? $dotDamage : null, 'dotType' => $dotDamage > 0 ? $dotType : null,
                'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $newMonsterHp,
                'playerMana' => $playerMana, 'playerMaxMana' => $playerMaxMana,
            ];
        }

        if ($isCrit) {
            $damage = (int) ($damage * 1.5);
            $baseDamage = (int) ($baseDamage * 1.5);
            $bonusDamage = (int) ($bonusDamage * 1.5);
            $magicDamage = (int) ($magicDamage * 1.5);
        }

        $newMonsterHp = max(0, $monsterHp - $damage - $dotDamage);

        $turn = [
            'actor' => 'player', 'type' => 'hit', 'value' => $damage,
            'dotDamage' => $dotDamage > 0 ? $dotDamage : null, 'dotType' => $dotDamage > 0 ? $dotType : null,
            'crit' => $isCrit, 'playerHp' => $playerHp, 'enemyHp' => $newMonsterHp,
            'playerMana' => $playerMana, 'playerMaxMana' => $playerMaxMana,
        ];

        if ($bonusDamage > 0 || $magicDamage > 0) {
            $turn['baseDamage'] = $baseDamage;
            $turn['bonusDamage'] = $bonusDamage > 0 ? $bonusDamage : null;
            $turn['magicDamage'] = $magicDamage > 0 ? $magicDamage : null;
        }

        $procs = $this->rollEquipmentProcs($eq);
        if ($procs['dot']) {
            $activeDots[] = $procs['dot'];
        }
        if ($procs['cc']) {
            $turn['cc_applied'] = ['type' => $procs['cc']['type'], 'duration' => max($turn['cc_applied']['duration'] ?? 0, $procs['cc']['duration'])];
        }

        return $turn;
    }

    private function monsterAttackStep(Monster $monster, array $scaledMonster, Character $character, int $playerHp, int $monsterHp, array $activeBuffs): array
    {
        $damageData = $this->calculateMonsterDamage($monster, $scaledMonster, $character);
        $damage = $damageData['total'];

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $isCrit = $this->rollMonsterCritical($scaledMonster);
        $playerItemDodge = (float) ($character->getEquipmentStats()['dodge_chance'] ?? 0);
        $isMiss = $this->rollDodge($playerAgi, $scaledMonster['agi'], $playerItemDodge);

        if ($isMiss) {
            return ['actor' => 'enemy', 'type' => 'miss', 'value' => 0, 'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
        }

        if ($isCrit) {
            $damage = (int) ($damage * 1.5);
        }

        $defenseBuffValue = min(0.75, max(0, $activeBuffs['defense']['value'] ?? 0));
        if ($defenseBuffValue > 0) {
            $damage = max(1, (int) ($damage * (1 - $defenseBuffValue)));
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        return ['actor' => 'enemy', 'type' => 'hit', 'value' => $damage, 'crit' => $isCrit, 'playerHp' => $newPlayerHp, 'enemyHp' => $monsterHp];
    }

    private function evaluatePassivesForTurn(Character $character, $equippedSkills, int &$playerMana): array
    {
        $activePassives = [];
        if (!$equippedSkills) {
            return $activePassives;
        }

        $equippedWeaponType = $character->getEquippedWeaponType();

        foreach ($equippedSkills as $cs) {
            if ($cs->skill->type !== 'passive') {
                continue;
            }

            $reqWep = $cs->skill->required_weapon_type;
            if (!empty($reqWep) && $reqWep !== 'all' && $reqWep !== $equippedWeaponType) {
                continue;
            }

            $manaCost = $cs->getManaCost();
            if ($playerMana < $manaCost) {
                continue;
            }

            $playerMana -= $manaCost;
            $effVal = $cs->skill->base_value + ($cs->skill->scaling_value * ($cs->level - 1));

            if ($cs->skill->effect_type === 'passive_aura_dmg') {
                $activePassives['aura_dmg'] = ($activePassives['aura_dmg'] ?? 0) + $effVal;
            } elseif ($cs->skill->effect_type === 'passive_extra_attack') {
                $chance = min(0.75, max(0, $effVal));
                $activePassives['extra_attack_chance'] = max($activePassives['extra_attack_chance'] ?? 0, $chance);
            }
        }

        return $activePassives;
    }

    private function rollExtraAttack(array $activePassives): bool
    {
        $chance = $activePassives['extra_attack_chance'] ?? 0;
        if ($chance <= 0) {
            return false;
        }

        return mt_rand(1, 10000) <= (int) round($chance * 10000);
    }

    private function rollEquipmentProcs(array $eq): array
    {
        $poisonChance = max(0, $eq['poison_chance'] ?? 0);
        $bleedChance = max(0, $eq['bleed_chance'] ?? 0);
        $stunChance = max(0, $eq['stun_chance'] ?? 0);
        $doubleStrikeChance = max(0, $eq['double_strike_chance'] ?? 0);
        $magicInfusionChance = max(0, $eq['magic_infusion_chance'] ?? 0);

        $dot = null;
        $cc = null;
        $doubleStrike = false;
        $infusionArmorPen = 0;

        if ($poisonChance > 0 && mt_rand(1, 100) <= $poisonChance) {
            $dot = ['type' => 'poison', 'name' => 'Zatrucie (Ekwipunek)', 'value' => self::EQUIPMENT_POISON_VALUE, 'duration' => self::EQUIPMENT_POISON_DURATION];
        } elseif ($bleedChance > 0 && mt_rand(1, 100) <= $bleedChance) {
            $dot = ['type' => 'bleed', 'name' => 'Krwawienie (Ekwipunek)', 'value' => self::EQUIPMENT_POISON_VALUE, 'duration' => self::EQUIPMENT_POISON_DURATION];
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
                $dot = ['type' => 'bleed', 'name' => 'Infuzja Krwawienia', 'value' => self::EQUIPMENT_POISON_VALUE, 'duration' => self::EQUIPMENT_POISON_DURATION];
            } elseif ($infusionRoll === 2 && !$dot) {
                $dot = ['type' => 'poison', 'name' => 'Infuzja Otrucia', 'value' => self::EQUIPMENT_POISON_VALUE, 'duration' => self::EQUIPMENT_POISON_DURATION];
            } elseif ($infusionRoll === 3) {
                $doubleStrike = true;
            } elseif ($infusionRoll === 4) {
                $infusionArmorPen = 50;
            }
        }

        return ['dot' => $dot, 'cc' => $cc, 'double_strike' => $doubleStrike, 'infusion_armor_pen' => $infusionArmorPen];
    }

    private function calculatePlayerDamage(Character $character, Monster $monster, array $scaledMonster, bool $isMagicSkill = false): array
    {
        $weaponType = $character->getEquippedWeaponType();
        $eq = $character->getEquipmentStats();

        if ($isMagicSkill) {
            $statBonus = $character->getAttributeAttackBonus($weaponType);
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
        } else {
            if ($weaponType === 'wand') {
                $statBonus = $character->getAttributeAttackBonus('sword');
                $weaponAtkMin = (int) ($eq['attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['attack_max'] ?? 0);
            } elseif ($weaponType === 'bell') {
                $statBonus = $character->getAttributeAttackBonus('bell');
                $weaponAtkMin = (int) ($eq['attack_min'] ?? 0);
                $weaponAtkMax = (int) max($weaponAtkMin, $eq['attack_max'] ?? 0);
            } else {
                $statBonus = $character->getAttributeAttackBonus($weaponType);
                $weaponAtkMin = ($eq['attack_min'] ?? 0);
                $weaponAtkMax = ($eq['attack_max'] ?? 0);
            }
        }

        $baseDamageMin = 10 + $statBonus + ($character->level * 1) + $weaponAtkMin;
        $baseDamageMax = 10 + $statBonus + ($character->level * 1) + $weaponAtkMax;
        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }

        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $defense = $scaledMonster['def'];
        $baseDamage = max(1, $damage - ($defense * 0.2));
        $bonusDamage = 0;

        $bonusPercentage = ($eq['bonus_vs_monsters'] ?? 0) + ($eq['strong_vs_monsters'] ?? 0) + ($eq['bonus_vs_monster'] ?? 0);

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $bonusPercentage += ($eq['strong_vs_' . $typeStr] ?? 0) + ($eq['bonus_vs_' . $typeStr] ?? 0) + ($eq['strong_vs_' . $typeStr . 's'] ?? 0);
        }

        if ($bonusPercentage > 0) {
            $bonusDamage = (int) ($baseDamage * ($bonusPercentage / 100));
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

        return [
            'base' => (int) $baseDamage,
            'bonus' => (int) $bonusDamage,
            'magic' => (int) $magicBurstDamage,
            'total' => (int) ($baseDamage + $bonusDamage + $magicBurstDamage),
        ];
    }

    private function calculateMonsterDamage(Monster $monster, array $scaledMonster, Character $character): array
    {
        $baseDamage = $scaledMonster['atk'];
        $vitality = $character->getTotalAttributes()['vit'] ?? 1;
        $eq = $character->getEquipmentStats();
        $defense = $vitality + ($character->level / 2) + ($eq['defense'] ?? 0);

        $damage = max(1, $baseDamage - ($defense * 0.2));
        $resistDamage = 0;

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $resistPercentage = ($eq['resist_' . $typeStr] ?? 0) + ($eq['resist_' . $typeStr . 's'] ?? 0);
            if ($resistPercentage > 0) {
                $resistDamage = (int) ($damage * ($resistPercentage / 100));
            }
        }

        return ['base' => (int) $damage, 'resist' => (int) $resistDamage, 'total' => max(1, (int) $damage - $resistDamage)];
    }

    private function rollCritical(Character $character): bool
    {
        $agility = $character->getTotalAttributes()['agi'] ?? 1;
        $eq = $character->getEquipmentStats();
        $baseCrit = 0.05 + ($agility * 0.0015) + (($eq['crit_chance'] ?? 0) / 100);
        $critChance = max(0.03, min(1.00, $baseCrit));

        return mt_rand(1, 1000) <= (int) round($critChance * 1000);
    }

    private function rollMonsterCritical(array $scaledMonster): bool
    {
        $baseCrit = 0.03 + ($scaledMonster['agi'] * 0.003);
        $critChance = max(0.02, min(0.30, $baseCrit));

        return mt_rand(1, 1000) <= (int) round($critChance * 1000);
    }

    private function rollDodge(int $defenderAgi, int $attackerAgi = 0, float $defenderItemDodge = 0.0): bool
    {
        $dodgeChance = max(0.00, min(0.30, 0.03 + ($defenderAgi * 0.0006) + ($defenderItemDodge / 100.0)));

        return mt_rand(1, 1000) <= (int) round($dodgeChance * 1000);
    }

    public function getChestForMap(Map $map): ?ItemTemplate
    {
        if (isset(self::MAP_CHEST_NAMES[$map->name])) {
            $template = ItemTemplate::where('name', self::MAP_CHEST_NAMES[$map->name])->first();
            if ($template) {
                return $template;
            }
        }

        return ItemTemplate::where('type', 'consumable')->where('sub_type', 'chest')->first();
    }

    /**
     * Nagroda gold/xp za pojedynczy slot (lub sumę killCount sztuk w grupie), skalowana
     * mnożnikiem rangi eventu x mnożnikiem poziomu ulepszenia x (opcjonalnie) bonusem
     * hardcore. Item z tabeli zrzutów potwora losowany wyłącznie na slocie bossa (ostatni
     * w łańcuchu) - analogicznie do DungeonService::calculateStageLoot().
     */
    private function calculateSlotLoot(Character $character, Monster $monster, LocationEvent $event, LocationEventUpgradeLevel $upgradeLevel, bool $isHardcore, bool $isBossSlot, int $killCount): array
    {
        $multiplierService = app(RewardMultiplierService::class);
        $globalGoldMult = $multiplierService->getGoldMultiplier($character);
        $globalExpMult = $multiplierService->getExpMultiplier($character);

        $goldEventMult = $event->reward_multiplier * $upgradeLevel->drop_multiplier * ($isHardcore ? self::HARDCORE_REWARD_BONUS_MULTIPLIER : 1.0);
        $xpEventMult = $event->reward_multiplier * $upgradeLevel->exp_multiplier * ($isHardcore ? self::HARDCORE_REWARD_BONUS_MULTIPLIER : 1.0);

        $totalGold = 0;
        $totalXp = 0;

        for ($i = 0; $i < $killCount; $i++) {
            $baseGold = 10 + ($monster->level * 2);
            $baseGold = (int) ($baseGold * mt_rand(80, 120) / 100);

            $levelDiff = $monster->level - $character->level;
            $baseXp = 25 * pow($monster->level, 1.2) + 30;
            if ($levelDiff > 0) {
                $baseXp *= (1 + ($levelDiff * 0.1));
            } elseif ($levelDiff < -5) {
                $baseXp *= max(0.1, 1 + ($levelDiff * 0.05));
            }
            $baseXp = (int) round($baseXp);

            $totalGold += (int) round($baseGold * $globalGoldMult * $goldEventMult);
            $totalXp += (int) round($baseXp * $globalExpMult * $xpEventMult);
        }

        $items = [];

        if ($isBossSlot && $monster->lootTable) {
            $entries = $monster->lootTable->entries->toArray();
            if (!empty($entries)) {
                $picker = app(WeightedPicker::class);
                $rng = app(RandomProvider::class);

                $selectedEntry = $picker->pick($entries);
                if ($selectedEntry) {
                    $quantity = (int) round($rng->int($selectedEntry['min_qty'], $selectedEntry['max_qty']) * $upgradeLevel->drop_multiplier);
                    $quantity = max(1, $quantity);

                    if ($selectedEntry['reward_type'] === 'gold') {
                        $totalGold += $quantity;
                    } elseif ($selectedEntry['reward_type'] === 'gems') {
                        $items[] = ['type' => 'gems', 'name' => 'Klejnoty', 'quantity' => $quantity, 'ref_ulid' => null, 'icon' => null];
                    } elseif (in_array($selectedEntry['reward_type'], ['item', 'material'])) {
                        $template = ItemTemplate::find($selectedEntry['ref_ulid']);
                        $items[] = [
                            'type' => $selectedEntry['reward_type'],
                            'name' => $template ? $template->name : 'Przedmiot',
                            'quantity' => $quantity,
                            'ref_ulid' => $selectedEntry['ref_ulid'],
                            'icon' => $template?->icon,
                        ];
                    }
                }
            }
        }

        return ['gold' => $totalGold, 'xp' => $totalXp, 'items' => $items];
    }

    /**
     * Przyznaje skumulowany łup całego runu po jego ukończeniu (mirror
     * DungeonService::grantAccumulatedLoot()).
     */
    private function grantAccumulatedLoot(CharacterLocationEventRun $run): void
    {
        $character = $run->character;
        $loot = $run->accumulated_loot;
        if (!$loot) {
            return;
        }

        DB::transaction(function () use ($character, $loot, $run) {
            $idempotencyKey = "location_event_run:{$run->id}";

            if (($loot['gold'] ?? 0) > 0) {
                $newGold = $character->gold + $loot['gold'];
                $character->update(['gold' => $newGold]);

                CurrencyLedger::create([
                    'id' => Str::ulid(),
                    'character_id' => $character->id,
                    'currency_type' => 'gold',
                    'amount' => $loot['gold'],
                    'balance_after' => $newGold,
                    'idempotency_key' => "{$idempotencyKey}:gold",
                    'source_type' => 'location_event',
                    'source_id' => $run->id,
                    'created_at' => now(),
                ]);
            }

            if (($loot['xp'] ?? 0) > 0) {
                $character->increment('xp', $loot['xp']);
                app(\App\Application\Characters\LevelUpService::class)->checkAndApply($character);
            }

            $items = $loot['items'] ?? [];
            foreach ($items as $index => $itemData) {
                if ($itemData['type'] === 'gems') {
                    $user = $character->user;
                    $newGems = $user->gems + $itemData['quantity'];
                    $user->update(['gems' => $newGems]);

                    CurrencyLedger::create([
                        'id' => Str::ulid(),
                        'character_id' => $character->id,
                        'currency_type' => 'gems',
                        'amount' => $itemData['quantity'],
                        'balance_after' => $newGems,
                        'idempotency_key' => "{$idempotencyKey}:gems:{$index}",
                        'created_at' => now(),
                    ]);
                } elseif (in_array($itemData['type'], ['item', 'material'])) {
                    $templateUlid = $itemData['ref_ulid'];
                    $quantity = $itemData['quantity'];
                    if (!$templateUlid) continue;

                    $template = ItemTemplate::find($templateUlid);
                    if (!$template) continue;

                    if (in_array($template->type, ['material', 'consumable', 'currency', 'egg', 'key', 'quest_item'])) {
                        $existingItem = ItemInstance::where('owner_character_id', $character->id)
                            ->where('template_id', $templateUlid)
                            ->where('location', 'inventory')
                            ->first();

                        if ($existingItem) {
                            $existingItem->stack_size += $quantity;
                            $existingItem->save();

                            ItemLedger::create([
                                'id' => Str::ulid(),
                                'character_id' => $character->id,
                                'item_instance_id' => $existingItem->id,
                                'action' => 'drop',
                                'ref_type' => 'location_event_run',
                                'ref_id' => $run->id,
                                'quantity_change' => $quantity,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}",
                            ]);
                        } else {
                            if ($character->isBackpackFull()) {
                                continue;
                            }
                            $instance = ItemInstance::create([
                                'id' => Str::ulid(),
                                'template_id' => $templateUlid,
                                'owner_character_id' => $character->id,
                                'location' => 'inventory',
                                'stack_size' => $quantity,
                                'rarity' => 'common',
                                'roll_stats' => [],
                                'upgrade_level' => 0,
                            ]);

                            ItemLedger::create([
                                'id' => Str::ulid(),
                                'character_id' => $character->id,
                                'item_instance_id' => $instance->id,
                                'action' => 'drop',
                                'ref_type' => 'location_event_run',
                                'ref_id' => $run->id,
                                'quantity_change' => $quantity,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}",
                            ]);
                        }
                    } else {
                        if ($character->isBackpackFull()) {
                            continue;
                        }
                        for ($i = 0; $i < $quantity; $i++) {
                            $instance = ItemInstance::create([
                                'id' => Str::ulid(),
                                'template_id' => $templateUlid,
                                'owner_character_id' => $character->id,
                                'location' => 'inventory',
                                'stack_size' => 1,
                                'rarity' => 'common',
                                'roll_stats' => [],
                                'upgrade_level' => 0,
                            ]);

                            ItemLedger::create([
                                'id' => Str::ulid(),
                                'character_id' => $character->id,
                                'item_instance_id' => $instance->id,
                                'action' => 'drop',
                                'ref_type' => 'location_event_run',
                                'ref_id' => $run->id,
                                'quantity_change' => 1,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}:{$i}",
                            ]);
                        }
                    }
                }
            }
        });
    }
}
