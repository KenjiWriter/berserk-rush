<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscordUpdateLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_model_can_store_discord_message_id_and_source(): void
    {
        $news = News::create([
            'title' => 'AKTUALIZACJA [wersja: beta 0.2.4]!',
            'content' => "Wprowadzone zmiany:\n- Kompletny rework systemu Petów",
            'published_at' => now(),
            'discord_message_id' => '1234567890',
            'source' => 'discord',
        ]);

        $this->assertDatabaseHas('news', [
            'id' => $news->id,
            'title' => 'AKTUALIZACJA [wersja: beta 0.2.4]!',
            'discord_message_id' => '1234567890',
            'source' => 'discord',
        ]);
    }
}
