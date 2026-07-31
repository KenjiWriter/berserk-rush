<?php

namespace App\Application\Dungeon;

use App\Application\Shared\Result;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemLedger;
use App\Infrastructure\Persistence\CurrencyLedger;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Application\Loot\WeightedPicker;
use App\Infrastructure\RNG\RandomProvider;
use App\Application\Combat\RewardMultiplierService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DungeonService
{
    private const EQUIPMENT_POISON_DURATION = 3;
    private const EQUIPMENT_POISON_VALUE = 0.03;
    private const EQUIPMENT_STUN_DURATION = 1;

    /**
     * Rozpoczyna nowy run w dungeonie. Wymaga klucza (entry item).
     */
    public function startRun(Character $character, Dungeon $dungeon, int $keyMultiplier = 1): Result
    {
        $keyMultiplier = max(1, $keyMultiplier);

        // Sprawdź poziom
        if ($character->level < $dungeon->min_level) {
            return Result::error('LEVEL_TOO_LOW', "Wymagany poziom: {$dungeon->min_level}");
        }

        // Sprawdź czy nie ma aktywnego runu
        $activeRun = CharacterDungeonRun::where('character_id', $character->id)
            ->where('is_completed', false)
            ->where('is_failed', false)
            ->first();

        if ($activeRun) {
            return Result::error('ACTIVE_RUN', 'Masz już aktywną ekspedycję w lochu.');
        }

        // Sprawdź klucz (entry_item_template_id)
        if ($dungeon->entry_item_template_id) {
            $keyItems = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $dungeon->entry_item_template_id)
                ->whereIn('location', ['inventory', 'material_stash'])
                ->where('stack_size', '>=', 1)
                ->get();

            $totalKeys = $keyItems->sum('stack_size');
            if ($totalKeys < $keyMultiplier) {
                return Result::error('NO_KEY', "Nie posiadasz wystarczającej liczby kluczy ({$totalKeys}/{$keyMultiplier}) do tego lochu.");
            }

            // Zużyj klucze
            $remainingToDeduct = $keyMultiplier;
            foreach ($keyItems as $keyItem) {
                if ($remainingToDeduct <= 0) break;

                if ($keyItem->stack_size > $remainingToDeduct) {
                    $keyItem->decrement('stack_size', $remainingToDeduct);
                    $remainingToDeduct = 0;
                } else {
                    $remainingToDeduct -= $keyItem->stack_size;
                    $keyItem->delete();
                }
            }
        }

        // Sprawdź czy dungeon ma etapy
        $stageCount = $dungeon->stages()->count();
        if ($stageCount === 0) {
            return Result::error('NO_STAGES', 'Ten loch nie ma zdefiniowanych etapów.');
        }

        // Utwórz run
        $run = CharacterDungeonRun::create([
            'character_id' => $character->id,
            'dungeon_id' => $dungeon->id,
            'key_multiplier' => $keyMultiplier,
            'current_stage' => 1,
            'current_hp' => $character->getMaxHp(),
            'is_completed' => false,
            'is_failed' => false,
        ]);

        return Result::ok($run);
    }

    /**
     * Walka z potworem na aktualnym etapie. Zwraca wynik walki.
     */
    public function fightCurrentStage(CharacterDungeonRun $run): Result
    {
        if ($run->is_completed || $run->is_failed) {
            return Result::error('RUN_ENDED', 'Ta ekspedycja się już zakończyła.');
        }

        if ($run->combat_state === 'calculating') {
            return Result::error('ALREADY_CALCULATING', 'Walka jest już w trakcie obliczania.');
        }

        $run->combat_state = 'calculating';
        $run->combat_data = null;
        $run->save();

        // Dispatch Job
        dispatch(new \App\Jobs\SimulateDungeonStageJob($run->id));

        return Result::ok(['status' => 'calculating']);
    }

    /**
     * Wewnętrzna metoda symulująca walkę wywoływana przez Joba.
     */
    public function simulateStage(CharacterDungeonRun $run): Result
    {
        $stage = $run->getCurrentStageModel();
        if (!$stage) {
            return Result::error('NO_STAGE', 'Nie znaleziono etapu.');
        }

        $character = $run->character;
        $monster = $stage->monster;

        if (!$character || !$monster) {
            return Result::error('MISSING_DATA', 'Brak danych do walki.');
        }

        // Pobierz wyposażone umiejętności postaci
        $equippedSkills = CharacterCombatSkill::with('skill')
            ->where('character_id', $character->id)
            ->where('is_equipped', true)
            ->orderBy('equip_slot')
            ->get();

        $activeCooldowns = [];
        foreach ($equippedSkills as $cs) {
            if ($cs->skill->type === 'active') {
                $activeCooldowns[$cs->id] = max(0, $cs->skill->base_cooldown - 1);
            }
        }
        $activeDots = [];
        $activeBuffs = [];
        $activePassives = [];
        $monsterCcTurns = 0;

        // Symuluj walkę z aktualnym HP gracza (brak regeneracji!)
        $playerHp = $run->current_hp;
        $startPlayerHp = $playerHp;
        $playerMaxHp = $character->getMaxHp();

        // Zachowaj przenoszony stan many z poprzedniego etapu
        $playerMaxMana = $character->getMaxMana();
        $savedState = is_array($run->combat_state) ? $run->combat_state : (json_decode($run->combat_state ?? '', true) ?: []);
        $playerMana = isset($savedState['current_mana']) ? min($playerMaxMana, max(0, (int)$savedState['current_mana'])) : $playerMaxMana;

        $totalStages = $run->dungeon->stages()->count();
        $isBossStage = ($stage->stage_type === 'boss' || $run->current_stage >= $totalStages);
        $stageType = $stage->stage_type ?? 'single_mob';
        $monsterCount = max(1, $stage->monster_count ?? 1);
        $maxTurns = $stage->max_turns ?? 50;

        $turns = [];
        $turnCount = 0;

        $diffMultiplier = 1.0 + (($run->key_multiplier ?? 1) - 1) * 0.25;

        if ($stageType === 'gate') {
            $maxTurns = $stage->max_turns ?? 10;
            $monsterHp = (int) round(($monster->stats['hp'] ?? ($monster->level * 40)) * $diffMultiplier);
            $monsterMaxHp = $monsterHp;

            while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
                foreach ($activeCooldowns as $id => $cd) {
                    if ($cd > 0) $activeCooldowns[$id]--;
                }
                foreach ($activeBuffs as $k => $b) {
                    $activeBuffs[$k]['duration']--;
                    if ($activeBuffs[$k]['duration'] <= 0) unset($activeBuffs[$k]);
                }

                $turn = $this->playerAttackStep(
                    $character,
                    $monster,
                    $playerHp,
                    $monsterHp,
                    $monsterMaxHp,
                    $playerMaxHp,
                    $equippedSkills,
                    $activeCooldowns,
                    $activeDots,
                    $activeBuffs,
                    $activePassives,
                    $diffMultiplier,
                    $playerMana,
                    $playerMaxMana
                );

                $playerHp = $turn['playerHp'];
                $monsterHp = $turn['enemyHp'];
                $playerMana = $turn['playerMana'];
                $turns[] = $turn;
                $turnCount++;

                if ($monsterHp > 0 && $this->rollExtraAttack($activePassives)) {
                    $extraTurn = $this->playerAttackStep(
                        $character,
                        $monster,
                        $playerHp,
                        $monsterHp,
                        $monsterMaxHp,
                        $playerMaxHp,
                        $equippedSkills,
                        $activeCooldowns,
                        $activeDots,
                        $activeBuffs,
                        $activePassives,
                        $diffMultiplier,
                        $playerMana,
                        $playerMaxMana
                    );
                    $extraTurn['extra_attack'] = true;
                    $playerHp = $extraTurn['playerHp'];
                    $monsterHp = $extraTurn['enemyHp'];
                    $playerMana = $extraTurn['playerMana'];
                    $turns[] = $extraTurn;
                    $turnCount++;
                }
            }

            $won = $monsterHp <= 0;
        } elseif ($stageType === 'group_mob' && $monsterCount > 1) {
            $singleMaxHp = (int) round(($monster->stats['hp'] ?? ($monster->level * 20)) * $diffMultiplier);
            $mobs = [];
            for ($m = 0; $m < $monsterCount; $m++) {
                $mobs[] = [
                    'id' => $m + 1,
                    'hp' => $singleMaxHp,
                    'maxHp' => $singleMaxHp,
                    'cc_turns' => 0
                ];
            }
            $monsterMaxHp = $singleMaxHp * $monsterCount;

            $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
            $monsterAgi = $monster->stats['agi'] ?? $monster->level;
            $playerFirst = $playerAgi >= $monsterAgi;

            while ($playerHp > 0 && count(array_filter($mobs, fn($mb) => $mb['hp'] > 0)) > 0 && $turnCount < $maxTurns) {
                $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);
                $aliveMobs = array_values(array_filter($mobs, fn($mb) => $mb['hp'] > 0));
                $totalCurrentMonsterHp = array_sum(array_column($mobs, 'hp'));

                if ($isPlayerTurn && !empty($aliveMobs)) {
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
                    $targetMobMaxHp = $aliveMobs[0]['maxHp'];

                    $turn = $this->playerAttackStep(
                        $character,
                        $monster,
                        $playerHp,
                        $targetMobHp,
                        $targetMobMaxHp,
                        $playerMaxHp,
                        $equippedSkills,
                        $activeCooldowns,
                        $activeDots,
                        $activeBuffs,
                        $activePassives,
                        $diffMultiplier,
                        $playerMana,
                        $playerMaxMana
                    );

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

                    $totalCurrentMonsterHp = array_sum(array_column($mobs, 'hp'));
                    $turn['enemyHp'] = $totalCurrentMonsterHp;
                    $turns[] = $turn;
                    $turnCount++;

                    if ($totalCurrentMonsterHp > 0 && $this->rollExtraAttack($activePassives)) {
                        $aliveAfter = array_values(array_filter($mobs, fn($mb) => $mb['hp'] > 0));
                        if (!empty($aliveAfter)) {
                            $bonusMobKey = null;
                            foreach ($mobs as $mk => $mv) {
                                if ($mv['id'] === $aliveAfter[0]['id']) {
                                    $bonusMobKey = $mk;
                                    break;
                                }
                            }
                            if ($bonusMobKey !== null) {
                                $bonusMob = &$mobs[$bonusMobKey];
                                $extraTurn = $this->playerAttackStep(
                                    $character,
                                    $monster,
                                    $playerHp,
                                    $bonusMob['hp'],
                                    $bonusMob['maxHp'],
                                    $playerMaxHp,
                                    $equippedSkills,
                                    $activeCooldowns,
                                    $activeDots,
                                    $activeBuffs,
                                    $activePassives,
                                    $diffMultiplier,
                                    $playerMana,
                                    $playerMaxMana
                                );
                                $extraTurn['extra_attack'] = true;
                                $dmg = max(0, $bonusMob['hp'] - $extraTurn['enemyHp']);
                                $bonusMob['hp'] = max(0, $bonusMob['hp'] - $dmg);
                                $playerHp = $extraTurn['playerHp'];
                                $playerMana = $extraTurn['playerMana'];

                                $totalCurrentMonsterHp = array_sum(array_column($mobs, 'hp'));
                                $extraTurn['enemyHp'] = $totalCurrentMonsterHp;
                                $turns[] = $extraTurn;
                                $turnCount++;
                            }
                        }
                    }
                } else {
                    foreach ($mobs as &$aliveMob) {
                        if ($aliveMob['hp'] <= 0 || $playerHp <= 0) continue;

                        if ($aliveMob['cc_turns'] > 0) {
                            $aliveMob['cc_turns']--;
                            $turns[] = [
                                'actor' => 'enemy',
                                'type' => 'crowd_controlled',
                                'value' => 0,
                                'crit' => false,
                                'playerHp' => $playerHp,
                                'enemyHp' => $totalCurrentMonsterHp,
                            ];
                        } else {
                            $turn = $this->monsterAttackStep($monster, $character, $playerHp, $totalCurrentMonsterHp, $activeBuffs, $diffMultiplier);
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
            $monsterHp = (int) round(($monster->stats['hp'] ?? ($monster->level * 20)) * $diffMultiplier);
            $monsterMaxHp = $monsterHp;

            $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
            $monsterAgi = $monster->stats['agi'] ?? $monster->level;
            $playerFirst = $playerAgi >= $monsterAgi;

            while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
                $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

                if ($isPlayerTurn) {
                    foreach ($activeCooldowns as $id => $cd) {
                        if ($cd > 0) $activeCooldowns[$id]--;
                    }
                    foreach ($activeBuffs as $k => $b) {
                        $activeBuffs[$k]['duration']--;
                        if ($activeBuffs[$k]['duration'] <= 0) unset($activeBuffs[$k]);
                    }

                    $activePassives = $this->evaluatePassivesForTurn($character, $equippedSkills, $playerMana);

                    $turn = $this->playerAttackStep(
                        $character,
                        $monster,
                        $playerHp,
                        $monsterHp,
                        $monsterMaxHp,
                        $playerMaxHp,
                        $equippedSkills,
                        $activeCooldowns,
                        $activeDots,
                        $activeBuffs,
                        $activePassives,
                        $diffMultiplier,
                        $playerMana,
                        $playerMaxMana
                    );

                    $playerHp = $turn['playerHp'];
                    $monsterHp = $turn['enemyHp'];
                    $playerMana = $turn['playerMana'];

                    if (!empty($turn['cc_applied'])) {
                        $monsterCcTurns = max($monsterCcTurns, (int) $turn['cc_applied']['duration']);
                    }

                    $turns[] = $turn;
                    $turnCount++;

                    if ($monsterHp > 0 && $this->rollExtraAttack($activePassives)) {
                        $extraTurn = $this->playerAttackStep(
                            $character,
                            $monster,
                            $playerHp,
                            $monsterHp,
                            $monsterMaxHp,
                            $playerMaxHp,
                            $equippedSkills,
                            $activeCooldowns,
                            $activeDots,
                            $activeBuffs,
                            $activePassives,
                            $diffMultiplier,
                            $playerMana,
                            $playerMaxMana
                        );
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
                    $turn = [
                        'actor' => 'enemy',
                        'type' => 'crowd_controlled',
                        'value' => 0,
                        'crit' => false,
                        'playerHp' => $playerHp,
                        'enemyHp' => $monsterHp,
                    ];
                } else {
                    $turn = $this->monsterAttackStep($monster, $character, $playerHp, $monsterHp, $activeBuffs, $diffMultiplier);
                    $playerHp = $turn['playerHp'];
                }

                $turns[] = $turn;
                $turnCount++;
            }

            $won = $monsterHp <= 0;
        }

        // Zapisz stan HP po walce
        $run->current_hp = $playerHp;
        
        $accumulatedLoot = $run->accumulated_loot ?? ['gold' => 0, 'xp' => 0, 'items' => []];

        if (!$won || $playerHp <= 0) {
            $run->is_failed = true;
            $run->save();

            return Result::ok([
                'turns' => $turns,
                'result' => 'lose',
                'stage' => $run->current_stage,
                'player_hp' => $playerHp,
                'monster_max_hp' => $monsterMaxHp,
                'start_player_hp' => $startPlayerHp,
                'start_monster_hp' => $monsterMaxHp,
            ]);
        }

        // Przeciwnik pokonany - losujemy loot (przedmioty wykluczone dla nie-bossa)
        $lootFromThisStage = $this->calculateStageLoot($character, $monster, $isBossStage, $run->dungeon, $run->key_multiplier ?? 1);
        $accumulatedLoot['gold'] += $lootFromThisStage['gold'];
        $accumulatedLoot['xp'] += $lootFromThisStage['xp'];
        foreach ($lootFromThisStage['items'] as $item) {
            $accumulatedLoot['items'][] = $item;
        }
        $run->accumulated_loot = $accumulatedLoot;

        // Sprawdź czy to ostatni etap
        $totalStages = $run->dungeon->stages()->count();
        if ($run->current_stage >= $totalStages) {
            $run->is_completed = true;
            $run->save();
            
            // Ostatni etap wygrany -> przyznaj cały loot
            $this->grantAccumulatedLoot($run);

            return Result::ok([
                'turns' => $turns,
                'result' => 'dungeon_complete',
                'stage' => $run->current_stage,
                'player_hp' => $playerHp,
                'monster_max_hp' => $monsterMaxHp,
                'start_player_hp' => $startPlayerHp,
                'start_monster_hp' => $monsterMaxHp,
                'loot' => $lootFromThisStage,
                'total_loot' => $accumulatedLoot,
            ]);
        }

        // Przejdź do następnego etapu i zapisz stan many
        $savedState['current_mana'] = $playerMana;
        $run->combat_state = $savedState;
        $run->current_stage++;
        $run->save();

        return Result::ok([
            'turns' => $turns,
            'result' => 'stage_clear',
            'stage' => $run->current_stage - 1,
            'next_stage' => $run->current_stage,
            'player_hp' => $playerHp,
            'monster_max_hp' => $monsterMaxHp,
            'start_player_hp' => $startPlayerHp,
            'start_monster_hp' => $monsterMaxHp,
            'loot' => $lootFromThisStage,
        ]);
    }

    /**
     * Użycie mikstury w lochu - leczy tyle ile mikstura leczy (wyklucza skrzynie).
     */
    public function usePotion(CharacterDungeonRun $run, string $itemInstanceId): Result
    {
        if ($run->is_completed || $run->is_failed) {
            return Result::error('RUN_ENDED', 'Ta ekspedycja się już zakończyła.');
        }

        $character = $run->character;
        $potion = ItemInstance::where('id', $itemInstanceId)
            ->where('owner_character_id', $character->id)
            ->where('location', 'inventory')
            ->first();

        if (!$potion) {
            return Result::error('NO_POTION', 'Nie posiadasz tej mikstury.');
        }

        // Sprawdź czy to prawdziwa mikstura lecznicza (wyklucz skrzynie, zwoje i inne consumable bez heal_amount)
        $template = $potion->template;
        if (!$template || $template->type !== 'consumable' || !isset($template->base_stats['heal_amount'])) {
            return Result::error('NOT_CONSUMABLE', 'Ten przedmiot nie jest miksturą.');
        }

        $healAmount = $template->base_stats['heal_amount'];
        $maxHp = $character->getMaxHp();

        $run->current_hp = min($maxHp, $run->current_hp + $healAmount);
        $run->save();

        // Zużyj miksturę
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
        $monster,
        int $playerHp,
        int $monsterHp,
        int $monsterMaxHp,
        int $playerMaxHp,
        $equippedSkills,
        array &$activeCooldowns,
        array &$activeDots,
        array &$activeBuffs,
        array $activePassives,
        float $diffMultiplier = 1.0,
        int $playerMana = 0,
        int $playerMaxMana = 0
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
                    continue; // Za mało many, by użyć tej umiejętności
                }

                $playerMana -= $manaCost;
                $activeCooldowns[$cs->id] = $cs->skill->base_cooldown;
                $effVal = $cs->skill->base_value + ($cs->skill->scaling_value * ($cs->level - 1));

                if ($cs->skill->effect_type === 'poison' || $cs->skill->effect_type === 'fire') {
                    $activeDots[] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                } elseif ($cs->skill->effect_type === 'buff_phys_dmg') {
                    $activeBuffs['phys_dmg'] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
                        'value' => $effVal,
                        'duration' => $cs->skill->base_duration,
                    ];
                } elseif ($cs->skill->effect_type === 'buff_defense') {
                    $activeBuffs['defense'] = [
                        'type' => $cs->skill->effect_type,
                        'name' => $cs->skill->name,
                        'icon' => $cs->skill->icon,
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

        // Process DoTs
        $dotDamage = 0;
        $dotType = null;
        foreach ($activeDots as $k => $dot) {
            if ($dot['type'] === 'poison') {
                $dmg = (int)($monsterHp * $dot['value']);
            } elseif ($dot['type'] === 'fire') {
                $dmg = (int)($monsterMaxHp * $dot['value']);
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
                    'playerMana' => $playerMana,
                    'playerMaxMana' => $playerMaxMana,
                ];
            }

            $skillMultiplier = 1.0;
            if (in_array($csSkill->effect_type, ['direct_dmg', 'aoe_dmg', 'freeze', 'stun'], true)) {
                $skillMultiplier = $effVal;
            }

            $damageData = $this->calculatePlayerDamage($character, $monster);
            $damage = (int)($damageData['total'] * $skillMultiplier);
            $baseDamage = (int)($damageData['base'] * $skillMultiplier);
            $bonusDamage = (int)($damageData['bonus'] * $skillMultiplier);
            $magicDamage = (int)(($damageData['magic'] ?? 0) * $skillMultiplier);

            $physBuffValue = ($activeBuffs['phys_dmg']['value'] ?? 0) + ($activePassives['aura_dmg'] ?? 0);
            if ($physBuffValue > 0) {
                $damage = (int)($damage * (1 + $physBuffValue));
                $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
            }

            $isCrit = $this->rollCritical($character, $monster);
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
                'playerMana' => $playerMana,
                'playerMaxMana' => $playerMaxMana,
            ];

            if (in_array($csSkill->effect_type, ['freeze', 'stun'], true)) {
                $result['cc_applied'] = [
                    'type' => $csSkill->effect_type,
                    'duration' => max(1, (int) $csSkill->base_duration),
                ];
            }

            $procs = $this->rollEquipmentProcs($eq);
            if ($procs['dot']) {
                $activeDots[] = $procs['dot'];
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
        $damageData = $this->calculatePlayerDamage($character, $monster);
        $damage = $damageData['total'];
        $baseDamage = $damageData['base'];
        $bonusDamage = $damageData['bonus'];
        $magicDamage = $damageData['magic'] ?? 0;

        $physBuffValue = ($activeBuffs['phys_dmg']['value'] ?? 0) + ($activePassives['aura_dmg'] ?? 0);
        if ($physBuffValue > 0) {
            $damage = (int)($damage * (1 + $physBuffValue));
            $baseDamage = (int)($baseDamage * (1 + $physBuffValue));
        }

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $scaledMonsterStats = $monster->getScaledStats($character->level);
        $monsterAgi = $scaledMonsterStats['agi'] ?? ($monster->stats['agi'] ?? $monster->level);

        $isCrit = $this->rollCritical($character, $monster);
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
                'playerMana' => $playerMana,
                'playerMaxMana' => $playerMaxMana,
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
            'playerMana' => $playerMana,
            'playerMaxMana' => $playerMaxMana,
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
            $turn['cc_applied'] = [
                'type' => $procs['cc']['type'],
                'duration' => max($turn['cc_applied']['duration'] ?? 0, $procs['cc']['duration']),
            ];
        }

        return $turn;
    }

    private function monsterAttackStep($monster, Character $character, int $playerHp, int $monsterHp, array $activeBuffs, float $diffMultiplier = 1.0): array
    {
        $damageData = $this->calculateMonsterDamage($monster, $character, $diffMultiplier);
        $damage = $damageData['total'];

        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $scaledMonsterStats = $monster->getScaledStats($character->level);
        $monsterAgi = $scaledMonsterStats['agi'] ?? ($monster->stats['agi'] ?? $monster->level);

        $isCrit = $this->rollMonsterCritical($monster, $character);
        $playerItemDodge = (float)($character->getEquipmentStats()['dodge_chance'] ?? 0);
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
        }

        $defenseBuffValue = min(0.75, max(0, $activeBuffs['defense']['value'] ?? 0));
        if ($defenseBuffValue > 0) {
            $damage = max(1, (int)($damage * (1 - $defenseBuffValue)));
        }

        $newPlayerHp = max(0, $playerHp - $damage);

        return [
            'actor' => 'enemy',
            'type' => 'hit',
            'value' => $damage,
            'crit' => $isCrit,
            'playerHp' => $newPlayerHp,
            'enemyHp' => $monsterHp,
        ];
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
                continue; // Za mało MP na ten pasyw w tej turze
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
        $stunChance = max(0, $eq['stun_chance'] ?? 0);

        $dot = null;
        if ($poisonChance > 0 && mt_rand(1, 100) <= $poisonChance) {
            $dot = [
                'type' => 'poison',
                'name' => 'Zatrucie (Ekwipunek)',
                'value' => self::EQUIPMENT_POISON_VALUE,
                'duration' => self::EQUIPMENT_POISON_DURATION,
            ];
        }

        $cc = null;
        if ($stunChance > 0 && mt_rand(1, 100) <= $stunChance) {
            $cc = ['type' => 'stun', 'duration' => self::EQUIPMENT_STUN_DURATION];
        }

        return ['dot' => $dot, 'cc' => $cc];
    }

    private function calculatePlayerDamage(Character $character, $monster, float $diffMultiplier = 1.0): array
    {
        $weaponType = $character->getEquippedWeaponType();
        $statBonus = $character->getAttributeAttackBonus($weaponType);
        $eq = $character->getEquipmentStats();

        $weaponAtkMin = ($eq['attack_min'] ?? 0) + ($eq['magic_attack_min'] ?? 0);
        $weaponAtkMax = ($eq['attack_max'] ?? 0) + ($eq['magic_attack_max'] ?? 0);

        $baseDamageMin = 10 + $statBonus + ($character->level * 1) + $weaponAtkMin;
        $baseDamageMax = 10 + $statBonus + ($character->level * 1) + $weaponAtkMax;

        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }

        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $scaledStats = $monster->getScaledStats($character->level);
        $rawDefense = $scaledStats['def'] ?? ($monster->stats['def'] ?? $monster->level);
        $defense = (int) round($rawDefense * $diffMultiplier);

        $baseDamage = max(1, $damage - ($defense * 0.2));
        $bonusDamage = 0;

        if (isset($monster->type)) {
            $typeStr = strtolower(is_object($monster->type) ? $monster->type->value : $monster->type);
            $bonusKey = 'strong_vs_' . $typeStr;
            $altBonusKey = 'bonus_vs_' . $typeStr;
            $pluralBonusKey = 'strong_vs_' . $typeStr . 's';

            $bonusPercentage = ($eq[$bonusKey] ?? 0) + ($eq[$altBonusKey] ?? 0) + ($eq[$pluralBonusKey] ?? 0);
            if ($bonusPercentage > 0) {
                $bonusDamage = (int)($baseDamage * ($bonusPercentage / 100));
            }
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

    private function calculateMonsterDamage($monster, Character $character, float $diffMultiplier = 1.0): array
    {
        $scaledStats = $monster->getScaledStats($character->level);
        $rawDamage = $scaledStats['atk'] ?? ($monster->stats['atk'] ?? $monster->level * 2);
        $baseDamage = (int) round($rawDamage * $diffMultiplier);
        $vitality = $character->getTotalAttributes()['vit'] ?? 1;
        $eq = $character->getEquipmentStats();
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
            'resist' => (int)$resistDamage,
            'total' => max(1, (int)$damage - $resistDamage)
        ];
    }

    private function rollCritical(Character $character, $monster): bool
    {
        $agility = $character->getTotalAttributes()['agi'] ?? 1;
        $eq = $character->getEquipmentStats();
        $scaledMonsterStats = $monster->getScaledStats($character->level);
        $monsterAgi = $scaledMonsterStats['agi'] ?? ($monster->stats['agi'] ?? 0);

        $baseCrit = 0.05 + ($agility * 0.004) + (($eq['crit_chance'] ?? 0) / 100);
        $agiCritPenalty = max(0, ($monsterAgi - $agility) * 0.0008);
        $critChance = max(0.03, $baseCrit - $agiCritPenalty);

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollMonsterCritical($monster, Character $character): bool
    {
        $scaledMonsterStats = $monster->getScaledStats($character->level);
        $monsterAgi = $scaledMonsterStats['agi'] ?? ($monster->stats['agi'] ?? 0);
        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;

        $baseCrit = 0.03 + ($monsterAgi * 0.003);
        $agiCritPenalty = max(0, ($playerAgi - $monsterAgi) * 0.0008);
        $critChance = max(0.02, min(0.30, $baseCrit - $agiCritPenalty));

        return mt_rand(1, 1000) <= (int)round($critChance * 1000);
    }

    private function rollDodge(int $defenderAgi, int $attackerAgi, float $defenderItemDodge = 0.0): bool
    {
        $agiDodgeAdvantage = max(0, $defenderAgi - $attackerAgi);
        $dodgeChance = 0.03 + ($agiDodgeAdvantage * 0.0015) + ($defenderItemDodge / 100.0);

        return mt_rand(1, 1000) <= (int)round($dodgeChance * 1000);
    }

    public function getChestForDungeon(Dungeon $dungeon): ?ItemTemplate
    {
        $nameMap = [
            'Zapomniane Katakumby' => 'Skrzynia Starych Ruin',
            'Krypta Przeklętych' => 'Skrzynia Jaskini Trolli',
            'Pustkowia Zarazy' => 'Skrzynia Bagien Grozy',
            'Cytadela Cienia' => 'Skrzynia Gór Cienia',
            'Otchłań Zniszczenia' => 'Skrzynia Skażonego Miasta',
        ];

        if (isset($nameMap[$dungeon->name])) {
            $template = ItemTemplate::where('name', $nameMap[$dungeon->name])->first();
            if ($template) return $template;
        }

        return ItemTemplate::where('type', 'consumable')
            ->where('sub_type', 'chest')
            ->where('level_requirement', '<=', $dungeon->min_level ?? 1)
            ->orderBy('level_requirement', 'desc')
            ->first() ?? ItemTemplate::where('type', 'consumable')->where('sub_type', 'chest')->first();
    }

    private function calculateStageLoot(Character $character, $monster, bool $isBossStage = false, ?Dungeon $dungeon = null, int $keyMultiplier = 1): array
    {
        $multiplierService = app(RewardMultiplierService::class);
        
        $baseGold = $monster->stats['gold'] ?? $monster->level * 10;
        $baseXp = ($monster->stats['xp'] ?? $monster->level * 25) * 4;

        // Apply variance
        $baseGold = (int)($baseGold * mt_rand(90, 110) / 100);
        $baseXp = (int)($baseXp * mt_rand(90, 110) / 100);

        $goldMult = $multiplierService->getGoldMultiplier($character);
        $xpMult = $multiplierService->getExpMultiplier($character);
        
        $totalGold = (int)round($baseGold * $goldMult) * $keyMultiplier;
        $totalXp = (int)round($baseXp * $xpMult) * $keyMultiplier;

        $items = [];
        
        // Roll loot table ONLY for boss stages
        if ($isBossStage) {
            // Gwarantowany drop skrzyń (100% szansy na 1-3 sztuki skrzyń * keyMultiplier z bossa)
            if ($dungeon) {
                $chestTemplate = $this->getChestForDungeon($dungeon);
                if ($chestTemplate) {
                    $chestQuantity = mt_rand(1, 3) * $keyMultiplier;
                    $items[] = [
                        'type' => 'item',
                        'name' => $chestTemplate->name,
                        'quantity' => $chestQuantity,
                        'ref_ulid' => $chestTemplate->id,
                        'icon' => $chestTemplate->icon,
                    ];
                }
            }

            // Dodatkowy drop z tabeli zrzutów bossa (jaja, zwoje, materiały)
            if ($monster->lootTable) {
                $entries = $monster->lootTable->entries->toArray();
                if (!empty($entries)) {
                    $picker = app(WeightedPicker::class);
                    $rng = app(RandomProvider::class);
                    
                    $selectedEntry = $picker->pick($entries);
                    if ($selectedEntry) {
                        $quantity = $rng->int($selectedEntry['min_qty'], $selectedEntry['max_qty']) * $keyMultiplier;
                        
                        if ($selectedEntry['reward_type'] === 'gold') {
                            $totalGold += $quantity;
                        } elseif ($selectedEntry['reward_type'] === 'gems') {
                            $items[] = [
                                'type' => 'gems',
                                'name' => 'Klejnoty',
                                'quantity' => $quantity,
                                'ref_ulid' => null,
                                'icon' => null,
                            ];
                        } elseif (in_array($selectedEntry['reward_type'], ['item', 'material'])) {
                            $template = \App\Infrastructure\Persistence\ItemTemplate::find($selectedEntry['ref_ulid']);
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
        }

        return [
            'gold' => $totalGold,
            'xp' => $totalXp,
            'items' => $items,
        ];
    }

    private function grantAccumulatedLoot(CharacterDungeonRun $run): void
    {
        $character = $run->character;
        $loot = $run->accumulated_loot;
        if (!$loot) return;

        DB::transaction(function () use ($character, $loot, $run) {
            $idempotencyKey = "dungeon_run:{$run->id}";
            
            // Grant Gold
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
                    'source_type' => 'dungeon',
                    'source_id' => $run->id,
                    'created_at' => now(),
                ]);
            }

            // Grant XP
            if (($loot['xp'] ?? 0) > 0) {
                $character->increment('xp', $loot['xp']);
                app(\App\Application\Characters\LevelUpService::class)->checkAndApply($character);
            }

            // Grant Items
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
                    
                    $template = \App\Infrastructure\Persistence\ItemTemplate::find($templateUlid);
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
                                'ref_type' => 'dungeon_run',
                                'ref_id' => $run->id,
                                'quantity_change' => $quantity,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}"
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
                                'upgrade_level' => 0
                            ]);
                            
                            ItemLedger::create([
                                'id' => Str::ulid(),
                                'character_id' => $character->id,
                                'item_instance_id' => $instance->id,
                                'action' => 'drop',
                                'ref_type' => 'dungeon_run',
                                'ref_id' => $run->id,
                                'quantity_change' => $quantity,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}"
                            ]);
                        }
                    } else {
                        if ($character->isBackpackFull()) {
                            continue;
                        }
                        // Unstackable items
                        for ($i = 0; $i < $quantity; $i++) {
                            $instance = ItemInstance::create([
                                'id' => Str::ulid(),
                                'template_id' => $templateUlid,
                                'owner_character_id' => $character->id,
                                'location' => 'inventory',
                                'stack_size' => 1,
                                'rarity' => 'common',
                                'roll_stats' => [],
                                'upgrade_level' => 0
                            ]);
                            
                            ItemLedger::create([
                                'id' => Str::ulid(),
                                'character_id' => $character->id,
                                'item_instance_id' => $instance->id,
                                'action' => 'drop',
                                'ref_type' => 'dungeon_run',
                                'ref_id' => $run->id,
                                'quantity_change' => 1,
                                'idempotency_key' => "{$idempotencyKey}:item:{$index}:{$i}"
                            ]);
                        }
                    }
                }
            }
        });
    }
}
