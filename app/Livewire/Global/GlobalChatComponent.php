<?php

namespace App\Livewire\Global;

use App\Domain\Social\Events\MessageSent;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class GlobalChatComponent extends Component
{
    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    public string $newMessage = '';

    #[Session]
    public bool $isOpen = true;
    public string $currentChannel = 'global';

    public int $unreadGlobalCount = 0;
    public int $unreadGuildCount = 0;

    // Tooltip state: character_id => loaded data
    public array $tooltipData = [];

    public ?string $activeTooltipId = null;

    public function mount(): void
    {
        // Chat is ephemeral — no DB history loaded
    }

    public function getListeners()
    {
        $listeners = [
            'echo:global-chat,.App\\Domain\\Social\\Events\\MessageSent' => 'onMessageReceived',
        ];

        $characterId = session('active_character');
        if ($characterId) {
            $char = Character::find($characterId);
            if ($char && $char->guild_id) {
                $listeners["echo:guild-chat.{$char->guild_id},.App\\Domain\\Social\\Events\\GuildMessageSent"] = 'onGuildMessageReceived';
            }
        }

        return $listeners;
    }

    /**
     * Receive incoming WebSocket events from Echo and push them into local $messages array.
     */
    public function onMessageReceived(array $event): void
    {
        \Illuminate\Support\Facades\Log::info("onMessageReceived called", ['event' => $event]);

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

        if (! $this->isOpen) {
            $this->unreadGlobalCount++;
        }
    }

    public function onGuildMessageReceived(array $event): void
    {
        $this->messages[] = [
            'character_id'    => $event['character_id'],
            'character_name'  => $event['character_name'],
            'character_level' => $event['character_level'],
            'combat_power'    => $event['combat_power'],
            'message'         => $event['message'],
            'sent_at'         => $event['sent_at'],
            'channel'         => 'guild',
        ];

        if (count($this->messages) > 100) {
            array_shift($this->messages);
        }

        if (! $this->isOpen) {
            $this->unreadGuildCount++;
        }
    }

    public function setChannel(string $channel): void
    {
        $this->currentChannel = $channel;
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
        
        if (str_starts_with(strtolower($message), '/donate')) {
            $this->handleDonateCommand($message, $character);
            $this->newMessage = '';
            return;
        }

        if (str_starts_with(strtolower($message), '/give ')) {
            if ($character->user->permission_level == 9) {
                $this->handleGiveCommand($message, $character);
            } else {
                $this->addError('newMessage', 'Brak uprawnień.');
            }
            $this->newMessage = '';
            return;
        }

        if (str_starts_with(strtolower($message), '/exp ')) {
            if ($character->user->permission_level == 9) {
                $this->handleExpCommand($message, $character);
            } else {
                $this->addError('newMessage', 'Brak uprawnień.');
            }
            $this->newMessage = '';
            return;
        }

        $cp = $character->getTotalCombatPower();

        \Illuminate\Support\Facades\Log::info("Sending message", ['character_id' => $character->id, 'channel' => $this->currentChannel, 'message' => $message]);

        if ($this->currentChannel === 'guild' && $character->guild_id) {
            broadcast(new \App\Domain\Social\Events\GuildMessageSent(
                characterName:  $character->name,
                characterLevel: $character->level,
                combatPower:    $cp,
                message:        $message,
                sentAt:         now()->toTimeString(),
                characterId:    $character->id,
                guildId:        $character->guild_id,
            ));
        } else {
            broadcast(new MessageSent(
                characterName:  $character->name,
                characterLevel: $character->level,
                combatPower:    $cp,
                message:        $message,
                sentAt:         now()->toTimeString(),
                characterId:    $character->id,
            ));
        }

        \Illuminate\Support\Facades\Log::info("Message broadcasted");

        $this->newMessage = '';
    }

    /**
     * Load tooltip data for a character on hover (called from Alpine via Livewire action).
     */
    public function loadTooltip(string $characterId): void
    {
        try {
            if (isset($this->tooltipData[$characterId])) {
                $this->activeTooltipId = $characterId;
                return;
            }

            $character = Character::with(['equippedItems.template'])
                ->find($characterId);

            if (! $character) {
                return;
            }

            $equippedItems = $character->equippedItems->map(fn ($item) => [
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
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Tooltip Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        }
    }

    public function closeTooltip(): void
    {
        $this->activeTooltipId = null;
    }

    public function inviteToGuild(string $characterId): void
    {
        $senderId = session('active_character');
        if (!$senderId) return;

        $sender = Character::find($senderId);
        if (!$sender || !$sender->guild_id) {
            // Brak gildii
            return;
        }

        $target = Character::find($characterId);
        if (!$target || $target->guild_id) {
            // Target już ma gildię
            return;
        }

        // Sprawdź czy sender ma permisje (leader / commander)
        $member = \App\Models\GuildMember::where('character_id', $sender->id)->first();
        if (!$member || !in_array($member->role, ['leader', 'commander', 'elder'])) {
            return;
        }

        // Wysyłamy zaproszenie (Phase 9 placeholder logic - actually we can send an in-game Mail!)
        $guild = $sender->guild;
        \App\Infrastructure\Persistence\Mail::create([
            'from_character_id' => $sender->id,
            'to_character_id' => $target->id,
            'subject' => 'Zaproszenie do Gildii: ' . $guild->name,
            'body' => "Gracz {$sender->name} zaprasza Cię do dołączenia do gildii {$guild->name}. \nOdbierz tę wiadomość (załącznik), aby zaakceptować zaproszenie.",
            'attachments' => [['type' => 'guild_invite', 'guild_id' => $guild->id]],
            'claimed' => false,
        ]);
        
        $this->activeTooltipId = null; // close tooltip
        $this->dispatch('notify', message: "Wysłano zaproszenie do gildii dla gracza {$target->name}!", type: 'success');
    }

    private function handleDonateCommand(string $command, Character $character): void
    {
        $parts = explode(' ', strtolower(trim($command)));
        if (count($parts) < 3) {
            $this->addError('newMessage', 'Użycie: /donate <exp|gold|gems> <ilość>');
            return;
        }

        $type = $parts[1];
        $amount = (int) $parts[2];

        if ($amount <= 0) {
            $this->addError('newMessage', 'Ilość musi być większa niż 0.');
            return;
        }

        $guild = $character->guild;
        if (!$guild) {
            $this->addError('newMessage', 'Nie jesteś w gildii.');
            return;
        }

        if ($type === 'exp') {
            if ($character->xp < $amount) {
                $this->addError('newMessage', 'Nie masz tyle expa.');
                return;
            }
            \Illuminate\Support\Facades\DB::transaction(function() use ($character, $guild, $amount) {
                $character->xp -= $amount;
                $character->save();
                $guild->addXp($amount);
                \App\Models\GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $character->id,
                    'action' => 'donate_exp',
                    'amount' => $amount,
                ]);
            });
            $this->broadcastSystemGuildMessage($guild->id, "Gracz {$character->name} przekazał {$amount} EXP na rozwój gildii.");
        } elseif ($type === 'gold') {
            if ($character->gold < $amount) {
                $this->addError('newMessage', 'Nie masz tyle złota.');
                return;
            }
            if ($guild->gold + $amount > $guild->getMaxGold()) {
                $this->addError('newMessage', 'Skarbiec gildii jest pełny.');
                return;
            }
            \Illuminate\Support\Facades\DB::transaction(function() use ($character, $guild, $amount) {
                $character->gold -= $amount;
                $character->save();
                $guild->gold += $amount;
                $guild->save();
                \App\Models\GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $character->id,
                    'action' => 'donate_gold',
                    'amount' => $amount,
                ]);
            });
            $this->broadcastSystemGuildMessage($guild->id, "Gracz {$character->name} wpłacił {$amount} złota do skarbca gildii.");
        } elseif ($type === 'gems') {
            if ($character->user->gems < $amount) {
                $this->addError('newMessage', 'Nie masz tyle diamentów.');
                return;
            }
            if ($guild->gems + $amount > $guild->getMaxGems()) {
                $this->addError('newMessage', 'Skarbiec gildii jest pełny.');
                return;
            }
            \Illuminate\Support\Facades\DB::transaction(function() use ($character, $guild, $amount) {
                $character->user->gems -= $amount;
                $character->user->save();
                $guild->gems += $amount;
                $guild->save();
                \App\Models\GuildLog::create([
                    'guild_id' => $guild->id,
                    'character_id' => $character->id,
                    'action' => 'donate_gems',
                    'amount' => $amount,
                ]);
            });
            $this->broadcastSystemGuildMessage($guild->id, "Gracz {$character->name} wpłacił {$amount} diamentów do skarbca gildii.");
        } else {
            $this->addError('newMessage', 'Nieznany typ donacji (exp, gold, gems).');
        }
    }

    private function broadcastSystemGuildMessage(string $guildId, string $msg): void
    {
        broadcast(new \App\Domain\Social\Events\GuildMessageSent(
            characterName:  'System',
            characterLevel: 0,
            combatPower:    0,
            message:        $msg,
            sentAt:         now()->toTimeString(),
            characterId:    'system',
            guildId:        $guildId,
        ));
    }

    public function toggleChat(): void
    {
        $this->isOpen = ! $this->isOpen;

        if ($this->isOpen) {
            $this->unreadGlobalCount = 0;
            $this->unreadGuildCount = 0;
        }
    }

    public function render()
    {
        return view('livewire.global.global-chat-component');
    }

    private function handleGiveCommand(string $command, Character $character): void
    {
        $parts = explode(' ', trim($command));
        if (count($parts) < 3) {
            $this->addError('newMessage', 'Użycie: /give <item_id|gold|gems> <ilość>');
            return;
        }

        $type = strtolower($parts[1]);
        $amount = (int) $parts[2];

        if ($amount <= 0) {
            $this->addError('newMessage', 'Ilość musi być większa niż 0.');
            return;
        }

        if ($type === 'gold') {
            $character->gold += $amount;
            $character->save();
            $this->dispatch('notify', message: "Dodano {$amount} złota.", type: 'success');
        } elseif ($type === 'gems') {
            $character->user->gems += $amount;
            $character->user->save();
            $this->dispatch('notify', message: "Dodano {$amount} diamentów.", type: 'success');
        } else {
            $template = \App\Infrastructure\Persistence\ItemTemplate::find($parts[1]);
            if (!$template) {
                $this->addError('newMessage', 'Nie znaleziono przedmiotu o podanym ID.');
                return;
            }
            \App\Infrastructure\Persistence\ItemInstance::create([
                'template_id' => $template->id,
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => $amount,
                'rarity' => 'common',
            ]);
            $this->dispatch('notify', message: "Dodano przedmiot {$template->name} ({$amount}x).", type: 'success');
        }
    }

    private function handleExpCommand(string $command, Character $character): void
    {
        $parts = explode(' ', trim($command));
        if (count($parts) < 2) {
            $this->addError('newMessage', 'Użycie: /exp <ilość>');
            return;
        }

        $amount = (int) $parts[1];
        if ($amount <= 0) {
            $this->addError('newMessage', 'Ilość musi być większa niż 0.');
            return;
        }

        $character->xp += $amount;
        $character->save();

        $levelUpService = app(\App\Application\Characters\LevelUpService::class);
        $levelUpResult = $levelUpService->processLevelUps($character);

        $this->dispatch('notify', message: "Dodano {$amount} expa.", type: 'success');

        if ($levelUpResult->hadLevelUp) {
            $this->dispatch('notify', message: "Awansowałeś na poziom {$character->level}!", type: 'success');
        }
    }
}
