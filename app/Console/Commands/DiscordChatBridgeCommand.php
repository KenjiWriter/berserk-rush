<?php

namespace App\Console\Commands;

use App\Domain\Social\Events\MessageSent;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\DiscordLinkCode;
use Discord\Discord;
use Discord\Parts\Channel\Message;
use Discord\WebSockets\Event;
use Discord\WebSockets\Intents;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Long-lived Discord bot process that bridges the #in-game-chat Discord
 * channel with the in-game global chat (two-way, alongside the one-way
 * webhook relay in ForwardGlobalChatMessageToDiscord which handles
 * game -> Discord).
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
 *     Privileged Gateway Intents -> copy the bot token.
 *  2. Invite the bot to your server with "View Channel" + "Send Messages"
 *     permissions in #in-game-chat.
 *  3. Set DISCORD_BOT_TOKEN and DISCORD_CHAT_CHANNEL_ID in .env
 *     (channel ID: right-click #in-game-chat in Discord with Developer Mode
 *     enabled -> "Copy Channel ID").
 *  4. composer require team-reflex/discord-php
 *  5. php artisan migrate (adds characters.discord_user_id + discord_link_codes)
 */
class DiscordChatBridgeCommand extends Command
{
    protected $signature = 'discord:bridge';

    protected $description = 'Runs the Discord bot that bridges #in-game-chat with the in-game global chat';

    public function handle(): int
    {
        $token = config('services.discord.bot_token');
        $channelId = config('services.discord.chat_channel_id');

        if (empty($token) || empty($channelId)) {
            $this->error('DISCORD_BOT_TOKEN and/or DISCORD_CHAT_CHANNEL_ID are not configured in .env.');
            return self::FAILURE;
        }

        $discord = new Discord([
            'token' => $token,
            // MESSAGE_CONTENT is a privileged intent - must be enabled for
            // the bot in the Discord Developer Portal or messages arrive empty.
            'intents' => Intents::getDefaultIntents() | Intents::MESSAGE_CONTENT,
        ]);

        $discord->on('ready', function (Discord $discord) use ($channelId) {
            $this->info('Discord chat bridge connected as ' . $discord->user->username);

            $discord->on(Event::MESSAGE_CREATE, function (Message $message) use ($channelId) {
                try {
                    $this->handleIncomingMessage($message, $channelId);
                } catch (\Throwable $e) {
                    Log::warning('Discord chat bridge: error handling incoming message', [
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        });

        $discord->run();

        return self::SUCCESS;
    }

    private function handleIncomingMessage(Message $message, string $channelId): void
    {
        // Only listen in the configured channel.
        if ((string) $message->channel_id !== (string) $channelId) {
            return;
        }

        // Ignore the bot's own messages and anything posted by a webhook -
        // that's how the game -> Discord relay posts messages into this same
        // channel, and we must not treat those as new incoming chat.
        if ($message->author->bot || $message->webhook_id) {
            return;
        }

        $content = trim($message->content);

        if ($content === '') {
            return;
        }

        if (str_starts_with(strtolower($content), '!link ')) {
            $this->handleLinkCommand($message, $content);
            return;
        }

        $discordUserId = (string) $message->author->id;
        $character = Character::where('discord_user_id', $discordUserId)->first();

        if (! $character) {
            $message->reply(
                "{$message->author}, nie masz jeszcze połączonej postaci z tym kontem Discord.\n".
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

    private function handleLinkCommand(Message $message, string $content): void
    {
        $code = strtoupper(trim(substr($content, strlen('!link '))));

        $linkCode = DiscordLinkCode::where('code', $code)
            ->where('expires_at', '>', now())
            ->first();

        if (! $linkCode) {
            $message->reply(
                "{$message->author}, ten kod jest nieprawidłowy albo wygasł. ".
                "Wpisz **/discord** na czacie w grze, żeby wygenerować nowy."
            );
            return;
        }

        $discordUserId = (string) $message->author->id;

        // One character per Discord account - drop any previous link.
        Character::where('discord_user_id', $discordUserId)
            ->update(['discord_user_id' => null]);

        $character = $linkCode->character;
        $character->discord_user_id = $discordUserId;
        $character->save();

        $linkCode->delete();

        $message->reply("✅ {$message->author}, połączono z postacią **{$character->name}**! Twoje wiadomości na tym kanale będą teraz widoczne na czacie globalnym w grze.");
    }
}
