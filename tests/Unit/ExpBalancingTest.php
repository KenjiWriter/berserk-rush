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

        // Level 1: 100
        $this->assertEquals(100, $service->xpToNext(1));

        // Level 3: 482
        $this->assertEquals(482, $service->xpToNext(3));

        // Level 10: 7656
        $this->assertEquals(7656, $service->xpToNext(10));

        // Level 50: 3301032 (5-8 min)
        $this->assertEquals(3301032, $service->xpToNext(50));

        // Level 70: 12979729 (30 min)
        $this->assertEquals(12979729, $service->xpToNext(70));

        // Level 80: 22385639 (1h)
        $this->assertEquals(22385639, $service->xpToNext(80));
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
        // 15 * 1^1.2 + 20 = 35
        $this->assertEquals(35, $reward1['base']);

        $monsterLvl3 = new Monster(['level' => 3]);
        $reward3 = $method->invoke($encounterService, $monsterLvl3, $char);
        // 12 * 3^1.15 + 15 + levelDiff bonus (3 - 1 = 2 -> +20%) => (12 * 3.55 + 15) * 1.2 = 57 * 1.2 = 69
        $this->assertGreaterThan(50, $reward3['base']);
        $this->assertLessThan(100, $reward3['base']);
    }
}
