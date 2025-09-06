<?php

namespace App\Application\Characters\DTOs;

readonly class LevelUpResult
{
    public function __construct(
        public array $levelUps,
        public int $newLevel,
        public int $pointsGained,
        public bool $hadLevelUp
    ) {}
}
