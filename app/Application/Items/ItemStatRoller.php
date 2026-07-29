<?php

namespace App\Application\Items;

use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\RNG\RandomProvider;

class ItemStatRoller
{
    public function __construct(private RandomProvider $rng) {}

    /**
     * Rolls a concrete value for every ranged stat ([min, max]) on the template's
     * base_stats, and derives the item's rarity from how close the roll landed
     * to the maximum across all ranged stats.
     *
     * @return array{rolled_stats: array<string, int>, quality: float, rarity: string}
     */
    public function roll(ItemTemplate $template): array
    {
        $baseStats = $template->base_stats ?? [];
        $rolledStats = [];
        $percentiles = [];

        foreach ($baseStats as $stat => $value) {
            if (!is_array($value) || count($value) < 2) {
                continue;
            }

            $min = (int) $value[0];
            $max = (int) $value[1];

            if ($max > $min) {
                $rolled = $this->rng->int($min, $max);
                $percentiles[] = ($rolled - $min) / ($max - $min);
            } else {
                $rolled = $min;
                $percentiles[] = 1.0;
            }

            $rolledStats[$stat] = $rolled;
        }

        $quality = empty($percentiles) ? 0.0 : array_sum($percentiles) / count($percentiles);

        return [
            'rolled_stats' => $rolledStats,
            'quality' => $quality,
            'rarity' => $this->rarityFromQuality($quality),
        ];
    }

    public function rarityFromQuality(float $quality): string
    {
        return match (true) {
            $quality >= 0.95 => 'legendary',
            $quality >= 0.80 => 'epic',
            $quality >= 0.60 => 'rare',
            $quality >= 0.35 => 'uncommon',
            default => 'common',
        };
    }
}
