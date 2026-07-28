<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Application\Characters\LevelUpService;
use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\Character;

use Illuminate\Foundation\Testing\RefreshDatabase;

class ExpBalancingTest extends TestCase
{
    use RefreshDatabase;
    public function test_xp_to_next_calculates_correct_quadratic_values(): void
    {
        $service = new LevelUpService();

        // Level 1: 65
        $this->assertEquals(65, $service->xpToNext(1));

        // Level 3: 299
        $this->assertEquals(299, $service->xpToNext(3));

        // Level 10: 3888
        $this->assertEquals(3888, $service->xpToNext(10));

        // Level 50: 1426335
        $this->assertEquals(1426335, $service->xpToNext(50));

        // Level 70: 5584991
        $this->assertEquals(5584991, $service->xpToNext(70));

        // Level 80: 9622702
        $this->assertEquals(9622702, $service->xpToNext(80));
    }

    public function test_monster_xp_reward_scales_progressively(): void
    {
        $encounterService = new EncounterService();
        $reflection = new \ReflectionClass($encounterService);
        $method = $reflection->getMethod('calculateXpReward');
        $method->setAccessible(true);

        $char = new Character(['level' => 1]);

        $monsterLvl1 = new Monster(['level' => 1]);
        $reward1 = $method->invoke($encounterService, $monsterLvl1, $char);
        // 25 * 1^1.2 + 30 = 55
        $this->assertEquals(55, $reward1['base']);

        $monsterLvl3 = new Monster(['level' => 3]);
        $reward3 = $method->invoke($encounterService, $monsterLvl3, $char);
        $this->assertGreaterThan(50, $reward3['base']);
        $this->assertLessThan(200, $reward3['base']);
    }

    public function test_level_99_is_max_level_and_caps_exp_at_99_percent(): void
    {
        $service = new LevelUpService();

        $xpFor99 = $service->xpToNext(99);
        $this->assertGreaterThan(0, $xpFor99);

        $user = \App\Models\User::factory()->create();
        $char = Character::create([
            'user_id' => $user->id,
            'name' => 'MaxHero',
            'level' => 98,
            'xp' => 100000000,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $res = $service->checkAndApply($char);
        $this->assertTrue($res->isOk());
        $char->refresh();

        // Level must cap at 99 and XP must cap at xpToNext(99) - 1
        $this->assertEquals(99, $char->level);
        $this->assertEquals($xpFor99 - 1, $char->xp);

        // Level 99 character starting with 1000 XP can gain XP up to cap
        $char->update(['xp' => 1000]);
        $char->refresh();

        $char->update(['xp' => $char->xp + 5000]);
        $res2 = $service->checkAndApply($char);
        $this->assertTrue($res2->isOk());
        $char->refresh();

        $this->assertEquals(99, $char->level);
        $this->assertEquals(6000, $char->xp);
        $this->assertFalse($res2->getPayload()->hadLevelUp);
    }
}
