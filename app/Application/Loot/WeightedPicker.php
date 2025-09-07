<?php

namespace App\Application\Loot;

use App\Infrastructure\RNG\RandomProvider;

class WeightedPicker
{
    public function __construct(
        private RandomProvider $rng
    ) {}

    public function pick(array $items, string $weightKey = 'weight'): mixed
    {
        if (empty($items)) {
            return null;
        }

        $totalWeight = array_sum(array_column($items, $weightKey));
        if ($totalWeight <= 0) {
            return $items[array_rand($items)];
        }

        $randomWeight = $this->rng->int(1, $totalWeight);
        $currentWeight = 0;

        foreach ($items as $item) {
            $currentWeight += $item[$weightKey];
            if ($randomWeight <= $currentWeight) {
                return $item;
            }
        }

        // Fallback to last item
        return end($items);
    }
}
