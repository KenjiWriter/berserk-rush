<?php

namespace App\Domain\Collections\Events;

use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemDiscovered
{
    use Dispatchable, SerializesModels;

    public Character $character;
    public string $itemTemplateId;

    public function __construct(Character $character, string $itemTemplateId)
    {
        $this->character = $character;
        $this->itemTemplateId = $itemTemplateId;
    }
}
