<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EnsureActiveCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_own_active_character()
    {
        $user = User::factory()->create();
        $char1 = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroOne',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 0, 'agi' => 0],
        ]);

        $response = $this->actingAs($user)
            ->withSession(['active_character' => $char1->id])
            ->get(route('city.hub', $char1));

        $response->assertOk();
    }

    public function test_user_cannot_access_different_character_when_one_is_already_active_in_session()
    {
        $user = User::factory()->create();
        $char1 = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroOne',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 0, 'agi' => 0],
        ]);

        $char2 = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroTwo',
            'level' => 1,
            'attributes' => ['str' => 2, 'int' => 2, 'vit' => 3, 'agi' => 3],
        ]);

        // User is playing char1 in session
        $response = $this->actingAs($user)
            ->withSession(['active_character' => $char1->id])
            ->get(route('city.hub', $char2));

        // Attempting to access char2 should redirect to homepage with error
        $response->assertRedirect(route('homepage'));
        $response->assertSessionHas('error');
    }

    public function test_user_cannot_launch_second_character_via_play_route_when_another_character_is_active()
    {
        $user = User::factory()->create();
        $char1 = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroOne',
            'level' => 1,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 0, 'agi' => 0],
        ]);

        $char2 = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroTwo',
            'level' => 1,
            'attributes' => ['str' => 2, 'int' => 2, 'vit' => 3, 'agi' => 3],
        ]);

        // User is playing char1
        $response = $this->actingAs($user)
            ->withSession(['active_character' => $char1->id])
            ->get(route('characters.play', $char2));

        $response->assertRedirect(route('homepage'));
        $response->assertSessionHas('error');
    }
}
