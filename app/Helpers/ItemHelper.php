<?php

namespace App\Helpers;

class ItemHelper
{
    /**
     * Zwraca klasy Tailwind dla obramowania i cienia przedmiotu w zależności od jego rzadkości/jakości.
     */
    public static function getRarityBorderClass(?string $rarity, bool $isEquipped = false): string
    {
        $rarity = strtolower($rarity ?? 'common');

        return match ($rarity) {
            'uncommon' => 'border-emerald-500 shadow-[0_0_10px_rgba(16,185,129,0.35)] hover:border-emerald-400',
            'rare' => 'border-sky-400 shadow-[0_0_12px_rgba(56,189,248,0.4)] hover:border-sky-300',
            'epic' => 'border-purple-500 shadow-[0_0_14px_rgba(168,85,247,0.45)] hover:border-purple-400',
            'legendary' => 'border-amber-400 shadow-[0_0_16px_rgba(245,158,11,0.55)] hover:border-amber-300',
            default => 'border-slate-600/90 shadow-sm hover:border-slate-400',
        };
    }

    /**
     * Sprawdza, czy dany przedmiot jest zaczarowany (posiada zaklęcia w roll_stats).
     */
    public static function isEnchanted(mixed $item): bool
    {
        if (!$item) {
            return false;
        }

        $rollStats = [];
        if (is_object($item)) {
            $rollStats = $item->roll_stats ?? [];
        } elseif (is_array($item)) {
            $rollStats = $item['roll_stats'] ?? [];
        }

        if (is_string($rollStats)) {
            $rollStats = json_decode($rollStats, true) ?? [];
        }

        $enchants = $rollStats['enchants'] ?? [];
        return is_array($enchants) && count($enchants) > 0;
    }

    /**
     * Zwraca przesunięty styl animation-delay (w sekundach) dla zsynchronizowania rozbłysków na kafelkach.
     */
    public static function getAnimationDelayStyle(mixed $key = null): string
    {
        $idStr = is_object($key) ? ($key->id ?? json_encode($key)) : (string) $key;
        $hash = abs(crc32($idStr));
        $delaySeconds = round(($hash % 75) / 10, 1); // 0.0s do 7.4s
        return "style=\"animation-delay: {$delaySeconds}s; --shimmer-delay: {$delaySeconds}s;\"";
    }
}
