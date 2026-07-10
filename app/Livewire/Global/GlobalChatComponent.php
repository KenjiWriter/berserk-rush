<?php

namespace App\Livewire\Global;

use App\Domain\Social\Events\MessageSent;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Component;

class GlobalChatComponent extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $newMessage = '';

    public bool $isOpen = true;

    // Tooltip state: character_id => loaded data
    public array $tooltipData = [];

    public ?string $activeTooltipId = null;

    public function mount(): void
    {
        // Chat is ephemeral — no DB history loaded
    }

    /**
     * Receive incoming WebSocket events from Echo and push them into local $messages array.
     */
    #[On('echo:global-chat,MessageSent')]
    public function onMessageReceived(array $event): void
    {
        $this->messages[] = [
            'character_id'    => $event['character_id'],
            'character_name'  => $event['character_name'],
            'character_level' => $event['character_level'],
            'combat_power'    => $event['combat_power'],
            'message'         => $event['message'],
            'sent_at'         => $event['sent_at'],
        ];

        // Keep at most 100 messages in memory per browser session
        if (count($this->messages) > 100) {
            array_shift($this->messages);
        }
    }

    public function sendMessage(): void
    {
        $user = Auth::user();

        if (! $user) {
            return;
        }

        $characterId = session('active_character');
        if (! $characterId) {
            return;
        }

        $this->validate([
            'newMessage' => 'required|string|min:1|max:200',
        ]);

        $character = Character::find($characterId);
        if (! $character || $character->user_id !== $user->id) {
            return;
        }

        // Rate-limit: 1 message per 2 seconds per character
        $key = 'chat:' . $character->id;

        if (RateLimiter::tooManyAttempts($key, maxAttempts: 1)) {
            $this->addError('newMessage', 'Zbyt szybko! Poczekaj chwilę przed kolejną wiadomością.');
            return;
        }

        RateLimiter::hit($key, decaySeconds: 2);

        $message = trim($this->newMessage);
        $cp      = $character->getTotalCombatPower();

        broadcast(new MessageSent(
            characterName:  $character->name,
            characterLevel: $character->level,
            combatPower:    $cp,
            message:        $message,
            sentAt:         now()->toTimeString(),
            characterId:    $character->id,
        ));

        $this->newMessage = '';
    }

    /**
     * Load tooltip data for a character on hover (called from Alpine via Livewire action).
     */
    public function loadTooltip(string $characterId): void
    {
        if (isset($this->tooltipData[$characterId])) {
            // Already loaded — just activate
            $this->activeTooltipId = $characterId;
            return;
        }

        $character = Character::with(['items' => fn ($q) => $q->where('location', 'equipped')->with('template')])
            ->select(['id', 'name', 'level'])
            ->find($characterId);

        if (! $character) {
            return;
        }

        $equippedItems = $character->items->map(fn ($item) => [
            'name'          => $item->template?->name ?? 'Nieznany',
            'slot'          => $item->template?->slot ?? '?',
            'upgrade_level' => $item->upgrade_level,
            'rarity'        => $item->rarity,
            'combat_power'  => $item->getCombatPower(),
        ])->values()->toArray();

        $this->tooltipData[$characterId] = [
            'name'          => $character->name,
            'level'         => $character->level,
            'combat_power'  => $character->getTotalCombatPower(),
            'equipped_items' => $equippedItems,
        ];

        $this->activeTooltipId = $characterId;
    }

    public function closeTooltip(): void
    {
        $this->activeTooltipId = null;
    }

    public function toggleChat(): void
    {
        $this->isOpen = ! $this->isOpen;
    }

    public function render()
    {
        return view('livewire.global.global-chat-component');
    }
}
