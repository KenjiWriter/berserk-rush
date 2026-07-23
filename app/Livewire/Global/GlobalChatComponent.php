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
    #[Session]
    public array $messages = [];

    public string $newMessage = '';

    #[Session]
    public bool $isOpen = true;
    public string $currentChannel = 'global';

    #[Session]
    public int $unreadGlobalCount = 0;

    #[Session]
    public int $unreadGuildCount = 0;

    // Tooltip state: character_id => loaded data
    public array $tooltipData = [];

    public ?string $activeTooltipId = null;

    public function mount(): void
    {
        // Purge messages older than 10 minutes from session
        $tenMinutesAgo = now()->subMinutes(10)->timestamp;

        $this->messages = array_filter($this->messages, function ($msg) use ($tenMinutesAgo) {
            return ($msg['received_at'] ?? 0) >= $tenMinutesAgo;
        });

        // Reindex array
        $this->messages = array_values($this->messages);
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
            'title_prefix'    => $event['title_prefix'] ?? null,
            'is_premium'      => $event['is_premium'] ?? false,
            'received_at'     => now()->timestamp,
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
            'title_prefix'    => $event['title_prefix'] ?? null,
            'is_premium'      => $event['is_premium'] ?? false,
            'received_at'     => now()->timestamp,
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
                $lowerMsg = strtolower($message);
                if ($character->guild_id && (str_contains($lowerMsg, ' exp') || str_contains($lowerMsg, ' gold') || str_contains($lowerMsg, ' gems'))) {
                    $this->handleDonateCommand(str_replace('/give', '/donate', $message), $character);
                } else {
                    $this->addError('newMessage', 'Brak uprawnień.');
                }
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

        if (str_starts_with(strtolower($message), '/set ')) {
            if ($character->user->permission_level == 9) {
                $this->handleSetCommand($message, $character);
            } else {
                $this->addError('newMessage', 'Brak uprawnień.');
            }
            $this->newMessage = '';
            return;
        }

        $cp = $character->getTotalCombatPower();

        \Illuminate\Support\Facades\Log::info("Sending message", ['character_id' => $character->id, 'channel' => $this->currentChannel, 'message' => $message]);

        $titlePrefix = null;
        if ($character->active_title_id && $character->activeTitle) {
            $titlePrefix = $character->activeTitle->prefix;
        }

        if ($this->currentChannel === 'guild' && $character->guild_id) {
            broadcast(new \App\Domain\Social\Events\GuildMessageSent(
                characterName:  $character->name,
                characterLevel: $character->level,
                combatPower:    $cp,
                message:        $message,
                sentAt:         now()->toTimeString(),
                characterId:    $character->id,
                guildId:        $character->guild_id,
                titlePrefix:    $titlePrefix,
                isPremium:      $character->user->hasPremium(),
            ));
        } else {
            broadcast(new MessageSent(
                characterName:  $character->name,
                characterLevel: $character->level,
                combatPower:    $cp,
                message:        $message,
                sentAt:         now()->toTimeString(),
                characterId:    $character->id,
                titlePrefix:    $titlePrefix,
                isPremium:      $character->user->hasPremium(),
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

            $equippedPet = \App\Infrastructure\Persistence\Pet::where('character_id', $characterId)
                ->where('is_equipped', true)
                ->first();

            $equippedItems = $character->equippedItems->map(fn ($item) => [
                'name'          => $item->template?->name ?? 'Nieznany',
                'slot'          => $item->template?->slot ?? '?',
                'type'          => $item->template?->type ?? 'unknown',
                'icon'          => $item->template?->icon,
                'upgrade_level' => $item->upgrade_level,
                'rarity'        => $item->rarity,
                'combat_power'  => $item->getCombatPower(),
            ])->values()->toArray();

            $this->tooltipData[$characterId] = [
                'name'          => $character->name,
                'level'         => $character->level,
                'combat_power'  => $character->getTotalCombatPower(),
                'equipped_items' => $equippedItems,
                'pet'           => $equippedPet ? [
                    'name' => $equippedPet->name,
                    'rarity' => $equippedPet->rarity,
                    'level' => $equippedPet->level,
                    'combat_power' => $equippedPet->getCombatPower(),
                ] : null,
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
            $this->dispatch('refresh-guild');
            $this->dispatch('notify', message: "Przekazano {$amount} EXP dla gildii!", type: 'success');
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
            $this->dispatch('refresh-guild');
            $this->dispatch('notify', message: "Wpłacono {$amount} złota do skarbca gildii!", type: 'success');
        } elseif ($type === 'gems') {
            if ($character->user->gems < $amount) {
                $this->dispatch('not-enough-gems');
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
            $this->dispatch('refresh-guild');
            $this->dispatch('notify', message: "Wpłacono {$amount} diamentów do skarbca gildii!", type: 'success');
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
        $commandTrimmed = trim($command);
        $parts = explode(' ', $commandTrimmed);
        if (count($parts) < 3) {
            $this->addError('newMessage', 'Użycie: /give <item_id|gold|gems|pet|title> <ilość|nazwa>');
            return;
        }

        $type = strtolower($parts[1]);

        if ($type === 'pet') {
            $petName = trim(substr($commandTrimmed, 10)); 
            if (empty($petName)) {
                $this->addError('newMessage', 'Użycie: /give pet <nazwa peta>');
                return;
            }
            
            $petTemplate = \App\Infrastructure\Persistence\PetTemplate::whereRaw('LOWER(name) = ?', [strtolower($petName)])->first();
            if (!$petTemplate) {
                $this->addError('newMessage', "Nie znaleziono peta o nazwie: {$petName}");
                return;
            }
            
            \App\Infrastructure\Persistence\Pet::create([
                'character_id' => $character->id,
                'name' => $petTemplate->name,
                'rarity' => $petTemplate->rarity,
                'stats' => $petTemplate->base_stats,
                'level' => 1,
                'exp' => 0,
                'is_equipped' => false,
                'icon' => $petTemplate->icon,
            ]);
            
            $this->dispatch('notify', message: "Otrzymano chowańca: {$petTemplate->name}!", type: 'success');
            return;
        }

        if ($type === 'title') {
            $titleName = trim(substr($commandTrimmed, 12)); 
            if (empty($titleName)) {
                $this->addError('newMessage', 'Użycie: /give title <nazwa tytułu>');
                return;
            }
            
            $title = \App\Infrastructure\Persistence\Title::whereRaw('LOWER(name) = ?', [strtolower($titleName)])->first();
            if (!$title) {
                $this->addError('newMessage', "Nie znaleziono tytułu o nazwie: {$titleName}");
                return;
            }

            \App\Infrastructure\Persistence\CharacterTitle::firstOrCreate([
                'character_id' => $character->id,
                'title_id' => $title->id,
            ], [
                'unlocked_at' => now()
            ]);
            
            $this->dispatch('notify', message: "Otrzymano tytuł: {$title->name}!", type: 'success');
            return;
        }

        if ($type === 'exp') {
            $amount = (int) ($parts[2] ?? 0);
            if ($amount <= 0) {
                $this->addError('newMessage', 'Ilość musi być większa niż 0.');
                return;
            }

            if ((isset($parts[3]) && strtolower($parts[3]) === 'guild') || $this->currentChannel === 'guild') {
                $guild = $character->guild;
                if (!$guild) {
                    $this->addError('newMessage', 'Nie jesteś w gildii.');
                    return;
                }
                $guild->addXp($amount);
                $this->broadcastSystemGuildMessage($guild->id, "Dodano {$amount} EXP na rozwój gildii.");
                $this->dispatch('refresh-guild');
                $this->dispatch('notify', message: "Dodano {$amount} EXP dla gildii!", type: 'success');
                return;
            }

            $character->xp += $amount;
            $character->save();
            $levelUpService = app(\App\Application\Characters\LevelUpService::class);
            $result = $levelUpService->checkAndApply($character);
            $this->dispatch('notify', message: "Dodano {$amount} expa dla postaci.", type: 'success');
            if ($result->isOk() && $result->getPayload()?->hadLevelUp) {
                $this->dispatch('notify', message: "Awansowałeś na poziom {$character->level}!", type: 'success');
            }
            return;
        }

        if ($type === 'guild' || $type === 'gexp' || $type === 'gxp') {
            $subType = strtolower($parts[2] ?? '');
            $subAmount = (int) ($parts[3] ?? $parts[2] ?? 0);
            if ($subAmount <= 0) {
                $this->addError('newMessage', 'Użycie: /give guild <exp|gold|gems> <ilość>');
                return;
            }
            $guild = $character->guild;
            if (!$guild) {
                $this->addError('newMessage', 'Nie jesteś w gildii.');
                return;
            }
            if ($subType === 'exp' || $type === 'gexp' || $type === 'gxp') {
                $guild->addXp($subAmount);
                $this->broadcastSystemGuildMessage($guild->id, "Dodano {$subAmount} EXP na rozwój gildii.");
                $this->dispatch('refresh-guild');
                $this->dispatch('notify', message: "Dodano {$subAmount} EXP dla gildii!", type: 'success');
            } elseif ($subType === 'gold') {
                $guild->gold += $subAmount;
                $guild->save();
                $this->broadcastSystemGuildMessage($guild->id, "Dodano {$subAmount} złota do skarbca gildii.");
                $this->dispatch('refresh-guild');
                $this->dispatch('notify', message: "Dodano {$subAmount} złota dla gildii!", type: 'success');
            } elseif ($subType === 'gems') {
                $guild->gems += $subAmount;
                $guild->save();
                $this->broadcastSystemGuildMessage($guild->id, "Dodano {$subAmount} diamentów do skarbca gildii.");
                $this->dispatch('refresh-guild');
                $this->dispatch('notify', message: "Dodano {$subAmount} diamentów dla gildii!", type: 'success');
            }
            return;
        }

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
        $result = $levelUpService->checkAndApply($character);

        $this->dispatch('notify', message: "Dodano {$amount} expa.", type: 'success');

        if ($result->isOk()) {
            $levelUpResult = $result->getPayload();
            if ($levelUpResult && $levelUpResult->hadLevelUp) {
                $this->dispatch('notify', message: "Awansowałeś na poziom {$character->level}!", type: 'success');
            }
        }
    }

    private function handleSetCommand(string $command, Character $character): void
    {
        $parts = explode(' ', strtolower(trim($command)));
        if (count($parts) < 3) {
            $this->addError('newMessage', 'Użycie: /set <level|sp> <wartość>');
            return;
        }

        $type = $parts[1];
        $value = (int) $parts[2];

        if ($value <= 0) {
            $this->addError('newMessage', 'Wartość musi być większa niż 0.');
            return;
        }

        if ($type === 'level') {
            $character->level = $value;
            $character->save();
            $this->dispatch('notify', message: "Zmieniono poziom na {$value}.", type: 'success');
        } elseif ($type === 'sp') {
            // Dodajemy punkty, zgodnie z intencją "dodawania" z prośby (lub możemy przypisywać)
            $character->character_points += $value;
            $character->save();
            $this->dispatch('notify', message: "Dodano {$value} punktów SP.", type: 'success');
        } else {
            $this->addError('newMessage', 'Nieznany typ dla komendy /set (dostępne: level, sp).');
        }
    }
}
