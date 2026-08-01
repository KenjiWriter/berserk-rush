<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Models\User;
use App\Application\Combat\EncounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MonsterScalingTest extends TestCase
{
    use RefreshDatabase;

    public function test_monster_stats_do_not_scale_when_player_level_is_lower_or_equal()
    {
        $monster = new Monster([
            'level' => 5,
            'stats' => ['hp' => 100, 'atk' => 20, 'def' => 10, 'agi' => 15],
        ]);

        $scaledStats = $monster->getScaledStats(5);

        $this->assertEquals(100, $scaledStats['hp']);
        $this->assertEquals(20, $scaledStats['atk']);
        $this->assertEquals(10, $scaledStats['def']);
        $this->assertEquals(15, $scaledStats['agi']);
    }

    public function test_monster_stats_scale_up_progressively_when_player_level_is_higher()
    {
        $monster = new Monster([
            'level' => 5,
            'stats' => ['hp' => 100, 'atk' => 20, 'def' => 10, 'agi' => 10],
        ]);

        $scaledStats = $monster->getScaledStats(10);

        $this->assertEquals(100, $scaledStats['hp']);
        $this->assertEquals(20, $scaledStats['atk']);
        $this->assertEquals(10, $scaledStats['def']);
        $this->assertEquals(10, $scaledStats['agi']);
    }

    public function test_tutorial_stats_override_for_tutorial_encounter()
    {
        $monster = new Monster([
            'level' => 5,
            'stats' => ['hp' => 200, 'atk' => 50, 'def' => 30, 'agi' => 30],
        ]);

        $scaledStats = $monster->getScaledStats(1, true);

        $this->assertEquals(35, $scaledStats['hp']);
        $this->assertEquals(6, $scaledStats['atk']);
        $this->assertEquals(2, $scaledStats['def']);
        $this->assertEquals(3, $scaledStats['agi']);
    }

    public function test_encounter_service_selects_wolf_during_tutorial()
    {
        $user = User::factory()->create(['game_stage' => 11]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Novice',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
            'gold' => 0,
        ]);
        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
            'level_min' => 0,
            'level_max' => 10,
            'tier' => 1,
        ]);

        $otherMonster = Monster::firstOrCreate(
            ['map_id' => $map->id, 'name' => 'Goblin Zwiadowca'],
            [
                'level' => 8,
                'rank' => 'regular',
                'stats' => ['hp' => 100, 'atk' => 20, 'def' => 5, 'agi' => 10],
            ]
        );

        $wolfMonster = Monster::firstOrCreate(
            ['map_id' => $map->id, 'name' => 'Wilk Leśny'],
            [
                'level' => 3,
                'rank' => 'regular',
                'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5],
            ]
        );

        $service = app(EncounterService::class);
        $result = $service->start($character, $map);

        $this->assertTrue($result->isOk());
        $encounter = $result->getPayload();
        $this->assertEquals($wolfMonster->id, $encounter->monster_id);
    }
}
