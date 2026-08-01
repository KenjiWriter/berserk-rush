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

    public function test_format_news_markdown_handles_utf8_bullets_safely(): void
    {
        $rawContent = "Wprowadzone zmiany:\n• Kompletny rework **Petów**\n• Nowy system";
        $html = \App\Livewire\Homepage::formatNewsMarkdown($rawContent);

        $this->assertStringContainsString('Kompletny rework <strong>Petów</strong>', $html);
        $this->assertStringContainsString('<li>Nowy system</li>', $html);
    }
}
