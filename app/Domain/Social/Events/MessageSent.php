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
        public readonly bool $isPremium = false,
        public readonly bool $isAdmin = false,
        public readonly bool $isModerator = false,
        // True when this message originated from the linked Discord channel
        // (see App\Console\Commands\DiscordChatBridgeCommand) rather than from
        // the in-game chat UI. Listeners that relay messages OUT to Discord
        // (ForwardGlobalChatMessageToDiscord) must skip these to avoid echoing
        // a message back to the very channel it came from.
        public readonly bool $fromDiscord = false,
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
            'is_premium'      => $this->isPremium,
            'is_admin'        => $this->isAdmin,
            'is_moderator'    => $this->isModerator,
            'from_discord'    => $this->fromDiscord,
        ];
    }
}
