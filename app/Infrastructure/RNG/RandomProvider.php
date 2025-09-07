<?php

namespace App\Infrastructure\RNG;

interface RandomProvider
{
    public function int(int $min, int $max): int;
    public function float(float $min = 0.0, float $max = 1.0): float;
}
