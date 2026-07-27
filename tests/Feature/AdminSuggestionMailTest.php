<?php

namespace Tests\Feature;

use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Mail;
use App\Infrastructure\Persistence\Suggestion;
use App\Livewire\Admin\Suggestions;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminSuggestionMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_suggestion_status_sends_in_game_mail_to_character(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'TestHero',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 0, 'agi' => 0],
        ]);

        $suggestion = Suggestion::create([
            'user_id'      => $user->id,
            'character_id' => $character->id,
            'category'     => 'sugestia',
            'content'      => 'Testowa tresc zgloszenia o cos tam',
            'status'       => 'new',
            'admin_notes'  => 'Przetestowane w środowisku testowym',
        ]);

        Livewire::actingAs($user)
            ->test(Suggestions::class)
            ->call('updateStatus', $suggestion->id, 'resolved');

        $suggestion->refresh();
        $this->assertEquals('resolved', $suggestion->status);

        $mail = Mail::where('to_character_id', $character->id)->first();
        $this->assertNotNull($mail);
        $this->assertEquals('Aktualizacja statusu zgłoszenia', $mail->subject);
        $this->assertStringContainsString('Testowa tresc zgloszenia o cos tam', $mail->body);
        $this->assertStringContainsString('zostało zaktualizowane na status: Rozpatrzona', $mail->body);
        $this->assertStringContainsString('Notatka administratora:', $mail->body);
        $this->assertStringContainsString('Przetestowane w środowisku testowym', $mail->body);
    }
}
