<?php

namespace App\Infrastructure\RNG;

class DeterministicRandomProvider implements RandomProvider
{
    private array $values;
    private int $index = 0;

    public function __construct(array $values)
    {
        $this->values = $values;
    }

    public function int(int $min, int $max): int
    {
        $value = $this->values[$this->index % count($this->values)];
        $this->index++;

        // Scale the predetermined value to the requested range
        return $min + ($value % ($max - $min + 1));
    }

    public function float(float $min = 0.0, float $max = 1.0): float
    {
        $value = $this->values[$this->index % count($this->values)];
        $this->index++;

        return $min + (($value % 1000) / 1000.0) * ($max - $min);
    }
}
