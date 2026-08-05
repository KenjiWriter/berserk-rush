<?php

namespace App\Console\Commands;

use App\Application\Mail\Actions\SendMailAction;
use App\Domain\Social\Events\MessageSent;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\DiscordLinkCode;
use App\Infrastructure\Persistence\News;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
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

    /**
     * Singleton guard: only one discord:bridge process is allowed to be
     * actively polling at a time. Without this, two processes (e.g. a stray
     * manually-started one alongside the Supervisor-managed one) each keep
     * their own in-memory polling cursor, both pick up the same new Discord
     * message, and both broadcast it - which is what makes messages appear
     * duplicated (sometimes under a different character, if the linked
     * character changed between the two processes' last restart).
     */
    private const SINGLETON_LOCK_KEY = 'discord_bridge_singleton_owner';
    private const SINGLETON_LOCK_TTL_SECONDS = 45;

    private string $token;
    private string $channelId;
    private ?string $updateLogChannelId;
    private string $lockOwner;

    public function handle(): int
    {
        $token = config('services.discord.bot_token');
        $channelId = config('services.discord.chat_channel_id');
        $updateLogChannelId = config('services.discord.update_log_channel_id');

        if (empty($token) || empty($channelId)) {
            $this->error('DISCORD_BOT_TOKEN and/or DISCORD_CHAT_CHANNEL_ID are not configured in .env.');
            return self::FAILURE;
        }

        $this->token = $token;
        $this->channelId = $channelId;
        $this->updateLogChannelId = $updateLogChannelId;
        $this->lockOwner = bin2hex(random_bytes(16));

        if (! Cache::add(self::SINGLETON_LOCK_KEY, $this->lockOwner, self::SINGLETON_LOCK_TTL_SECONDS)) {
            $this->error(
                'Another discord:bridge process already holds the singleton lock - refusing to start '.
                'a second instance (it would duplicate every incoming message). Check "ps aux | grep discord:bridge" '.
                'and "sudo supervisorctl status" for a stray/duplicate process.'
            );
            return self::FAILURE;
        }

        try {
            return $this->poll();
        } finally {
            // Best-effort release; if we lost the lock (e.g. a stall past the
            // TTL) another process may already own it, so only clear it if
            // it's still ours.
            if (Cache::get(self::SINGLETON_LOCK_KEY) === $this->lockOwner) {
                Cache::forget(self::SINGLETON_LOCK_KEY);
            }
        }
    }

    private function poll(): int
    {
        $channelId = $this->channelId;

        $lastMessageId = $this->fetchLatestMessageIdForChannel($this->channelId);

        if ($lastMessageId === null) {
            $this->error(
                'Could not reach Discord (bad token, wrong channel ID, or bot missing '.
                '"View Channel"/"Read Message History" permission on that channel). Check storage/logs/laravel.log.'
            );
            return self::FAILURE;
        }

        $lastUpdateLogId = null;
        if (!empty($this->updateLogChannelId)) {
            $lastUpdateLogId = $this->fetchLatestMessageIdForChannel($this->updateLogChannelId);
            $this->info("Discord update-log bridge listening on channel {$this->updateLogChannelId}.");
        }

        $this->info("Discord chat bridge polling channel {$channelId} every ".self::POLL_INTERVAL_SECONDS.'s...');

        while (true) {
            // Fencing check: if our singleton lock expired (e.g. we stalled
            // past SINGLETON_LOCK_TTL_SECONDS on a slow Discord API call) and
            // another process took over ownership, stop immediately instead
            // of continuing to poll as an unlocked duplicate.
            if (Cache::get(self::SINGLETON_LOCK_KEY) !== $this->lockOwner) {
                Log::warning('Discord chat bridge: lost singleton lock ownership, stopping to avoid duplicate polling.');
                $this->error('Lost singleton lock ownership (stalled past the TTL?) - stopping this instance.');
                return self::FAILURE;
            }

            Cache::put(self::SINGLETON_LOCK_KEY, $this->lockOwner, self::SINGLETON_LOCK_TTL_SECONDS);

            try {
                $messages = $this->fetchNewMessagesForChannel($this->channelId, $lastMessageId);

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
                Log::warning('Discord chat bridge: poll error (chat)', ['error' => $e->getMessage()]);
            }

            // Poll update log channel if configured
            if (!empty($this->updateLogChannelId) && $lastUpdateLogId !== null) {
                try {
                    $updateLogs = $this->fetchNewMessagesForChannel($this->updateLogChannelId, $lastUpdateLogId);

                    foreach (array_reverse($updateLogs) as $logMessage) {
                        $lastUpdateLogId = $logMessage['id'];

                        try {
                            $this->handleIncomingUpdateLogMessage($logMessage);
                        } catch (\Throwable $e) {
                            Log::warning('Discord bridge: error handling update log message', [
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning('Discord bridge: poll error (update-log)', ['error' => $e->getMessage()]);
                }
            }

            sleep(self::POLL_INTERVAL_SECONDS);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchNewMessagesForChannel(string $channelId, string $afterId): array
    {
        $response = $this->discordGet("/channels/{$channelId}/messages", [
            'after' => $afterId,
            'limit' => 100,
        ]);

        if ($response === null || $response->failed()) {
            return [];
        }

        return $response->json() ?? [];
    }

    private function fetchLatestMessageIdForChannel(string $channelId): ?string
    {
        $response = $this->discordGet("/channels/{$channelId}/messages", ['limit' => 1]);

        if ($response === null || $response->failed()) {
            return null;
        }

        $messages = $response->json() ?? [];

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

        // Deduplication guard: if multiple bridge processes are running
        // simultaneously (e.g. two Supervisor workers), only the first one
        // that claims this Discord message ID should actually broadcast it.
        // Cache::add() is atomic - returns false if the key already exists.
        $messageId = $message['id'] ?? null;
        if ($messageId && ! Cache::add('discord_bridge_msg:' . $messageId, 1, 60)) {
            Log::info('Discord chat bridge: DEDUP SKIP - message already claimed', [
                'discord_message_id' => $messageId,
                'content' => $message['content'] ?? null,
                'lock_owner' => $this->lockOwner ?? null,
            ]);
            return;
        }

        Log::info('Discord chat bridge: handling incoming message', [
            'discord_message_id' => $messageId,
            'author_id' => $message['author']['id'] ?? null,
            'content' => $message['content'] ?? null,
            'lock_owner' => $this->lockOwner ?? null,
        ]);

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

        // Prefer the most recently linked character in case multiple characters
        // somehow share the same discord_user_id (data integrity edge case).
        $character = Character::where('discord_user_id', (string) $authorId)
            ->orderBy('id', 'desc')
            ->first();

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

        Log::info('Discord chat bridge: broadcasting resolved character', [
            'discord_message_id' => $messageId,
            'author_id' => $authorId,
            'character_id' => $character->id,
            'character_name' => $character->name,
            'character_level' => $character->level,
            'lock_owner' => $this->lockOwner ?? null,
        ]);

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

    /**
     * Process an incoming message from the update-log channel on Discord.
     * Parses title and content, removing role mentions, and updates/creates
     * a News entry in the game database.
     *
     * @param array<string, mixed> $message
     */
    private function handleIncomingUpdateLogMessage(array $message): void
    {
        $rawContent = $message['content'] ?? '';
        if (trim($rawContent) === '') {
            return;
        }

        $parsed = $this->parseUpdateLogContent($rawContent);
        if (!$parsed) {
            return;
        }

        $msgId = (string) ($message['id'] ?? '');
        if (empty($msgId)) {
            return;
        }

        $publishedAt = isset($message['timestamp'])
            ? Carbon::parse($message['timestamp'])
            : now();

        $newsItem = News::updateOrCreate(
            ['discord_message_id' => $msgId],
            [
                'title' => $parsed['title'],
                'content' => $parsed['content'],
                'source' => 'discord',
                'published_at' => $publishedAt,
            ]
        );

        Log::info('Discord update log imported into news', [
            'news_id' => $newsItem->id,
            'discord_message_id' => $msgId,
            'title' => $parsed['title'],
        ]);
    }

    /**
     * Parses raw Discord text format into title and content.
     * Example input:
     *   @Update-log notification
     *   AKTUALIZACJA [wersja: beta 0.2.4]!
     *   Wprowadzone zmiany:
     *   • Kompletny rework...
     *
     * @return array{title: string, content: string}|null
     */
    private function parseUpdateLogContent(string $rawContent): ?array
    {
        // Strip raw mention tags (<@&...>, <@...>) and string mention "@Update-log notification"
        $cleaned = preg_replace('/<@&?\d+>/', '', $rawContent);
        $cleaned = preg_replace('/@Update-log notification/i', '', $cleaned);
        $cleaned = trim($cleaned);

        if ($cleaned === '') {
            return null;
        }

        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $cleaned));
        $nonEmptyLines = [];
        foreach ($lines as $line) {
            if (trim($line) !== '') {
                $nonEmptyLines[] = trim($line);
            }
        }

        if (empty($nonEmptyLines)) {
            return null;
        }

        // First non-empty line is title
        $title = array_shift($nonEmptyLines);

        // Find index of title line in original lines to preserve exact formatting of body
        $titleIndex = -1;
        foreach ($lines as $idx => $line) {
            if (trim($line) === $title) {
                $titleIndex = $idx;
                break;
            }
        }

        if ($titleIndex !== -1 && isset($lines[$titleIndex + 1])) {
            $content = trim(implode("\n", array_slice($lines, $titleIndex + 1)));
        } else {
            $content = implode("\n", $nonEmptyLines);
        }

        // Normalize unicode bullet points (•) to Markdown list hyphens (- )
        $content = preg_replace('/^[ \t]*[•]\s*/m', '- ', $content);

        return [
            'title' => $title,
            'content' => $content,
        ];
    }
}
