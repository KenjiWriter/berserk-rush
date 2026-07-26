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

        // Level 1: 25 * 1^2 + 25 * 1 = 50
        $this->assertEquals(50, $service->xpToNext(1));

        // Level 2: 25 * 2^2 + 25 * 2 = 150
        $this->assertEquals(150, $service->xpToNext(2));

        // Level 3: 25 * 3^2 + 25 * 3 = 300
        $this->assertEquals(300, $service->xpToNext(3));

        // Level 5: 25 * 5^2 + 25 * 5 = 750
        $this->assertEquals(750, $service->xpToNext(5));

        // Level 10: 25 * 10^2 + 25 * 10 = 2750
        $this->assertEquals(2750, $service->xpToNext(10));

        // Level 50: 25 * 50^2 + 25 * 50 = 63750
        $this->assertEquals(63750, $service->xpToNext(50));
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
        // 12 * 1^1.15 + 15 = 27
        $this->assertEquals(27, $reward1['base']);

        $monsterLvl3 = new Monster(['level' => 3]);
        $reward3 = $method->invoke($encounterService, $monsterLvl3, $char);
        // 12 * 3^1.15 + 15 + levelDiff bonus (3 - 1 = 2 -> +20%) => (12 * 3.55 + 15) * 1.2 = 57 * 1.2 = 69
        $this->assertGreaterThan(50, $reward3['base']);
        $this->assertLessThan(100, $reward3['base']);
    }
}
