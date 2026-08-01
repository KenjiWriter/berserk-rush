<?php

namespace App\Domain\Pets;

/**
 * Reguły Fuzji: 2 pety tego samego tieru -> 1 pet o tier wyższy.
 * Szansa sukcesu = baza per tier + bonus za sumaryczną "dojrzałość"
 * (growth_stage) obu łączonych petów.
 */
class PetFusionRules
{
    public static function canFuse(int $tier): bool
    {
        return PetTier::canFuse($tier);
    }

    public static function resultTier(int $tier): ?int
    {
        return self::canFuse($tier) ? $tier + 1 : null;
    }

    public static function baseChance(int $tier): float
    {
        return (float) (config("pets.fusion_base_chance.{$tier}") ?? 0);
    }

    public static function growthStageBonus(int $growthStageA, int $growthStageB): float
    {
        $bonusPerStage = (float) config('pets.fusion_growth_stage_bonus_percent', 0);
        return ($growthStageA + $growthStageB) * $bonusPerStage;
    }

    /**
     * Całkowita szansa sukcesu (%), przycięta do [0, 100].
     */
    public static function successChance(int $tier, int $growthStageA, int $growthStageB): float
    {
        $chance = self::baseChance($tier) + self::growthStageBonus($growthStageA, $growthStageB);
        return max(0.0, min(100.0, $chance));
    }
}
