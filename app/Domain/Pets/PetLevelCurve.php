<?php

namespace App\Domain\Pets;

/**
 * Krzywa wymaganego EXP na poziom peta. Silniejsze pety (wyższy `fusion_count`,
 * czyli powstałe z fuzji) wymagają proporcjonalnie więcej EXP, żeby zniechęcić
 * do fuzjonowania świeżo wyklutych, niewykarmionych petów.
 */
class PetLevelCurve
{
    public static function requiredExp(int $level, int $fusionCount = 0): int
    {
        $base = (int) config('pets.exp_per_level_base', 100);
        $multiplier = PetStatCalculator::fusionMultiplier($fusionCount);

        return (int) round(max(1, $level) * $base * $multiplier);
    }
}
