<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SetCharacterLevelCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_set_character_level_by_id(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Zadymiarz',
            'level' => 53,
            'xp' => 5000,
            'character_points' => 0,
            'skill_points' => 0,
            'attributes' => ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
        ]);

        $this->artisan('character:set-level', [
            'character' => $character->id,
            'level' => 10,
        ])
        ->assertExitCode(0);

        $character->refresh();
        $this->assertEquals(10, $character->level);
        $this->assertEquals(0, $character->xp);
        $this->assertEquals(27, $character->character_points); // (10-1)*3 = 27
        $this->assertEquals(9, $character->skill_points); // 10-1 = 9
    }

    public function test_can_set_character_level_by_name(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Wojownik',
            'level' => 1,
            'xp' => 0,
            'attributes' => ['str' => 0, 'int' => 0, 'vit' => 0, 'agi' => 0],
        ]);

        $this->artisan('character:set-level', [
            'character' => 'Wojownik',
            'level' => 20,
        ])
        ->assertExitCode(0);

        $character->refresh();
        $this->assertEquals(20, $character->level);
    }
}
