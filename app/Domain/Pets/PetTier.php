<?php

namespace App\Domain\Pets;

/**
 * Metadane 6 tierów peta (Pospolity..Legendarny), czytane wyłącznie z
 * config/pets.php, żeby balans miał jedno źródło prawdy.
 */
class PetTier
{
    public const MIN_TIER = 1;
    public const MAX_TIER = 6;

    public static function name(int $tier): string
    {
        return config("pets.tiers.{$tier}.name", 'Nieznany');
    }

    public static function slug(int $tier): string
    {
        return config("pets.tiers.{$tier}.slug", 't1');
    }

    public static function hatchHours(int $tier): float
    {
        return (float) config("pets.tiers.{$tier}.hatch_hours", 1);
    }

    public static function levelNorm(int $tier): float
    {
        return (float) config("pets.tiers.{$tier}.level_norm", 1.0);
    }

    /**
     * Minimalny wymagany poziom przedmiotu akceptowanego przy karmieniu - bez
     * górnej granicy, więc mocniejszy (wyższy poziom) przedmiot zawsze można
     * skarmić petu niższego tieru.
     */
    public static function feedLevelMin(int $tier): int
    {
        return (int) config("pets.tiers.{$tier}.feed_level_min", 0);
    }

    public static function isItemLevelAccepted(int $tier, int $itemLevelRequirement): bool
    {
        return $itemLevelRequirement >= self::feedLevelMin($tier);
    }

    public static function canFuse(int $tier): bool
    {
        return $tier >= self::MIN_TIER && $tier < self::MAX_TIER;
    }

    public static function isValid(int $tier): bool
    {
        return $tier >= self::MIN_TIER && $tier <= self::MAX_TIER;
    }

    /**
     * Wszystkie tiery jako [tier => metadane] - używane przez panel informacyjny w UI.
     */
    public static function all(): array
    {
        return config('pets.tiers', []);
    }
}
