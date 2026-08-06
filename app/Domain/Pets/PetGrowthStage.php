<?php

namespace App\Domain\Pets;

/**
 * Etapy dojrzewania peta (0-3, progi w `config('pets.growth_stage_thresholds')`
 * - domyślnie poziomy 1/10/25/50), niezależne od tieru. Stage 3 = "Forma
 * Dorosła", wymagana do maksymalnego bonusu przy fuzji. Każdy etap daje też
 * realny mnożnik puli staty (`config('pets.growth_stage_stat_multiplier')`,
 * patrz PetStatCalculator::totalPool()), nie tylko zmianę grafiki/nazwy.
 * 3 realne warianty grafiki (baby/medium/adult) pokrywają 4 wewnętrzne
 * stopnie (1 i 2 dzielą wariant "medium") - patrz spriteVariant().
 */
class PetGrowthStage
{
    public const MAX_STAGE = 3;

    public static function forLevel(int $level): int
    {
        $thresholds = config('pets.growth_stage_thresholds', []);
        $stage = 0;

        foreach ($thresholds as $candidateStage => $minLevel) {
            if ($level >= $minLevel) {
                $stage = max($stage, (int) $candidateStage);
            }
        }

        return min($stage, self::MAX_STAGE);
    }

    public static function spriteVariant(int $stage): string
    {
        return match (true) {
            $stage <= 0 => 'baby',
            $stage >= self::MAX_STAGE => 'adult',
            default => 'medium',
        };
    }

    public static function label(int $stage): string
    {
        return match (true) {
            $stage <= 0 => 'Pisklak',
            $stage === 1 => 'Podrostek',
            $stage >= self::MAX_STAGE => 'Forma Dorosła',
            default => 'Okrzepły',
        };
    }

    public static function statMultiplier(int $stage): float
    {
        $multipliers = config('pets.growth_stage_stat_multiplier', []);

        return (float) ($multipliers[$stage] ?? 1.0);
    }
}
