<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Application\Skills\UpgradeSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class CombatSkillPointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_missing_points_calculates_unspent_points_correctly()
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'TestWarlock',
            'level' => 22,
            'skill_points' => 0, // Simulated corrupted / empty points
        ]);

        $skill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mocny Cios',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'sword',
            'effect_type' => 'buff_phys_dmg',
            'base_cooldown' => 5,
            'base_duration' => 3,
            'base_value' => 0.20,
            'scaling_value' => 0.05,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 3,
            'is_equipped' => true,
        ]);

        // Level 22 -> Total earned skill points = 21 * 3 = 63.
        // Spent: unlock_cost (5) + (level 3 - 1 = 2) = 7 points.
        // Unspent remaining = 63 - 7 = 56.
        $character->syncMissingPoints();

        $this->assertEquals(56, $character->fresh()->skill_points);
    }

    public function test_sync_missing_points_caps_skills_over_level_5()
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'BuggedWarlock',
            'level' => 30,
            'skill_points' => 0,
        ]);

        $skill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mocny Cios',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'sword',
            'effect_type' => 'buff_phys_dmg',
            'base_cooldown' => 5,
            'base_duration' => 3,
            'base_value' => 0.20,
            'scaling_value' => 0.05,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        $charSkill = CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 100, // Bugged high level
            'is_equipped' => true,
        ]);

        $character->syncMissingPoints();

        // Level should be capped to 5
        $this->assertEquals(5, $charSkill->fresh()->level);
        // Level 30 -> 29 * 3 = 87 earned points. Spent: 5 + (5-1=4) = 9 points. Remaining: 78 points.
        $this->assertEquals(78, $character->fresh()->skill_points);
    }

    public function test_upgrade_skill_blocks_upgrade_above_level_5()
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'MaxWarlock',
            'level' => 50,
            'skill_points' => 10,
        ]);

        $skill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mocny Cios',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'sword',
            'effect_type' => 'buff_phys_dmg',
            'base_cooldown' => 5,
            'base_duration' => 3,
            'base_value' => 0.20,
            'scaling_value' => 0.05,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        $charSkill = CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 5,
            'is_equipped' => true,
        ]);

        $upgrader = new UpgradeSkill();
        $result = $upgrader->execute($character, $charSkill);

        $this->assertTrue($result->isError());
        $this->assertEquals('MAX_LEVEL_REACHED', $result->getErrorCode());
        $this->assertEquals(5, $charSkill->fresh()->level);
    }

    public function test_skills_reset_all_command()
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'ResetTarget',
            'level' => 40,
            'skill_points' => 0,
        ]);

        $skill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Mocny Cios',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'sword',
            'effect_type' => 'buff_phys_dmg',
            'base_cooldown' => 5,
            'base_duration' => 3,
            'base_value' => 0.20,
            'scaling_value' => 0.05,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 4,
            'is_equipped' => true,
        ]);

        $this->artisan('skills:reset-all')
            ->assertExitCode(0);

        $this->assertEquals(0, CharacterCombatSkill::count());
        $this->assertEquals(117, $character->fresh()->skill_points);
    }
}
