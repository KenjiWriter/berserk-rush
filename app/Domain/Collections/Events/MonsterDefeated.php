<?php

namespace App\Domain\Collections\Events;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Monster;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MonsterDefeated
{
    use Dispatchable, SerializesModels;

    public Character $character;
    public Monster $monster;
    public string $mapId;

    public function __construct(Character $character, Monster $monster, string $mapId)
    {
        $this->character = $character;
        $this->monster = $monster;
        $this->mapId = $mapId;
    }
}
