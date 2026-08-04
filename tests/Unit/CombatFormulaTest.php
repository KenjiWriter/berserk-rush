<?php

namespace Tests\Unit;

use App\Application\Combat\EncounterService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Monster;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;

class CombatFormulaTest extends TestCase
{
    use RefreshDatabase;
    public function test_roll_critical_is_not_reduced_by_monster_agi(): void
    {
        $character = new Character();
        $character->attributes = ['str' => 10, 'agi' => 50, 'vit' => 10, 'int' => 10];
        $character->level = 10;

        $monsterLowAgi = new Monster();
        $monsterLowAgi->level = 10;
        $monsterLowAgi->stats = ['hp' => 100, 'atk' => 10, 'def' => 5, 'agi' => 1, 'int' => 1];

        $monsterHighAgi = new Monster();
        $monsterHighAgi->level = 10;
        $monsterHighAgi->stats = ['hp' => 100, 'atk' => 10, 'def' => 5, 'agi' => 1000, 'int' => 1];

        $service = app(EncounterService::class);
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('rollCritical');
        $method->setAccessible(true);

        $successLow = 0;
        $successHigh = 0;
        $iterations = 5000;

        for ($i = 0; $i < $iterations; $i++) {
            if ($method->invoke($service, $character, $monsterLowAgi)) {
                $successLow++;
            }
            if ($method->invoke($service, $character, $monsterHighAgi)) {
                $successHigh++;
            }
        }

        $rateLow = $successLow / $iterations;
        $rateHigh = $successHigh / $iterations;

        $this->assertEqualsWithDelta(0.125, $rateLow, 0.03);
        $this->assertEqualsWithDelta(0.125, $rateHigh, 0.03);
    }
}
