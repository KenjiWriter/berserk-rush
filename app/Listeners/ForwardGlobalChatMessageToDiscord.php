<?php

namespace App\Listeners;

use App\Domain\Social\Events\MessageSent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Mirrors every message sent on the in-game global chat (MessageSent event)
 * into a Discord channel via an Incoming Webhook.
 *
 * Setup:
 *  1. In Discord: Channel Settings -> Integrations -> Webhooks -> New Webhook,
 *     name it (e.g. "Berserk Rush - Global Chat"), copy the Webhook URL.
 *  2. Add DISCORD_GLOBAL_CHAT_WEBHOOK_URL=<that url> to your .env
 *  3. Make sure a queue worker is running (composer dev already starts one
 *     via `php artisan queue:listen`), since this listener is queued.
 *
 * Laravel auto-discovers listeners in app/Listeners by their handle()
 * type-hint, so this class does not need to be registered anywhere manually
 * (same pattern as AchievementProgressedListener in this codebase). If event
 * caching is ever enabled in production (`php artisan event:cache`), run
 * `php artisan event:clear` after deploying this file so it gets picked up.
 */
class ForwardGlobalChatMessageToDiscord implements ShouldQueue
{
    /**
     * Retry a couple of times (with backoff) if Discord/network hiccups,
     * then give up silently rather than clogging the queue.
     */
    public int $tries = 3;

    public function backoff(): array
    {
        return [2, 10, 30];
    }

    public function handle(MessageSent $event): void
    {
        $webhookUrl = config('services.discord.global_chat_webhook_url');

        if (empty($webhookUrl)) {
            // Discord relay not configured - do nothing.
            return;
        }

        // Note: double-quoted strings so the \u{...} unicode escapes work.
        $badge = match (true) {
            $event->isAdmin => " \u{1F451}",      // crown
            $event->isModerator => " \u{1F6E1}",  // shield
            $event->isPremium => " \u{2B50}",     // star
            default => '',
        };

        $prefix = $event->titlePrefix ? "[{$event->titlePrefix}] " : '';

        $content = sprintf(
            '**%s%s** `Lv.%d`%s: %s',
            $prefix,
            $event->characterName,
            $event->characterLevel,
            $badge,
            $event->message
        );

        // Discord hard-caps messages at 2000 chars; the game already caps
        // messages at 200 chars, so this is just a safety net.
        $content = mb_substr($content, 0, 1990);

        try {
            $response = Http::timeout(5)->post($webhookUrl, [
                'content' => $content,
                'username' => 'Berserk Rush - Global Chat',
                // Prevent players from pinging @everyone/@here/roles/users
                // through their in-game chat messages.
                'allowed_mentions' => ['parse' => []],
            ]);

            if ($response->failed()) {
                Log::warning('Discord chat relay: webhook call failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('Discord chat relay: exception while calling webhook', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
