<?php

namespace App\Domain\Mastery\Events;

use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChampionLeveledUp
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Character $character,
        public readonly int $newChampionLevel
    ) {}
}
