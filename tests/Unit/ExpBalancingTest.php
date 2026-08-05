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

        // Faza 1 rebalansu (2026-08-05): krzywa przemnożona globalnie x6 (patrz
        // LevelUpService::XP_CURVE_MULTIPLIER), żeby spowolnić tempo progresji -
        // wartości bazowe (przed mnożnikiem) pozostają identyczne co wcześniej.

        // Wartości = round(base * 6) - liczone na nieprzybliżonym `base`, więc NIE są
        // dokładnie "stara zaokrąglona wartość razy 6" (podwójne zaokrąglanie).
        // Level 1
        $this->assertEquals(391, $service->xpToNext(1));

        // Level 3
        $this->assertEquals(1791, $service->xpToNext(3));

        // Level 10
        $this->assertEquals(23330, $service->xpToNext(10));

        // Level 50
        $this->assertEquals(8558012, $service->xpToNext(50));

        // Level 70
        $this->assertEquals(33509946, $service->xpToNext(70));

        // Level 80
        $this->assertEquals(57736214, $service->xpToNext(80));
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
            // Faza 1 rebalansu (x6 na krzywej XP): xpToNext(98)=~132.4M, xpToNext(99)=~138.1M
            // (wcześniej ~22.1M/~23.0M) - musi przekraczać SUMĘ obu, by po awansie na 99
            // zostająca nadwyżka XP nadal przekraczała cap i realnie ćwiczyła capowanie
            // (nie tylko sam awans z 98 na 99).
            'xp' => 300000000,
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
