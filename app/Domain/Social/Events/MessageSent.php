<?php

namespace App\Domain\Social\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $characterName,
        public readonly int    $characterLevel,
        public readonly int    $combatPower,
        public readonly string $message,
        public readonly string $sentAt,
        public readonly string $characterId,
        public readonly ?string $titlePrefix = null,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('global-chat'),
        ];
    }



    public function broadcastWith(): array
    {
        return [
            'character_id'    => $this->characterId,
            'character_name'  => $this->characterName,
            'character_level' => $this->characterLevel,
            'combat_power'    => $this->combatPower,
            'message'         => $this->message,
            'sent_at'         => $this->sentAt,
            'title_prefix'    => $this->titlePrefix,
        ];
    }
}
