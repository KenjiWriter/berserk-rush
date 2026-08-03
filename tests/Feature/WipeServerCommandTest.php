<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class WipeServerCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function test_game_wipe_command_preserves_users_and_characters_and_resets_game_stage(): void
    {
        // 1. Arrange: Create user with game_stage 40 and 500 gems
        $user = User::factory()->create([
            'email' => 'player_test1@example.com',
            'name' => 'Player One',
            'game_stage' => 40,
            'gems' => 500,
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroOneTest',
            'level' => 10,
            'xp' => 500,
            'gold' => 1000,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5],
        ]);

        // 2. Act: Call game:wipe command
        $this->artisan('game:wipe', ['--force' => true])
            ->assertExitCode(0);

        // 3. Assert: User and Character still exist in DB, game_stage is 3 and gems are 0
        $this->assertDatabaseHas('users', [
            'email' => 'player_test1@example.com',
            'name' => 'Player One',
            'game_stage' => 3,
            'gems' => 0,
        ]);

        $this->assertDatabaseHas('characters', [
            'name' => 'HeroOneTest',
            'level' => 10,
        ]);
    }

    public function test_game_wipe_command_with_users_only_option(): void
    {
        $user = User::factory()->create([
            'email' => 'player_test2@example.com',
            'game_stage' => 25,
            'gems' => 1200,
        ]);

        Character::create([
            'user_id' => $user->id,
            'name' => 'HeroTwoTest',
            'level' => 5,
            'attributes' => ['str' => 2, 'int' => 2, 'vit' => 2, 'agi' => 2],
        ]);

        $this->artisan('game:wipe', ['--users-only' => true, '--force' => true])
            ->assertExitCode(0);

        $this->assertDatabaseHas('users', [
            'email' => 'player_test2@example.com',
            'game_stage' => 3,
            'gems' => 0,
        ]);

        $this->assertDatabaseMissing('characters', [
            'name' => 'HeroTwoTest',
        ]);
    }
}
