<?php

namespace Tests\Feature;

use App\Application\PvP\MatchmakingService;
use App\Application\PvP\PvPEncounterService;
use App\Infrastructure\Persistence\Character;
use App\Livewire\City\Arena;
use App\Livewire\City\GladiatorShop;
use App\Livewire\City\Hub;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArenaLevelLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_below_level_15_cannot_enter_arena(): void
    {
        $user = User::factory()->create(['game_stage' => 38]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'LowLevelHero',
            'class' => 'warrior',
            'level' => 14,
            'experience' => 0,
            'gold' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(Arena::class, ['character' => $character])
            ->assertRedirect(route('city.hub', $character));
    }

    public function test_character_level_15_can_enter_arena(): void
    {
        $user = User::factory()->create(['game_stage' => 38]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HighLevelHero',
            'class' => 'warrior',
            'level' => 15,
            'experience' => 0,
            'gold' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(Arena::class, ['character' => $character])
            ->assertOk();
    }

    public function test_character_below_level_15_cannot_enter_gladiator_shop(): void
    {
        $user = User::factory()->create(['game_stage' => 38]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'LowLevelHero',
            'class' => 'warrior',
            'level' => 14,
            'experience' => 0,
            'gold' => 100,
        ]);

        Livewire::actingAs($user)
            ->test(GladiatorShop::class, ['character' => $character])
            ->assertRedirect(route('city.hub', $character));
    }

    public function test_pvp_encounter_service_prevents_low_level_encounters(): void
    {
        $user1 = User::factory()->create();
        $attacker = Character::create([
            'user_id' => $user1->id,
            'name' => 'Attacker',
            'class' => 'warrior',
            'level' => 14,
            'experience' => 0,
            'gold' => 100,
        ]);

        $user2 = User::factory()->create();
        $defender = Character::create([
            'user_id' => $user2->id,
            'name' => 'Defender',
            'class' => 'warrior',
            'level' => 15,
            'experience' => 0,
            'gold' => 100,
        ]);

        $service = app(PvPEncounterService::class);
        $result = $service->startEncounter($attacker, $defender);

        $this->assertTrue($result->isError());
        $this->assertEquals('LEVEL_TOO_LOW', $result->getErrorCode());
    }

    public function test_matchmaking_service_excludes_low_level_opponents(): void
    {
        $user1 = User::factory()->create();
        $character = Character::create([
            'user_id' => $user1->id,
            'name' => 'Player1',
            'class' => 'warrior',
            'level' => 15,
            'elo' => 1000,
        ]);

        $user2 = User::factory()->create();
        $lowOpponent = Character::create([
            'user_id' => $user2->id,
            'name' => 'LowOpponent',
            'class' => 'warrior',
            'level' => 14,
            'elo' => 1000,
        ]);

        $user3 = User::factory()->create();
        $highOpponent = Character::create([
            'user_id' => $user3->id,
            'name' => 'HighOpponent',
            'class' => 'warrior',
            'level' => 15,
            'elo' => 1000,
        ]);

        $matchmaking = app(MatchmakingService::class);
        $opponents = $matchmaking->findOpponents($character);

        $opponentIds = collect($opponents)->pluck('id')->toArray();

        $this->assertNotContains($lowOpponent->id, $opponentIds);
        $this->assertContains($highOpponent->id, $opponentIds);
    }

    public function test_arena_ranking_excludes_low_level_characters(): void
    {
        $user1 = User::factory()->create(['game_stage' => 38]);
        $character = Character::create([
            'user_id' => $user1->id,
            'name' => 'Player1',
            'class' => 'warrior',
            'level' => 15,
            'elo' => 1200,
        ]);

        $user2 = User::factory()->create();
        $lowChar = Character::create([
            'user_id' => $user2->id,
            'name' => 'LowLevelChar',
            'class' => 'warrior',
            'level' => 14,
            'elo' => 1500,
        ]);

        Livewire::actingAs($user1)
            ->test(Arena::class, ['character' => $character])
            ->call('switchTab', 'ranking')
            ->assertSee($character->name)
            ->assertDontSee($lowChar->name);
    }

    public function test_tutorial_stage_advances_to_38_when_character_reaches_level_15(): void
    {
        $user = User::factory()->create(['game_stage' => 37]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Hero',
            'class' => 'warrior',
            'level' => 15,
        ]);

        $user->checkAndRepairTutorialStage($character);

        $this->assertEquals(38, $user->fresh()->game_stage);
    }

    public function test_hub_goto_arena_blocked_for_level_below_15(): void
    {
        $user = User::factory()->create(['game_stage' => 37]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Hero',
            'class' => 'warrior',
            'level' => 14,
        ]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['character' => $character])
            ->call('goTo', 'arena')
            ->assertDispatched('notify', type: 'error');
    }
}
