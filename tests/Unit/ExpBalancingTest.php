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

    public function test_level_99_is_max_level_and_exp_keeps_accumulating_toward_champion_target(): void
    {
        $service = new LevelUpService();
        $championService = app(\App\Application\Mastery\ChampionService::class);

        $xpFor99 = $service->xpToNext(99);
        $this->assertGreaterThan(0, $xpFor99);

        $user = \App\Models\User::factory()->create();
        $char = Character::create([
            'user_id' => $user->id,
            'name' => 'MaxHero',
            'level' => 98,
            // Faza 1 rebalansu (x6 na krzywej XP): xpToNext(98)=~132.4M, xpToNext(99)=~138.1M
            // (wcześniej ~22.1M/~23.0M).
            'xp' => 300000000,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $res = $service->checkAndApply($char);
        $this->assertTrue($res->isOk());
        $char->refresh();

        // Level caps at 99, ale system Mistrzostwa (docs/modules/mastery.md) sprawia,
        // że nadwyżka XP ponad stary próg xpToNext(99) NIE jest już tracona - zostaje
        // w tym samym liczniku `xp` (leftover = 300M - xpToNext(98)), znacznie
        // przekraczając stary cap xpFor99, bo cel Mistrzostwa jest dużo wyższy.
        $this->assertEquals(99, $char->level);
        $leftover = 300000000 - $service->xpToNext(98);
        $this->assertEquals($leftover, $char->xp);
        $this->assertGreaterThan($xpFor99, $char->xp);

        // Dalszy przyrost expa nadal się kumuluje (nie jest przycinany do starego capu).
        $char->update(['xp' => $char->xp + 5000]);
        $res2 = $service->checkAndApply($char);
        $this->assertTrue($res2->isOk());
        $char->refresh();

        $this->assertEquals(99, $char->level);
        $this->assertEquals($leftover + 5000, $char->xp);
        $this->assertFalse($res2->getPayload()->hadLevelUp);

        // Ale ostatecznie licznik jest przycinany dokładnie do progu Mistrzostwa
        // (nie o 1 mniej - inaczej `ChampionService::attemptLevelUp()` nigdy by
        // realnie nie zaakceptował pełnego paska, patrz test w ChampionLevelUpTest).
        $char->update(['xp' => $championService->xpTarget() + 999_999_999]);
        $service->checkAndApply($char);
        $char->refresh();
        $this->assertEquals($championService->xpTarget(), $char->xp);
    }
}
