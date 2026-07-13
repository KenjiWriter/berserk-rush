<?php

namespace App\Domain\Social\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GuildMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $characterName,
        public int $characterLevel,
        public int $combatPower,
        public string $message,
        public string $sentAt,
        public string $characterId,
        public string $guildId,
        public ?string $titlePrefix = null,
        public bool $isPremium = false
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('guild-chat.' . $this->guildId),
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
            'guild_id'        => $this->guildId,
            'title_prefix'    => $this->titlePrefix,
            'is_premium'      => $this->isPremium,
        ];
    }
}
