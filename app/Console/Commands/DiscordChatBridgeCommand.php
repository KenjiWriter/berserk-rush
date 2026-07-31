<?php

namespace App\Console\Commands;

use App\Application\Mail\Actions\SendMailAction;
use App\Domain\Social\Events\MessageSent;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\DiscordLinkCode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Long-lived Discord bot process that bridges the #in-game-chat Discord
 * channel with the in-game global chat (two-way, alongside the one-way
 * webhook relay in ForwardGlobalChatMessageToDiscord which handles
 * game -> Discord).
 *
 * Implementation note: this polls the Discord REST API every few seconds
 * instead of using a Gateway/WebSocket library (e.g. team-reflex/discord-php).
 * That library's dependency tree (react/promise, guzzle, carbon, discord-php/http)
 * conflicts hard with versions already locked by this project (Reverb and
 * friends), and isn't resolvable without forcing risky downgrades across the
 * whole app. Polling needs zero new Composer packages - it's just
 * Illuminate\Support\Facades\Http, the same client already used by
 * ForwardGlobalChatMessageToDiscord. The trade-off is a ~2 second delay
 * instead of instant delivery, which is a fine trade for a chat bridge.
 *
 * Run it under Supervisor exactly like `reverb:start` / `queue:work`:
 *
 *   [program:berserk-discord-bridge]
 *   command=php /var/www/berserk-rush/artisan discord:bridge
 *   autostart=true
 *   autorestart=true
 *   user=www-data
 *   redirect_stderr=true
 *   stdout_logfile=/var/www/berserk-rush/storage/logs/discord-bridge.log
 *
 * Locally, add it as another concurrent process in composer.json's "dev"
 * script (see composer.json), next to serve/queue/vite/reverb.
 *
 * Setup required before this does anything:
 *  1. Discord Developer Portal (https://discord.com/developers/applications)
 *     -> New Application -> Bot -> enable "MESSAGE CONTENT INTENT" under
 *     Privileged Gateway Intents -> copy the bot token. (Still required even
 *     though we use REST, not the Gateway - Discord strips message content
 *     from API responses too without it.)
 *  2. Invite the bot to your server with "View Channel" + "Send Messages"
 *     permissions in #in-game-chat.
 *  3. Set DISCORD_BOT_TOKEN and DISCORD_CHAT_CHANNEL_ID in .env
 *     (channel ID: right-click #in-game-chat in Discord with Developer Mode
 *     enabled -> "Copy Channel ID").
 *  4. php artisan migrate (adds characters.discord_user_id/discord_link_reward_claimed_at
 *     + discord_link_codes)
 *
 * No "composer require" needed for this feature.
 */
class DiscordChatBridgeCommand extends Command
{
    protected $signature = 'discord:bridge';

    protected $description = 'Polls #in-game-chat on Discord and bridges it with the in-game global chat';

    private const POLL_INTERVAL_SECONDS = 2;
    private const API_BASE = 'https://discord.com/api/v10';

    private string $token;
    private string $channelId;

    public function handle(): int
    {
        $token = config('services.discord.bot_token');
        $channelId = config('services.discord.chat_channel_id');

        if (empty($token) || empty($channelId)) {
            $this->error('DISCORD_BOT_TOKEN and/or DISCORD_CHAT_CHANNEL_ID are not configured in .env.');
            return self::FAILURE;
        }

        $this->token = $token;
        $this->channelId = $channelId;

        $lastMessageId = $this->fetchLatestMessageId();

        if ($lastMessageId === null) {
            $this->error(
                'Could not reach Discord (bad token, wrong channel ID, or bot missing '.
                '"View Channel"/"Read Message History" permission on that channel). Check storage/logs/laravel.log.'
            );
            return self::FAILURE;
        }

        $this->info("Discord chat bridge polling channel {$channelId} every ".self::POLL_INTERVAL_SECONDS.'s...');

        while (true) {
            try {
                $messages = $this->fetchNewMessages($lastMessageId);

                // Discord returns newest-first; replay oldest-first so
                // ordering in the game chat matches the order they were sent.
                foreach (array_reverse($messages) as $message) {
                    $lastMessageId = $message['id'];

                    try {
                        $this->handleIncomingMessage($message);
                    } catch (\Throwable $e) {
                        Log::warning('Discord chat bridge: error handling incoming message', [
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning('Discord chat bridge: poll error', ['error' => $e->getMessage()]);
            }

            sleep(self::POLL_INTERVAL_SECONDS);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchNewMessages(string $afterId): array
    {
        $response = $this->discordGet("/channels/{$this->channelId}/messages", [
            'after' => $afterId,
            'limit' => 100,
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function fetchLatestMessageId(): ?string
    {
        $response = $this->discordGet("/channels/{$this->channelId}/messages", ['limit' => 1]);

        if ($response === null || $response->failed()) {
            return null;
        }

        $messages = $response->json() ?? [];

        // Empty channel history is still a "successful" connection - start
        // from "no messages seen yet" rather than treating it as an error.
        return $messages[0]['id'] ?? '0';
    }

    private function discordGet(string $path, array $query = []): ?\Illuminate\Http\Client\Response
    {
        $response = Http::withToken($this->token, 'Bot')
            ->timeout(10)
            ->get(self::API_BASE.$path, $query);

        if ($response->status() === 429) {
            $retryAfter = (float) ($response->json('retry_after') ?? 1);
            Log::warning('Discord chat bridge: rate limited, backing off', ['retry_after' => $retryAfter]);
            usleep((int) ($retryAfter * 1_000_000));
            return null;
        }

        if ($response->failed()) {
            Log::warning('Discord chat bridge: GET failed', [
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response;
    }

    private function replyToDiscord(string $content): void
    {
        $response = Http::withToken($this->token, 'Bot')
            ->timeout(10)
            ->post(self::API_BASE."/channels/{$this->channelId}/messages", [
                'content' => $content,
                'allowed_mentions' => ['parse' => ['users']],
            ]);

        if ($response->failed()) {
            Log::warning('Discord chat bridge: failed to send reply', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * @param array<string, mixed> $message Raw Discord API message object.
     */
    private function handleIncomingMessage(array $message): void
    {
        // Ignore the bot's own messages and anything posted by a webhook -
        // that's how the game -> Discord relay posts messages into this same
        // channel, and we must not treat those as new incoming chat.
        if (($message['author']['bot'] ?? false) || !empty($message['webhook_id'])) {
            return;
        }

        $content = trim($message['content'] ?? '');

        if ($content === '') {
            return;
        }

        $authorId = $message['author']['id'] ?? null;
        $authorMention = $authorId ? "<@{$authorId}>" : 'Hej';

        if (str_starts_with(strtolower($content), '!link ')) {
            $this->handleLinkCommand($authorId, $authorMention, $content);
            return;
        }

        if (strtolower($content) === '!unlink') {
            $this->handleUnlinkCommand($authorId, $authorMention);
            return;
        }

        if (! $authorId) {
            return;
        }

        $character = Character::where('discord_user_id', (string) $authorId)->first();

        if (! $character) {
            $this->replyToDiscord(
                "{$authorMention}, nie masz jeszcze połączonej postaci z tym kontem Discord.\n".
                "Wpisz **/discord** na czacie w grze, żeby dostać kod, a potem tutaj: `!link KOD`."
            );
            return;
        }

        $titlePrefix = null;
        if ($character->active_title_id && $character->activeTitle) {
            $titlePrefix = $character->activeTitle->prefix;
        }

        broadcast(new MessageSent(
            characterName: $character->name,
            characterLevel: $character->level,
            combatPower: $character->getTotalCombatPower(),
            message: mb_substr($content, 0, 200),
            sentAt: now()->toTimeString(),
            characterId: $character->id,
            titlePrefix: $titlePrefix,
            isPremium: $character->user->hasPremium(),
            isAdmin: $character->user->permission_level >= 9,
            isModerator: $character->user->permission_level == 8,
            fromDiscord: true,
        ));
    }

    private function handleLinkCommand(?string $authorId, string $authorMention, string $content): void
    {
        if (! $authorId) {
            return;
        }

        $code = strtoupper(trim(substr($content, strlen('!link '))));

        $linkCode = DiscordLinkCode::where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (! $linkCode) {
            $this->replyToDiscord(
                "{$authorMention}, ten kod jest nieprawidłowy albo wygasł. ".
                "Wpisz **/discord** na czacie w grze, żeby wygenerować nowy."
            );
            return;
        }

        // One character per Discord account - drop any previous link.
        Character::where('discord_user_id', (string) $authorId)
            ->update(['discord_user_id' => null]);

        $character = $linkCode->character;
        $character->discord_user_id = (string) $authorId;
        $character->save();

        $linkCode->delete();

        $rewardGranted = $this->grantLinkRewardIfEligible($character);

        $replyText = "✅ {$authorMention}, połączono z postacią **{$character->name}**! Twoje wiadomości na tym kanale będą teraz widoczne na czacie globalnym w grze.";

        if ($rewardGranted) {
            $replyText .= "\n🎁 Wysłaliśmy Ci **200 diamentów** pocztą w grze - odbierz je ze skrzynki!";
        }

        $this->replyToDiscord($replyText);
    }

    /**
     * "!unlink" - the Discord-side counterpart to "/discord unlink" in game.
     * Only clears discord_user_id; discord_link_reward_claimed_at is left
     * untouched on purpose, so the one-time 200 gems reward can never be
     * farmed again by unlinking and relinking (same or different account).
     */
    private function handleUnlinkCommand(?string $authorId, string $authorMention): void
    {
        if (! $authorId) {
            return;
        }

        $character = Character::where('discord_user_id', (string) $authorId)->first();

        if (! $character) {
            $this->replyToDiscord("{$authorMention}, to konto Discord nie jest połączone z żadną postacią.");
            return;
        }

        $character->update(['discord_user_id' => null]);

        $this->replyToDiscord("🔌 {$authorMention}, odłączono postać **{$character->name}** od tego konta Discord.");
    }

    /**
     * One-time reward for linking a Discord account for the first time.
     * Delivered via in-game mail (same mechanism as guild invites etc.),
     * so it works whether the player is online or not, and shows up in
     * their mailbox regardless of the Discord bot's own connection state.
     * Guarded by characters.discord_link_reward_claimed_at so unlinking
     * and relinking the same character can't be used to farm it again.
     */
    private function grantLinkRewardIfEligible(Character $character): bool
    {
        $rewardGems = 200;

        return DB::transaction(function () use ($character, $rewardGems) {
            // Lock the row so two near-simultaneous !link attempts for the
            // same character can't both slip past the claimed_at check.
            $fresh = Character::whereKey($character->id)->lockForUpdate()->first();

            if (! $fresh || $fresh->discord_link_reward_claimed_at !== null) {
                return false;
            }

            $fresh->discord_link_reward_claimed_at = now();
            $fresh->save();

            app(SendMailAction::class)->execute(
                toCharacterId: $fresh->id,
                subject: 'Nagroda za połączenie z Discordem',
                body: "Dzięki za połączenie postaci z naszym serwerem Discord! W załączniku {$rewardGems} diamentów.",
                attachments: [
                    ['type' => 'gems', 'qty' => $rewardGems],
                ],
            );

            return true;
        });
    }
}
