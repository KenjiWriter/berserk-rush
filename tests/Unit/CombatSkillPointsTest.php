<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
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

        // Level 30 -> 29 * 3 = 87 earned points.
        // Rebalans 2026-08-06: próg M1 obniżony z poziomu 17 na 6 (patrz
        // Character::syncMissingPoints() / CharacterCombatSkill::getTier()).
        // Spent: unlockCost (5) + max SP upgrades min(6, 100)-1 (5) = 10 points. Remaining: 87 - 10 = 77 points.
        $this->assertEquals(77, $character->fresh()->skill_points);
    }

    public function test_upgrade_skill_blocks_upgrade_above_level_27()
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
            'level' => 27,
            'is_equipped' => true,
        ]);

        $upgrader = new UpgradeSkill();
        $result = $upgrader->execute($character, $charSkill);

        $this->assertTrue($result->isError());
        $this->assertEquals('MAX_LEVEL_REACHED', $result->getErrorCode());
        $this->assertEquals(27, $charSkill->fresh()->level);
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

    public function test_skills_reset_all_refunds_books_and_stones_spent_under_old_tiers()
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'InvestedWarlock',
            'level' => 40,
            'skill_points' => 0,
        ]);

        ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Księga Walki Mieczem',
            'type' => 'material',
            'sub_type' => 'skill_book_sword',
            'level_requirement' => 1,
        ]);

        ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Kamień Duchowy',
            'type' => 'material',
            'sub_type' => 'soul_stone',
            'level_requirement' => 1,
        ]);

        $swordSkill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Wirujący Miecz',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'sword',
            'effect_type' => 'direct_dmg',
            'base_cooldown' => 5,
            'base_duration' => 1,
            'base_value' => 1.40,
            'scaling_value' => 0.15,
            'required_level' => 10,
            'unlock_cost' => 15,
        ]);

        // Stary układ (przed rebalansem 2026-08-06): M start=17, G start=27, max=38.
        // Poziom 22 -> 5 zakupów księgi (22-17), 0 kamieni (poniżej progu G=27).
        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $swordSkill->id,
            'level' => 22,
            'is_equipped' => true,
        ]);

        $daggerSkill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Szybkie Cięcie',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'dagger',
            'effect_type' => 'direct_dmg',
            'base_cooldown' => 3,
            'base_duration' => 1,
            'base_value' => 1.35,
            'scaling_value' => 0.15,
            'required_level' => 1,
            'unlock_cost' => 5,
        ]);

        ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Księga Mistrzostwa Sztyletów',
            'type' => 'material',
            'sub_type' => 'skill_book_dagger',
            'level_requirement' => 1,
        ]);

        // Poziom 38 (stary max/Perfect) -> 10 zakupów księgi (pełny etap M), 11 kamieni (pełny etap G).
        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $daggerSkill->id,
            'level' => 38,
            'is_equipped' => true,
        ]);

        $this->artisan('skills:reset-all')->assertExitCode(0);

        $this->assertEquals(0, CharacterCombatSkill::count());

        $swordBooks = ItemInstance::where('owner_character_id', $character->id)
            ->whereHas('template', fn($q) => $q->where('name', 'Księga Walki Mieczem'))
            ->first();
        $this->assertNotNull($swordBooks);
        $this->assertEquals(5, $swordBooks->stack_size);

        $daggerBooks = ItemInstance::where('owner_character_id', $character->id)
            ->whereHas('template', fn($q) => $q->where('name', 'Księga Mistrzostwa Sztyletów'))
            ->first();
        $this->assertNotNull($daggerBooks);
        $this->assertEquals(10, $daggerBooks->stack_size);

        $stones = ItemInstance::where('owner_character_id', $character->id)
            ->whereHas('template', fn($q) => $q->where('name', 'Kamień Duchowy'))
            ->first();
        $this->assertNotNull($stones);
        $this->assertEquals(11, $stones->stack_size);
    }
}
