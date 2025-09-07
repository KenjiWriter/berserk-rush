<?php

namespace App\Infrastructure\RNG;

class DefaultRandomProvider implements RandomProvider
{
    public function int(int $min, int $max): int
    {
        return random_int($min, $max);
    }

    public function float(float $min = 0.0, float $max = 1.0): float
    {
        return $min + (mt_rand() / mt_getrandmax()) * ($max - $min);
    }
}
