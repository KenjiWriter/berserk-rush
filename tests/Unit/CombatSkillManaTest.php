<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CombatSkillManaTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_get_max_mana_calculation(): void
    {
        $user = User::factory()->create();

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'ManaTester',
            'level' => 10,
            'xp' => 0,
            'gold' => 1000,
            'attributes' => [
                'str' => 5,
                'int' => 20,
                'vit' => 10,
                'agi' => 5,
            ],
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        // Max Mana = 50 + (INT * 10) + (Level * 3) + mana_bonus
        // 50 + (20 * 10) + (10 * 3) + 0 = 50 + 200 + 30 = 280
        $this->assertEquals(280, $character->getMaxMana());
    }

    public function test_combat_skill_get_mana_cost(): void
    {
        $activeSkill = CombatSkill::create([
            'name' => 'Kula Ognia Test',
            'description' => 'Test',
            'type' => 'active',
            'required_weapon_type' => 'wand',
            'effect_type' => 'direct_dmg',
            'base_cooldown' => 4,
            'base_duration' => 1,
            'base_value' => 1.5,
            'scaling_value' => 0.1,
            'base_mana_cost' => 20,
            'scaling_mana_cost' => 5,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        $this->assertEquals(20, $activeSkill->getManaCost(1));
        $this->assertEquals(25, $activeSkill->getManaCost(2));
        $this->assertEquals(40, $activeSkill->getManaCost(5));

        $passiveSkill = CombatSkill::create([
            'name' => 'Aura Test',
            'description' => 'Test',
            'type' => 'passive',
            'required_weapon_type' => 'sword',
            'effect_type' => 'passive_aura_dmg',
            'base_cooldown' => 0,
            'base_duration' => 0,
            'base_value' => 0.1,
            'scaling_value' => 0.02,
            'base_mana_cost' => 50,
            'scaling_mana_cost' => 10,
            'required_level' => 10,
            'unlock_cost' => 5,
        ]);

        // Passive skill cost is always 0
        $this->assertEquals(0, $passiveSkill->getManaCost(1));
    }

    public function test_persistent_mana_and_regen_rate(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'ManaTestPersist',
            'level' => 10,
            'xp' => 0,
            'gold' => 1000,
            'attributes' => ['str' => 5, 'int' => 0, 'vit' => 10, 'agi' => 5],
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        $map = \App\Infrastructure\Persistence\Map::create([
            'name' => 'Test Map Mana',
            'level_min' => 1,
            'level_max' => 20,
        ]);

        $monster = \App\Infrastructure\Persistence\Monster::create([
            'name' => 'Test Mob Mana',
            'level' => 10,
            'rank' => 'regular',
            'type' => 'animal',
            'stats' => ['hp' => 500, 'atk' => 10, 'def' => 5, 'agi' => 1],
            'map_id' => $map->id,
        ]);

        $service = app(\App\Application\Combat\EncounterService::class);
        $res = $service->start($character, $map, $monster, 'random', 20);
        $this->assertTrue($res->isOk());

        $encounter = $res->getPayload();
        $this->assertEquals(20, $encounter->combat_data['initial_mana']);

        $simRes = $service->simulate($encounter);
        $this->assertTrue($simRes->isOk());

        // Max mana for level 10 with 0 INT is 80.
        // Starting at 20 MP, regen is 5% of 80 = 4 MP per player turn.
        // First player turn: 20 + 4 = 24 MP.
        $turns = $simRes->getPayload()['turns'];
        $firstPlayerTurn = collect($turns)->firstWhere('actor', 'player');
        $this->assertNotNull($firstPlayerTurn);
        $this->assertEquals(24, $firstPlayerTurn['playerMana']);
    }
}
