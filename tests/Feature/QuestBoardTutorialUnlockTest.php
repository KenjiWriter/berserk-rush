<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Livewire\City\Hub;
use App\Livewire\City\Quests;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuestBoardTutorialUnlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_hub_unlocks_quest_board_immediately_for_level_5_character_stuck_earlier_in_tutorial(): void
    {
        $user = User::factory()->create(['game_stage' => 16]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Weteran',
            'level' => 5,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        session(['active_character' => $character->id]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['character' => $character])
            ->call('goTo', 'quests')
            ->assertNoRedirect();

        $this->assertEquals(22, $user->fresh()->game_stage);
    }

    public function test_hub_keeps_quest_board_locked_for_character_below_level_5(): void
    {
        $user = User::factory()->create(['game_stage' => 16]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Nowicjusz',
            'level' => 3,
            'gold' => 100,
            'attributes' => ['str' => 4, 'int' => 2, 'vit' => 2, 'agi' => 2],
        ]);

        session(['active_character' => $character->id]);

        Livewire::actingAs($user)
            ->test(Hub::class, ['character' => $character])
            ->call('goTo', 'quests')
            ->assertNoRedirect();

        $this->assertEquals(16, $user->fresh()->game_stage);
    }

    public function test_direct_quests_visit_by_level_5_character_bumps_stage_and_redirects_to_hub(): void
    {
        $user = User::factory()->create(['game_stage' => 18]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Weteran2',
            'level' => 5,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        session(['active_character' => $character->id]);

        Livewire::actingAs($user)
            ->test(Quests::class, ['character' => $character])
            ->assertRedirect(route('city.hub', $character));

        $this->assertEquals(22, $user->fresh()->game_stage);
    }
}
