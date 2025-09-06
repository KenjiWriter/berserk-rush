<?php

namespace App\Domain\Characters\Events;

use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterLeveledUp
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Character $character,
        public int $fromLevel,
        public int $toLevel
    ) {}
}
