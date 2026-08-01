<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Models\User;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\CharacterBestiary;
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

        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
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

        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
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

        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
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

    public function test_group_victory_rewards_and_bestiary_scale_with_monster_count()
    {
        // Regression test: previously a group fight (3-4 monsters) only ever granted
        // gold/xp/bestiary progress as if a single monster had been defeated, because
        // the reward calculation and MonsterDefeated event were only run once for the
        // group's "representative" monster instead of once per monster actually killed.
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'GroupRewardHero',
            'class' => 'warrior',
            'level' => 30,
            'experience' => 0,
            'gold' => 0,
            'attributes' => ['str' => 80, 'int' => 10, 'vit' => 80, 'agi' => 50],
        ]);

        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        $monster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Wilk Leśny',
            'level' => 3,
            'rank' => 'regular',
            'stats' => ['hp' => 30, 'atk' => 5, 'def' => 1, 'agi' => 2],
        ]);

        $service = app(EncounterService::class);
        $startRes = $service->start($character, $map);
        $this->assertTrue($startRes->isOk());
        $encounter = $startRes->getPayload();

        $monsterCount = count($encounter->combat_data['monsters']);
        $this->assertGreaterThanOrEqual(3, $monsterCount);

        $simRes = $service->simulate($encounter);
        $this->assertTrue($simRes->isOk());

        $encounter->refresh();

        // With a single, weak monster type and a much stronger character, the fight
        // must be won for the assertions below to be meaningful.
        $this->assertEquals('win', $encounter->state);

        // Reward for a single monster of this level, after the -66% over-level
        // penalty, tops out at 7 gold / 4 xp. If the fix regresses back to
        // "one representative monster only", totals would sit at/below that ceiling
        // regardless of how many monsters were actually in the group.
        $this->assertGreaterThan(7, $encounter->gold_reward);
        $this->assertGreaterThan(4, $encounter->xp_reward);

        // Bestiary kill count must reflect every monster actually defeated in the
        // group, not just one.
        $bestiary = CharacterBestiary::where('character_id', $character->id)
            ->where('monster_id', $monster->id)
            ->first();

        $this->assertNotNull($bestiary);
        $this->assertEquals($monsterCount, $bestiary->kills);
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

        $map = Map::firstOrCreate([
            'name' => 'Mroczny Las',
        ], [
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

    public function test_tier_scaling_applied_when_player_is_higher_tier()
    {
        // Tier 6 map (level_min=65) exists in db (seeded by MapSeeder),
        // but for this unit test we create maps inline.
        // Player at level 65 → playerTier = T2 (we only seed T1 at level_min=0 and T2 at level_min=10 below)
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'TierTestHero',
            'class' => 'warrior',
            'level' => 20,
            'experience' => 0,
            'gold' => 0,
        ]);

        // Tier 2 map (player level 20 >= level_min 10 → player is T2)
        $mapT2 = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Stare Ruiny Test',
            'level_min' => 10,
            'level_max' => 30,
            'tier' => 2,
        ]);

        // Tier 1 map (lower than player's natural tier T2)
        $mapT1 = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las Test',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        // Check playerTier
        $playerTier = $mapT1->getPlayerTier($character);
        $this->assertEquals(2, $playerTier);

        // Check tierDiff: playerTier(2) - mapTier(1) = 1
        $tierDiff = $mapT1->getTierDiff($character);
        $this->assertEquals(1, $tierDiff);

        // Check multiplier: 1.0 + (playerTier * 0.20) = 1.0 + (2 * 0.20) = 1.4
        $tierMultiplier = $mapT1->getMonsterTierMultiplier($character);
        $this->assertEquals(1.40, $tierMultiplier);

        // Check no scaling on same tier map
        $this->assertEquals(0, $mapT2->getTierDiff($character));
        $this->assertEquals(1.0, $mapT2->getMonsterTierMultiplier($character));
    }

    public function test_tier_label_appears_in_combat_data_and_monster_list()
    {
        $user = User::factory()->create(['game_stage' => 20]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'TierLabelHero',
            'class' => 'warrior',
            'level' => 20,
            'experience' => 0,
            'gold' => 0,
        ]);

        Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Stare Ruiny Test',
            'level_min' => 10,
            'level_max' => 30,
            'tier' => 2,
        ]);

        $mapT1 = Map::create([
            'id' => (string) \Illuminate\Support\Str::ulid(),
            'name' => 'Mroczny Las Test',
            'level_min' => 0,
            'level_max' => 15,
            'tier' => 1,
        ]);

        Monster::create([
            'map_id' => $mapT1->id,
            'name' => 'Wilk Leśny',
            'level' => 3,
            'rank' => 'regular',
            'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5],
        ]);

        $service = app(EncounterService::class);
        // Over-level (lvl 20 > level_max 15) so 3-4 monsters
        $result = $service->start($character, $mapT1);
        $this->assertTrue($result->isOk());
        $encounter = $result->getPayload();

        // tier_label should be [T2]
        $this->assertEquals('[T2]', $encounter->combat_data['tier_label']);
        $this->assertEquals(2, $encounter->combat_data['player_tier']);
        $this->assertEquals(1, $encounter->combat_data['tier_diff']);

        // All monsters in the list should carry the tier_label
        foreach ($encounter->combat_data['monsters'] as $m) {
            $this->assertEquals('[T2]', $m['tier_label']);
        }

        // Monster stats should be scaled by 1.4x
        $baseAtk = 8; // original atk
        $expectedAtk = (int)ceil($baseAtk * 1.40);
        foreach ($encounter->combat_data['monsters'] as $m) {
            $this->assertGreaterThanOrEqual($expectedAtk, $m['stats']['atk']);
        }
    }
}
