<?php

namespace App\Application\Pets;

use App\Application\Shared\Result;
use App\Domain\Pets\PetLevelCurve;
use App\Domain\Pets\PetTier;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\Pet;
use Illuminate\Support\Facades\DB;

class PetFeedingService
{
    /**
     * Oblicza punkty doświadczenia (EXP) przyznawane przez dany przedmiot dla peta.
     */
    public function calculateItemExp(ItemInstance $item): int
    {
        $baseExp = max(1, $item->template->level_requirement ?? 1);
        $rarityMultiplier = match ($item->rarity ?? 'common') {
            'legendary' => 3.0,
            'epic' => 2.0,
            'rare' => 1.5,
            'uncommon' => 1.25,
            default => 1.0,
        };

        return (int) round($baseExp * $rarityMultiplier);
    }

    /**
     * Karmienie peta przedmiotami z plecaka. Każdy wybrany przedmiot musi
     * mieścić się w przedziale poziomowym akceptowanym przez tier peta
     * (patrz PetTier::feedLevelRange) - w przeciwnym razie cała operacja jest
     * odrzucana (żeby uniknąć niejednoznacznego częściowego skarmienia).
     */
    public function feedPet(Character $character, int $petId, array $itemInstanceIds): Result
    {
        return DB::transaction(function () use ($character, $petId, $itemInstanceIds) {
            $pet = Pet::where('id', $petId)->where('character_id', $character->id)->first();
            if (!$pet) {
                return Result::error('NO_PET', 'Nie znaleziono wskazanego chowańca.');
            }

            if (empty($itemInstanceIds)) {
                return Result::error('NO_ITEMS', 'Wybierz co najmniej jeden przedmiot z plecaka.');
            }

            $items = ItemInstance::with('template')
                ->whereIn('id', $itemInstanceIds)
                ->where('owner_character_id', $character->id)
                ->where('location', 'inventory')
                ->get();

            if ($items->isEmpty()) {
                return Result::error('NO_ITEMS_FOUND', 'Nie znaleziono wybranych przedmiotów w Twoim plecaku.');
            }

            [$minLevel, $maxLevel] = PetTier::feedLevelRange($pet->tier);
            foreach ($items as $item) {
                $itemLevel = (int) ($item->template->level_requirement ?? 1);
                if (!PetTier::isItemLevelAccepted($pet->tier, $itemLevel)) {
                    $rangeLabel = $maxLevel === null ? "{$minLevel}+" : "{$minLevel}-{$maxLevel}";
                    return Result::error(
                        'ITEM_OUT_OF_RANGE',
                        "{$item->template->name} (poz. {$itemLevel}) nie mieści się w przedziale poziomowym karmienia dla tego chowańca ({$rangeLabel})."
                    );
                }
            }

            $totalGainedExp = 0;
            $consumedCount = 0;

            foreach ($items as $item) {
                $expVal = $this->calculateItemExp($item);

                if (($item->stack_size ?? 1) > 1) {
                    $item->decrement('stack_size');
                } else {
                    $item->delete();
                }

                $totalGainedExp += $expVal;
                $consumedCount++;
            }

            $currentLevel = $pet->level;
            $currentExp = $pet->exp + $totalGainedExp;

            while (true) {
                $requiredExp = PetLevelCurve::requiredExp($currentLevel, $pet->fusion_count);
                if ($currentExp >= $requiredExp) {
                    $currentExp -= $requiredExp;
                    $currentLevel++;
                } else {
                    break;
                }
            }

            $oldLevel = $pet->level;
            $leveledUp = $currentLevel > $oldLevel;

            $pet->level = $currentLevel;
            $pet->exp = $currentExp;
            $pet->recalculateStats();
            $pet->save();

            if ($pet->is_equipped) {
                $character->clearStatsCache();
            }

            return Result::ok([
                'pet' => $pet->fresh(),
                'gainedExp' => $totalGainedExp,
                'leveledUp' => $leveledUp,
                'oldLevel' => $oldLevel,
                'newLevel' => $currentLevel,
                'consumedCount' => $consumedCount,
            ]);
        });
    }
}
