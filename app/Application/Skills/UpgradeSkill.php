<?php

namespace App\Application\Skills;

use App\Application\Shared\Result;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Support\Facades\DB;

class UpgradeSkill
{
    public function execute(Character $character, CharacterCombatSkill $charSkill): Result
    {
        try {
            return DB::transaction(function () use ($character, $charSkill) {
                if ($charSkill->character_id !== $character->id) {
                    return Result::error('UNAUTHORIZED', 'Nie posiadasz tej umiejętności.');
                }

                $currentLevel = $charSkill->level;

                if ($currentLevel >= 38) {
                    return Result::error('MAX_LEVEL_REACHED', 'Osiągnięto maksymalny poziom tej umiejętności (Perfect P).');
                }

                // 1. Etap Podstawowy (Poziomy 1 -> 17 za Skill Points)
                if ($currentLevel < 17) {
                    $cost = 1;
                    if ($character->skill_points < $cost) {
                        return Result::error('NOT_ENOUGH_POINTS', "Brak punktów umiejętności. Wymagane: {$cost} PKT.");
                    }

                    $character->decrement('skill_points', $cost);
                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level === 17
                        ? 'Umiejętność osiągnęła poziom 17 i awansowała na stopień Mistrza (M1)!'
                        : "Ulepszono umiejętność na poziom {$charSkill->getDisplayLevel()}!";

                    return Result::ok(['message' => $msg]);
                }

                // 2. Etap Mistrza (M1 -> M10: Poziomy 17 -> 26 za dedykowaną Księgę Umiejętności + Gold)
                if ($currentLevel >= 17 && $currentLevel < 27) {
                    $goldCost = 500;
                    if ($character->gold < $goldCost) {
                        return Result::error('NOT_ENOUGH_GOLD', "Brak złota. Wymagane: {$goldCost} Gold.");
                    }

                    $reqWeapon = $charSkill->skill->required_weapon_type ?? 'all';
                    $bookSubType = self::getRequiredBookSubType($reqWeapon);
                    $bookName = self::getRequiredBookName($reqWeapon);

                    $bookTpl = ItemTemplate::where('sub_type', $bookSubType)
                        ->orWhere('name', $bookName)
                        ->first();

                    if (!$bookTpl) {
                        return Result::error('TEMPLATE_NOT_FOUND', "Nie odnaleziono szablonu dla: {$bookName}.");
                    }

                    $bookItem = ItemInstance::where('owner_character_id', $character->id)
                        ->where('template_id', $bookTpl->id)
                        ->whereIn('location', ['inventory', 'material_stash'])
                        ->where('stack_size', '>', 0)
                        ->first();

                    if (!$bookItem) {
                        return Result::error('MISSING_ITEM', "Wymagana 1x {$bookName} w ekwipunku.");
                    }

                    // Deduct gold & item
                    $character->decrement('gold', $goldCost);
                    if ($bookItem->stack_size > 1) {
                        $bookItem->decrement('stack_size');
                    } else {
                        $bookItem->delete();
                    }

                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level === 27
                        ? "Pomyślnie przeczytano {$bookName}! Skill osiągnął M10 i awansował na Arcymistrza (G1)!"
                        : "Przeczytano {$bookName}! Skill awansował na {$charSkill->getDisplayLevel()}!";

                    return Result::ok(['message' => $msg]);
                }

                // 3. Etap Arcymistrza (G1 -> G10 / P: Poziomy 27 -> 37 za Kamień Duchowy + Gold)
                if ($currentLevel >= 27 && $currentLevel < 38) {
                    $goldCost = $currentLevel === 37 ? 10000 : 2500;
                    if ($character->gold < $goldCost) {
                        return Result::error('NOT_ENOUGH_GOLD', "Brak złota. Wymagane: {$goldCost} Gold.");
                    }

                    $stoneTpl = ItemTemplate::where('name', 'Kamień Duchowy')
                        ->orWhere('sub_type', 'soul_stone')
                        ->first();

                    if (!$stoneTpl) {
                        return Result::error('TEMPLATE_NOT_FOUND', 'Nie odnaleziono szablonu Kamienia Duchowego.');
                    }

                    $stoneItem = ItemInstance::where('owner_character_id', $character->id)
                        ->where('template_id', $stoneTpl->id)
                        ->whereIn('location', ['inventory', 'material_stash'])
                        ->where('stack_size', '>', 0)
                        ->first();

                    if (!$stoneItem) {
                        return Result::error('MISSING_ITEM', 'Wymagany 1x Kamień Duchowy w ekwipunku.');
                    }

                    // Deduct gold & item
                    $character->decrement('gold', $goldCost);
                    if ($stoneItem->stack_size > 1) {
                        $stoneItem->decrement('stack_size');
                    } else {
                        $stoneItem->delete();
                    }

                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level >= 38
                        ? 'Niesamowite! Kamień Duchowy obudził potęgę - umiejętność osiągnęła poziom PERFECT (P)!'
                        : "Użyto Kamienia Duchowego! Skill awansował na {$charSkill->getDisplayLevel()}!";

                    return Result::ok(['message' => $msg]);
                }

                return Result::error('UPGRADE_FAILED', 'Nieznany stan ulepszenia.');
            });
        } catch (\Exception $e) {
            return Result::error('UPGRADE_FAILED', 'Nie udało się ulepszyć umiejętności: ' . $e->getMessage());
        }
    }

    public static function getRequiredBookSubType(string $weaponType): string
    {
        return match ($weaponType) {
            'sword' => 'skill_book_sword',
            'axe' => 'skill_book_axe',
            'bow' => 'skill_book_bow',
            'wand' => 'skill_book_wand',
            'bell' => 'skill_book_bell',
            'dagger' => 'skill_book_dagger',
            default => 'skill_book_all',
        };
    }

    public static function getRequiredBookName(string $weaponType): string
    {
        return match ($weaponType) {
            'sword' => 'Księga Walki Mieczem',
            'axe' => 'Księga Sztuki Topora',
            'bow' => 'Księga Łucznictwa',
            'wand' => 'Księga Tajemnic Różdżki',
            'bell' => 'Księga Magii Dzwonu',
            'dagger' => 'Księga Mistrzostwa Sztyletów',
            default => 'Księga Ogólnych Technik',
        };
    }
}
