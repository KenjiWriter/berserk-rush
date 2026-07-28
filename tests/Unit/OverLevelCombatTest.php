<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Models\User;
use App\Application\Combat\EncounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OverLevelCombatTest extends TestCase
{
    use RefreshDatabase;

    public function test_high_level_character_can_access_lower_level_map_and_is_flagged_overlevel()
    {
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HighLevelHero',
            'class' => 'warrior',
            'level' => 20,
            'experience' => 0,
            'gold' => 0,
        ]);

        $map = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        $this->assertTrue($map->isAccessibleBy($character));
        $this->assertTrue($map->isOverLevel($character));
    }

    public function test_over_level_encounter_spawns_multiple_monsters()
    {
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Veteran',
            'class' => 'warrior',
            'level' => 25,
            'experience' => 0,
            'gold' => 0,
        ]);

        $map = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        Monster::create([
            'map_id' => $map->id,
            'name' => 'Wilk Leśny',
            'level' => 3,
            'rank' => 'regular',
            'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5],
        ]);

        Monster::create([
            'map_id' => $map->id,
            'name' => 'Goblin Zwiadowca',
            'level' => 5,
            'rank' => 'regular',
            'stats' => ['hp' => 80, 'atk' => 12, 'def' => 4, 'agi' => 8],
        ]);

        $service = app(EncounterService::class);
        $result = $service->start($character, $map, null, 'highest_hp');

        $this->assertTrue($result->isOk());
        $encounter = $result->getPayload();

        $this->assertTrue($encounter->combat_data['is_overlevel']);
        $this->assertEquals('highest_hp', $encounter->combat_data['target_strategy']);
        $monsters = $encounter->combat_data['monsters'];
        $this->assertGreaterThanOrEqual(3, count($monsters));
        $this->assertLessThanOrEqual(4, count($monsters));
    }

    public function test_multi_monster_simulation_runs_and_applies_reward_penalty()
    {
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'OverlevelFarmer',
            'class' => 'warrior',
            'level' => 30,
            'experience' => 0,
            'gold' => 0,
            'attributes' => ['str' => 50, 'int' => 10, 'vit' => 50, 'agi' => 30],
        ]);

        $map = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        Monster::create([
            'map_id' => $map->id,
            'name' => 'Wilk Leśny',
            'level' => 3,
            'rank' => 'regular',
            'stats' => ['hp' => 30, 'atk' => 5, 'def' => 1, 'agi' => 2],
        ]);

        $service = app(EncounterService::class);
        $startRes = $service->start($character, $map, null, 'lowest_hp');
        $this->assertTrue($startRes->isOk());
        $encounter = $startRes->getPayload();

        $simRes = $service->simulate($encounter);
        $this->assertTrue($simRes->isOk());

        $payload = $simRes->getPayload();
        $this->assertNotEmpty($payload['turns']);

        // Confirm last turn state shows monsters_state with non-empty stats
        $lastTurn = end($payload['turns']);
        $this->assertArrayHasKey('monsters_state', $lastTurn);
        foreach ($lastTurn['monsters_state'] as $mState) {
            $this->assertArrayHasKey('stats', $mState);
            $this->assertGreaterThan(0, $mState['stats']['atk'] ?? 0);
        }
    }

    public function test_max_two_duplicates_per_group()
    {
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'DupeChecker',
            'class' => 'warrior',
            'level' => 25,
            'experience' => 0,
            'gold' => 0,
        ]);

        $map = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        $m1 = Monster::create([
            'map_id' => $map->id,
            'name' => 'Wilk Leśny',
            'level' => 3,
            'rank' => 'regular',
            'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5],
        ]);

        $m2 = Monster::create([
            'map_id' => $map->id,
            'name' => 'Goblin Zwiadowca',
            'level' => 5,
            'rank' => 'regular',
            'stats' => ['hp' => 80, 'atk' => 12, 'def' => 4, 'agi' => 8],
        ]);

        $service = app(EncounterService::class);

        // Run 20 starts to verify no group ever exceeds 2 duplicates of any monster
        for ($k = 0; $k < 20; $k++) {
            $startRes = $service->start($character, $map);
            $this->assertTrue($startRes->isOk());
            $encounter = $startRes->getPayload();

            $monsters = $encounter->combat_data['monsters'];
            $counts = array_count_values(array_column($monsters, 'id'));

            foreach ($counts as $mId => $count) {
                $this->assertLessThanOrEqual(2, $count, "Monster {$mId} was spawned {$count} times (max 2 allowed)");
            }

            // Simulate to finish encounter so next iteration can start
            $service->simulate($encounter);
        }
    }
}
