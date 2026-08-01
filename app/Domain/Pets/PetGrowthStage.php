<?php

namespace App\Domain\Pets;

/**
 * Wizualne/mechaniczne etapy dojrzewania peta (0-3), niezależne od tieru.
 * Stage 3 = "Forma Dorosła", wymagana do maksymalnego bonusu przy fuzji.
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
            $stage >= self::MAX_STAGE => 'Forma Dorosła',
            default => 'Podrostek',
        };
    }
}
