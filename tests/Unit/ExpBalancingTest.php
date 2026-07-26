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
}
