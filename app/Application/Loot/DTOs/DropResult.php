<?php

namespace App\Application\Loot\DTOs;

class DropResult
{
    public function __construct(
        public readonly int $gold,
        public readonly int $gems,
        public readonly array $items,
        public readonly array $materials,
        public readonly bool $hadDrops
    ) {}

    public function toArray(): array
    {
        return [
            'gold' => $this->gold,
            'gems' => $this->gems,
            'items' => $this->items,
            'materials' => $this->materials,
            'had_drops' => $this->hadDrops
        ];
    }
}
