<?php

namespace Tests\Feature;

use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\Encounter;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WorldBossSkillDamageTest extends TestCase
{
    use RefreshDatabase;

    public function test_world_boss_percent_hp_skills_are_capped(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'BossSlayer',
            'class' => 'mage',
            'level' => 10,
            'experience' => 0,
            'gold' => 100,
        ]);

        $map = Map::create([
            'id' => (string) Str::ulid(),
            'name' => 'Test Map',
            'level_min' => 1,
            'level_max' => 20,
            'tier' => 1,
        ]);

        $monster = Monster::create([
            'id' => (string) Str::ulid(),
            'map_id' => $map->id,
            'name' => 'Król Testów',
            'type' => 'animal',
            'level' => 10,
            'rank' => 'worldboss',
            'stats' => ['hp' => 14000, 'atk' => 42, 'def' => 22, 'agi' => 14, 'int' => 8, 'crit' => 0.4, 'dodge' => 0.08],
        ]);

        $fireSkill = CombatSkill::create([
            'id' => (string) Str::ulid(),
            'name' => 'Kula Ognia',
            'description' => 'Fireball',
            'type' => 'active',
            'required_weapon_type' => 'all',
            'effect_type' => 'fire',
            'base_cooldown' => 1,
            'base_duration' => 3,
            'base_value' => 0.05, // 5% max HP per tick
            'scaling_value' => 0.01,
            'required_level' => 1,
            'unlock_cost' => 1,
            'icon' => 'fireball.png',
        ]);

        CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $fireSkill->id,
            'level' => 1,
            'is_equipped' => true,
            'equip_slot' => 1,
        ]);

        $encounter = Encounter::create([
            'character_id' => $character->id,
            'map_id' => $map->id,
            'monster_id' => $monster->id,
            'state' => 'ongoing',
            'gold_reward' => 0,
            'xp_reward' => 0,
            'player_first' => true,
            'turns' => [],
            'combat_data' => [],
            'rewards_applied' => false,
            'started_at' => now(),
        ]);

        $service = app(EncounterService::class);
        $result = $service->simulate($encounter);

        $this->assertTrue($result->isOk());

        $data = $result->getPayload();
        $damageDealt = $data['rewards']['damage_dealt'];

        // With capping, 20 turns of fighting a World Boss at level 10 should deal reasonable damage (< 100,000)
        // rather than 500,000,000+
        $this->assertGreaterThan(0, $damageDealt);
        $this->assertLessThan(100000, $damageDealt, "World Boss damage dealt ({$damageDealt}) exceeds cap expected threshold");

        // Verify individual DoT ticks in turns are capped properly
        foreach ($data['turns'] as $turn) {
            if (isset($turn['dotDamage'])) {
                $this->assertLessThan(10000, $turn['dotDamage'], "Individual DoT tick ({$turn['dotDamage']}) was not capped");
            }
        }
    }
}
