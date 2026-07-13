<?php

namespace App\Domain\Achievements\Events;

use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AchievementProgressed
{
    use Dispatchable, SerializesModels;

    public Character $character;
    public string $type;
    public int $amount;
    public array $context;

    public function __construct(Character $character, string $type, int $amount = 1, array $context = [])
    {
        $this->character = $character;
        $this->type = $type;
        $this->amount = $amount;
        $this->context = $context;
    }
}
