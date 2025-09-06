<?php

namespace App\Application\Combat\DTOs;

readonly class CombatResult
{
    public function __construct(
        public array $turns,
        public string $winner,
        public int $gold,
        public int $xp,
        public int $playerStartHp,
        public int $enemyStartHp,
        public bool $playerFirst
    ) {}
}
