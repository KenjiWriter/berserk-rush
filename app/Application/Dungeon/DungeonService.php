<?php

namespace App\Application\Dungeon;

use App\Application\Shared\Result;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Dungeon;
use App\Infrastructure\Persistence\DungeonStage;
use App\Infrastructure\Persistence\CharacterDungeonRun;
use App\Infrastructure\Persistence\ItemInstance;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DungeonService
{
    /**
     * Rozpoczyna nowy run w dungeonie. Wymaga klucza (entry item).
     */
    public function startRun(Character $character, Dungeon $dungeon): Result
    {
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
            $keyItem = ItemInstance::where('owner_character_id', $character->id)
                ->where('template_id', $dungeon->entry_item_template_id)
                ->where('location', 'inventory')
                ->where('stack_size', '>=', 1)
                ->first();

            if (!$keyItem) {
                return Result::error('NO_KEY', 'Nie posiadasz klucza do tego lochu.');
            }

            // Zużyj klucz
            if ($keyItem->stack_size > 1) {
                $keyItem->decrement('stack_size');
            } else {
                $keyItem->delete();
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

        $stage = $run->getCurrentStageModel();
        if (!$stage) {
            return Result::error('NO_STAGE', 'Nie znaleziono etapu.');
        }

        $character = $run->character;
        $monster = $stage->monster;

        if (!$character || !$monster) {
            return Result::error('MISSING_DATA', 'Brak danych do walki.');
        }

        // Symuluj walkę z aktualnym HP gracza (brak regeneracji!)
        $playerHp = $run->current_hp;
        $monsterHp = $monster->stats['hp'] ?? $monster->level * 20;
        $monsterMaxHp = $monsterHp;

        $encounterService = app(EncounterService::class);

        // Używamy wewnętrznej symulacji walki
        $playerAgi = $character->getTotalAttributes()['agi'] ?? 0;
        $monsterAgi = $monster->stats['agi'] ?? $monster->level;
        $playerFirst = $playerAgi >= $monsterAgi;

        $turns = [];
        $turnCount = 0;
        $maxTurns = 50;

        while ($playerHp > 0 && $monsterHp > 0 && $turnCount < $maxTurns) {
            $isPlayerTurn = $playerFirst ? ($turnCount % 2 === 0) : ($turnCount % 2 === 1);

            if ($isPlayerTurn) {
                $damage = $this->calculatePlayerDamage($character, $monster);
                $isCrit = mt_rand(1, 100) <= 10;
                $isMiss = mt_rand(1, 100) <= 5;

                if ($isMiss) {
                    $turns[] = ['actor' => 'player', 'type' => 'miss', 'value' => 0, 'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
                } else {
                    if ($isCrit) $damage = (int)($damage * 1.5);
                    $monsterHp = max(0, $monsterHp - $damage);
                    $turns[] = ['actor' => 'player', 'type' => 'hit', 'value' => $damage, 'crit' => $isCrit, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
                }
            } else {
                $damage = $this->calculateMonsterDamage($monster, $character);
                $isCrit = mt_rand(1, 100) <= 8;
                $isMiss = mt_rand(1, 100) <= 5;

                if ($isMiss) {
                    $turns[] = ['actor' => 'enemy', 'type' => 'miss', 'value' => 0, 'crit' => false, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
                } else {
                    if ($isCrit) $damage = (int)($damage * 1.5);
                    $playerHp = max(0, $playerHp - $damage);
                    $turns[] = ['actor' => 'enemy', 'type' => 'hit', 'value' => $damage, 'crit' => $isCrit, 'playerHp' => $playerHp, 'enemyHp' => $monsterHp];
                }
            }
            $turnCount++;
        }

        $won = $monsterHp <= 0;

        // Zapisz stan HP po walce
        $run->current_hp = $playerHp;

        if (!$won || $playerHp <= 0) {
            $run->is_failed = true;
            $run->save();

            return Result::ok([
                'turns' => $turns,
                'result' => 'loss',
                'stage' => $run->current_stage,
                'player_hp' => $playerHp,
                'monster_max_hp' => $monsterMaxHp,
            ]);
        }

        // Sprawdź czy to ostatni etap
        $totalStages = $run->dungeon->stages()->count();
        if ($run->current_stage >= $totalStages) {
            $run->is_completed = true;
            $run->save();

            return Result::ok([
                'turns' => $turns,
                'result' => 'dungeon_complete',
                'stage' => $run->current_stage,
                'player_hp' => $playerHp,
                'monster_max_hp' => $monsterMaxHp,
            ]);
        }

        // Przejdź do następnego etapu
        $run->current_stage++;
        $run->save();

        return Result::ok([
            'turns' => $turns,
            'result' => 'stage_clear',
            'stage' => $run->current_stage - 1,
            'next_stage' => $run->current_stage,
            'player_hp' => $playerHp,
            'monster_max_hp' => $monsterMaxHp,
        ]);
    }

    /**
     * Użycie mikstury w lochu - leczy tyle ile mikstura leczy.
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

        // Sprawdź czy to mikstura/consumable
        $template = $potion->template;
        if (!$template || $template->type !== 'consumable') {
            return Result::error('NOT_CONSUMABLE', 'Ten przedmiot nie jest miksturą.');
        }

        $healAmount = $template->base_stats['heal'] ?? 50;
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

    private function calculatePlayerDamage(Character $character, $monster): int
    {
        $strength = $character->getTotalAttributes()['str'] ?? 1;
        $eq = $character->getEquipmentStats();

        $baseDamageMin = 10 + ($strength * 2) + ($character->level * 1) + $eq['attack_min'];
        $baseDamageMax = 10 + ($strength * 2) + ($character->level * 1) + $eq['attack_max'];

        if ($baseDamageMax < $baseDamageMin) {
            $baseDamageMax = $baseDamageMin;
        }

        $damage = mt_rand($baseDamageMin, $baseDamageMax);
        $defense = $monster->stats['def'] ?? $monster->level;

        return max(1, $damage - ($defense / 2));
    }

    private function calculateMonsterDamage($monster, Character $character): int
    {
        $baseDamage = $monster->stats['atk'] ?? $monster->level * 2;
        $vitality = $character->getTotalAttributes()['vit'] ?? 1;
        $eq = $character->getEquipmentStats();
        $defense = $vitality + ($character->level / 2) + $eq['defense'];

        return max(1, $baseDamage - ($defense / 2));
    }
}
