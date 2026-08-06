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
    /**
     * Rebalans 2026-08-06 (zgłoszenie gracza): oryginalne ulepszanie skilli miało
     * 100% szans powodzenia na każdym etapie, więc osiągnięcie P (Perfect) było
     * wyłącznie kwestią czasu/farmienia surowców bez żadnego ryzyka - w połączeniu
     * z bardzo silną krzywą wartości skilla (base_value + scaling*poziom, patrz
     * CharacterCombatSkill::getEffectiveValue()) prowadziło to do zbyt łatwego
     * osiągania maksymalnej mocy. Każdy etap ma teraz szansę porażki: surowce
     * (PKT/książki/kamienie + złoto) są zużywane NIEZALEŻNIE od wyniku rzutu -
     * podobnie jak przy ulepszaniu przedmiotów (UpgradeService::upgradeItem()).
     */
    private const NORMAL_SUCCESS_CHANCE = 85;
    private const MASTER_SUCCESS_CHANCE = 50;
    private const GRAND_MASTER_SUCCESS_CHANCE = 20;

    public function execute(Character $character, CharacterCombatSkill $charSkill): Result
    {
        try {
            return DB::transaction(function () use ($character, $charSkill) {
                if ($charSkill->character_id !== $character->id) {
                    return Result::error('UNAUTHORIZED', 'Nie posiadasz tej umiejętności.');
                }

                $currentLevel = $charSkill->level;

                if ($currentLevel >= 27) {
                    return Result::error('MAX_LEVEL_REACHED', 'Osiągnięto maksymalny poziom tej umiejętności (Perfect P).');
                }

                // 1. Etap Podstawowy (Poziomy 1 -> 6 za Skill Points)
                if ($currentLevel < 6) {
                    $cost = 1;
                    if ($character->skill_points < $cost) {
                        return Result::error('NOT_ENOUGH_POINTS', "Brak punktów umiejętności. Wymagane: {$cost} PKT.");
                    }

                    $character->decrement('skill_points', $cost);

                    if (mt_rand(1, 100) > self::NORMAL_SUCCESS_CHANCE) {
                        return Result::error('UPGRADE_ROLL_FAILED', 'Próba ulepszenia nie powiodła się. Punkt umiejętności został zużyty.', ['consumed' => true]);
                    }

                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level === 6
                        ? 'Umiejętność osiągnęła poziom 6 i awansowała na stopień Mistrza (M1)!'
                        : "Ulepszono umiejętność na poziom {$charSkill->getDisplayLevel()}!";

                    return Result::ok(['message' => $msg]);
                }

                // 2. Etap Mistrza (M1 -> M10: Poziomy 6 -> 15 za Księgi Umiejętności + Gold)
                // Koszt rośnie z każdym stopniem: M1=1 księga, M2=2 księgi, ..., M10=10 ksiąg.
                if ($currentLevel >= 6 && $currentLevel < 16) {
                    $bookCost = $currentLevel - 5;
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

                    $bookItems = ItemInstance::where('owner_character_id', $character->id)
                        ->where('template_id', $bookTpl->id)
                        ->whereIn('location', ['inventory', 'material_stash'])
                        ->where('stack_size', '>', 0)
                        ->get();

                    $ownedBooks = $bookItems->sum('stack_size');
                    if ($ownedBooks < $bookCost) {
                        return Result::error('MISSING_ITEM', "Wymagane {$bookCost}x {$bookName} w ekwipunku (posiadasz {$ownedBooks}).");
                    }

                    // Deduct gold & books (across stacks if needed)
                    $character->decrement('gold', $goldCost);
                    self::consumeStackedItems($bookItems, $bookCost);

                    if (mt_rand(1, 100) > self::MASTER_SUCCESS_CHANCE) {
                        return Result::error('UPGRADE_ROLL_FAILED', "Próba nie powiodła się! Zużyto {$bookCost}x {$bookName} i {$goldCost} złota.", ['consumed' => true]);
                    }

                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level === 16
                        ? "Pomyślnie przeczytano {$bookName}! Skill osiągnął M10 i awansował na Arcymistrza (G1)!"
                        : "Przeczytano {$bookName}! Skill awansował na {$charSkill->getDisplayLevel()}!";

                    return Result::ok(['message' => $msg]);
                }

                // 3. Etap Arcymistrza (G1 -> G10 / P: Poziomy 16 -> 26 za Kamienie Duchowe + Gold)
                // Koszt rośnie liniowo z każdym stopniem: G1=1 kamień, ..., G10->P=5 kamieni.
                if ($currentLevel >= 16 && $currentLevel < 27) {
                    $stoneCost = (int) round(1 + ($currentLevel - 16) * 4 / 9);
                    $goldCost = $currentLevel === 26 ? 10000 : 2500;
                    if ($character->gold < $goldCost) {
                        return Result::error('NOT_ENOUGH_GOLD', "Brak złota. Wymagane: {$goldCost} Gold.");
                    }

                    $stoneTpl = ItemTemplate::where('name', 'Kamień Duchowy')
                        ->orWhere('sub_type', 'soul_stone')
                        ->first();

                    if (!$stoneTpl) {
                        return Result::error('TEMPLATE_NOT_FOUND', 'Nie odnaleziono szablonu Kamienia Duchowego.');
                    }

                    $stoneItems = ItemInstance::where('owner_character_id', $character->id)
                        ->where('template_id', $stoneTpl->id)
                        ->whereIn('location', ['inventory', 'material_stash'])
                        ->where('stack_size', '>', 0)
                        ->get();

                    $ownedStones = $stoneItems->sum('stack_size');
                    if ($ownedStones < $stoneCost) {
                        return Result::error('MISSING_ITEM', "Wymagane {$stoneCost}x Kamień Duchowy w ekwipunku (posiadasz {$ownedStones}).");
                    }

                    // Deduct gold & stones (across stacks if needed)
                    $character->decrement('gold', $goldCost);
                    self::consumeStackedItems($stoneItems, $stoneCost);

                    if (mt_rand(1, 100) > self::GRAND_MASTER_SUCCESS_CHANCE) {
                        return Result::error('UPGRADE_ROLL_FAILED', "Próba nie powiodła się! Zużyto {$stoneCost}x Kamień Duchowy i {$goldCost} złota.", ['consumed' => true]);
                    }

                    $charSkill->increment('level');
                    $charSkill->refresh();

                    $msg = $charSkill->level >= 27
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

    /** Zużywa $quantity sztuk z kolekcji ItemInstance (możliwie wielu stosów), od najmniejszego stosu. */
    private static function consumeStackedItems(\Illuminate\Support\Collection $items, int $quantity): void
    {
        $toDeduct = $quantity;
        foreach ($items->sortBy('stack_size') as $item) {
            if ($toDeduct <= 0) {
                break;
            }

            if ($item->stack_size <= $toDeduct) {
                $toDeduct -= $item->stack_size;
                $item->delete();
            } else {
                $item->stack_size -= $toDeduct;
                $item->save();
                $toDeduct = 0;
            }
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
