<?php

namespace App\Domain\Characters\Events;

use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CharacterLeveledUp
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Character $character,
        public readonly int $fromLevel,
        public readonly int $toLevel
    ) {}
}
