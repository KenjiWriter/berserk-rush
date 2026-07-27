<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TutorialStageRepairTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_at_stage_21_with_level_5_or_higher_is_automatically_repaired_to_stage_22()
    {
        $user = User::factory()->create(['game_stage' => 21]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Wojownik',
            'level' => 69,
            'stat_str' => 10,
            'stat_agi' => 10,
            'stat_vit' => 10,
            'stat_int' => 10,
            'character_points' => 0,
            'skill_points' => 0,
            'xp' => 0,
            'gold' => 100,
        ]);

        $user->checkAndRepairTutorialStage($character);

        $this->assertEquals(22, $user->fresh()->game_stage);
    }

    public function test_user_at_stage_21_with_level_below_5_remains_at_stage_21()
    {
        $user = User::factory()->create(['game_stage' => 21]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Wojownik2',
            'level' => 3,
            'stat_str' => 10,
            'stat_agi' => 10,
            'stat_vit' => 10,
            'stat_int' => 10,
            'character_points' => 0,
            'skill_points' => 0,
            'xp' => 0,
            'gold' => 100,
        ]);

        $user->checkAndRepairTutorialStage($character);

        $this->assertEquals(21, $user->fresh()->game_stage);
    }
}
