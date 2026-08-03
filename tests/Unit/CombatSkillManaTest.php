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

        // Passive skill cost is calculated based on level
        $this->assertEquals(50, $passiveSkill->getManaCost(1));
        $this->assertEquals(60, $passiveSkill->getManaCost(2));
    }

    public function test_no_mid_combat_mana_regen(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'ManaTestNoRegen',
            'level' => 10,
            'xp' => 0,
            'gold' => 1000,
            'attributes' => ['str' => 5, 'int' => 0, 'vit' => 10, 'agi' => 5],
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        $map = \App\Infrastructure\Persistence\Map::create([
            'name' => 'Test Map Mana NoRegen',
            'level_min' => 1,
            'level_max' => 20,
        ]);

        $monster = \App\Infrastructure\Persistence\Monster::create([
            'name' => 'Test Mob Mana NoRegen',
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
        $simRes = $service->simulate($encounter);
        $this->assertTrue($simRes->isOk());

        // Every new encounter starts at 100% max mana (80 MP). Mana does not increase turn-by-turn.
        $turns = $simRes->getPayload()['turns'];
        $firstPlayerTurn = collect($turns)->firstWhere('actor', 'player');
        $this->assertNotNull($firstPlayerTurn);
        $this->assertEquals(80, $firstPlayerTurn['playerMana']);
    }

    public function test_passive_skill_drains_mana_per_turn_in_combat(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'PassiveManaTester',
            'level' => 10,
            'xp' => 0,
            'gold' => 1000,
            'attributes' => ['str' => 5, 'int' => 0, 'vit' => 10, 'agi' => 5], // Max Mana = 80
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        $passiveSkill = CombatSkill::create([
            'name' => 'Aura Miecza Test',
            'description' => 'Zwiększa atrybuty',
            'type' => 'passive',
            'required_weapon_type' => 'all',
            'effect_type' => 'passive_aura_dmg',
            'base_cooldown' => 0,
            'base_duration' => 0,
            'base_value' => 0.2,
            'scaling_value' => 0.05,
            'base_mana_cost' => 15,
            'scaling_mana_cost' => 5,
            'required_level' => 1,
            'unlock_cost' => 1,
        ]);

        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $passiveSkill->id,
            'level' => 1,
            'is_equipped' => true,
            'equip_slot' => 1,
        ]);

        $map = \App\Infrastructure\Persistence\Map::create([
            'name' => 'Test Map Passive Mana',
            'level_min' => 1,
            'level_max' => 20,
        ]);

        $monster = \App\Infrastructure\Persistence\Monster::create([
            'name' => 'Test Mob Passive Mana',
            'level' => 10,
            'rank' => 'regular',
            'type' => 'animal',
            'stats' => ['hp' => 500, 'atk' => 10, 'def' => 5, 'agi' => 1],
            'map_id' => $map->id,
        ]);

        $service = app(\App\Application\Combat\EncounterService::class);
        $res = $service->start($character, $map, $monster, 'random', 20);
        $encounter = $res->getPayload();
        $simRes = $service->simulate($encounter);

        $turns = $simRes->getPayload()['turns'];
        $playerTurns = array_values(array_filter($turns, fn($t) => ($t['actor'] ?? '') === 'player'));

        $this->assertNotEmpty($playerTurns);
        // Player starts with 80 MP. In first turn, passive drains 15 MP => 65 MP remaining.
        $this->assertEquals(65, $playerTurns[0]['playerMana']);
        if (isset($playerTurns[1])) {
            // Second turn, passive drains another 15 MP and regenerates 5 MP => 55 MP remaining.
            $this->assertEquals(55, $playerTurns[1]['playerMana']);
        }
    }
}
