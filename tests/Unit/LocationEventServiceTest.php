<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Infrastructure\Persistence\LocationEvent;
use App\Infrastructure\Persistence\LocationEventUpgradeLevel;
use App\Infrastructure\Persistence\CharacterLocationEventRun;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\LocationEvents\LocationEventService;
use Database\Seeders\LocationEventSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class LocationEventServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(LocationEventSeeder::class);
    }

    private function makeCharacter(array $overrides = []): Character
    {
        $user = User::factory()->create(['game_stage' => 20]);

        return Character::create(array_merge([
            'user_id' => $user->id,
            'name' => 'EventTester' . Str::random(6),
            'class' => 'warrior',
            'level' => 30,
            'experience' => 0,
            'gold' => 0,
        ], $overrides));
    }

    private function makeMap(string $name = 'Mroczny Las'): Map
    {
        return Map::firstOrCreate(['name' => $name], [
            'level_min' => 0,
            'level_max' => 99,
            'tier' => 1,
        ]);
    }

    public function test_roll_event_trigger_respects_probability_distribution(): void
    {
        $service = new LocationEventService();

        $iterations = 150000;
        $counts = array_fill(1, 6, 0);
        $totalEvents = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $result = $service->rollEventTrigger();
            if ($result === null) {
                continue;
            }
            $totalEvents++;
            $counts[$result['event']->rank]++;
        }

        // Sumaryczna szansa na JAKIKOLWIEK event jest przeskalowana do stałej
        // TARGET_TOTAL_SPAWN_CHANCE_PCT (~1%, kalibrowane pod ~1 event/5min - patrz
        // LocationEventService) - NIE do sumy bazowych spawn_chance_pct z arkusza (56%).
        $actualTotalPct = ($totalEvents / $iterations) * 100;
        $this->assertEqualsWithDelta(1.0, $actualTotalPct, 0.3);

        // Relatywne proporcje MIĘDZY rangami (warunkowo, wśród przypadków gdy event
        // faktycznie wypadł) muszą nadal odzwierciedlać oryginalne wagi z arkusza
        // (20:15:10:5:4:2), niezależnie od globalnego przeskalowania.
        if ($totalEvents > 0) {
            $expectedRatios = [1 => 20 / 56, 2 => 15 / 56, 3 => 10 / 56, 4 => 5 / 56, 5 => 4 / 56, 6 => 2 / 56];
            foreach ($expectedRatios as $rank => $expectedRatio) {
                $actualRatio = $counts[$rank] / $totalEvents;
                $this->assertEqualsWithDelta($expectedRatio, $actualRatio, 0.08, "Rank {$rank} conditional ratio off");
            }
        }
    }

    public function test_roll_upgrade_level_respects_probability_distribution(): void
    {
        $service = new LocationEventService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('rollUpgradeLevel');
        $method->setAccessible(true);

        $iterations = 20000;
        $counts = array_fill(0, 6, 0);

        for ($i = 0; $i < $iterations; $i++) {
            $level = $method->invoke($service);
            $counts[$level->level]++;
        }

        $expected = [0 => 0.30, 1 => 0.25, 2 => 0.18, 3 => 0.12, 4 => 0.09, 5 => 0.06];
        $tolerance = 0.02;

        foreach ($expected as $level => $expectedPct) {
            $actualPct = $counts[$level] / $iterations;
            $this->assertEqualsWithDelta($expectedPct, $actualPct, $tolerance, "Upgrade level {$level} distribution off");
        }
    }

    public function test_scaled_monster_stats_apply_rank_and_upgrade_multipliers(): void
    {
        $service = new LocationEventService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('getScaledMonsterStats');
        $method->setAccessible(true);

        $character = new Character(['level' => 1]);
        $monster = new Monster(['level' => 1, 'stats' => ['hp' => 1000, 'atk' => 100, 'def' => 50, 'agi' => 10]]);

        // Rank T6 (attack x1.30) + poziom ulepszenia 5 (attack x1.70, hp x2.00)
        $scaled = $method->invoke($service, $monster, $character, 1.30 * 1.70, 2.00);

        $this->assertEquals((int) round(1000 * 2.00), $scaled['hp']);
        $this->assertEquals((int) round(100 * 1.30 * 1.70), $scaled['atk']);
        $this->assertEquals((int) round(50 * 1.30 * 1.70), $scaled['def']);
        $this->assertEquals(10, $scaled['agi']); // agi nie skaluje się mnożnikami eventu
    }

    public function test_hardcore_reward_multiplier_increases_average_rewards(): void
    {
        $service = new LocationEventService();
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('calculateSlotLoot');
        $method->setAccessible(true);

        $character = $this->makeCharacter(['level' => 10]);
        $monster = new Monster(['level' => 10]);
        $event = LocationEvent::where('rank', 1)->first();
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 0)->first();

        $iterations = 500;
        $normalGoldTotal = 0;
        $hardcoreGoldTotal = 0;
        $normalXpTotal = 0;
        $hardcoreXpTotal = 0;

        for ($i = 0; $i < $iterations; $i++) {
            $normal = $method->invoke($service, $character, $monster, $event, $upgradeLevel, false, false, 1);
            $hardcore = $method->invoke($service, $character, $monster, $event, $upgradeLevel, true, false, 1);
            $normalGoldTotal += $normal['gold'];
            $hardcoreGoldTotal += $hardcore['gold'];
            $normalXpTotal += $normal['xp'];
            $hardcoreXpTotal += $hardcore['xp'];
        }

        // Bonus hardcore = x1.5 (HARDCORE_REWARD_BONUS_MULTIPLIER), z tolerancją na wariancję
        // losowania bazowego gold/xp (mt_rand 80-120%).
        $goldRatio = $hardcoreGoldTotal / $normalGoldTotal;
        $xpRatio = $hardcoreXpTotal / $normalXpTotal;

        $this->assertEqualsWithDelta(1.5, $goldRatio, 0.1);
        $this->assertEqualsWithDelta(1.5, $xpRatio, 0.1);
    }

    public function test_start_run_monster_count_within_rank_and_upgrade_range_and_last_slot_is_boss(): void
    {
        $character = $this->makeCharacter();
        $map = $this->makeMap();

        Monster::create(['map_id' => $map->id, 'name' => 'Wilk Leśny', 'level' => 3, 'rank' => 'regular', 'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5]]);
        Monster::create(['map_id' => $map->id, 'name' => 'Boss Testowy', 'level' => 10, 'rank' => 'boss', 'stats' => ['hp' => 500, 'atk' => 30, 'def' => 10, 'agi' => 5]]);

        $event = LocationEvent::where('rank', 4)->first(); // T4: count 5-8
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 3)->first(); // delta +1..+2

        $service = app(LocationEventService::class);

        for ($i = 0; $i < 15; $i++) {
            $result = $service->startRun($character, $map, $event, $upgradeLevel, false);
            $this->assertTrue($result->isOk());
            $run = $result->getPayload();

            $this->assertGreaterThanOrEqual(6, $run->total_monsters); // 5+1
            $this->assertLessThanOrEqual(10, $run->total_monsters); // 8+2

            $queue = $run->monsters_queue;
            $this->assertCount($run->total_monsters, $queue);
            $lastSlot = end($queue);
            $this->assertTrue($lastSlot['is_boss']);

            $run->update(['is_completed' => true]); // zwolnij blokadę "aktywny run" przed kolejną iteracją
        }
    }

    public function test_second_active_run_is_blocked(): void
    {
        $character = $this->makeCharacter();
        $map = $this->makeMap();
        Monster::create(['map_id' => $map->id, 'name' => 'Wilk Leśny', 'level' => 3, 'rank' => 'regular', 'stats' => ['hp' => 50, 'atk' => 8, 'def' => 2, 'agi' => 5]]);

        $event = LocationEvent::where('rank', 1)->first();
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 0)->first();
        $service = app(LocationEventService::class);

        $first = $service->startRun($character, $map, $event, $upgradeLevel, false);
        $this->assertTrue($first->isOk());

        $second = $service->startRun($character, $map, $event, $upgradeLevel, false);
        $this->assertTrue($second->isError());
        $this->assertEquals('ACTIVE_RUN', $second->getErrorCode());
    }

    public function test_normal_mode_resets_hp_between_monsters_while_hardcore_persists_it(): void
    {
        $map = $this->makeMap();

        // Postać wyraźnie silniejsza od potwora, ale z niską witalnością/unikiem, żeby
        // potwór realnie zdążył trafić kilka razy zanim padnie (3+ tury walki).
        $weakMonster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Słaby Testowy Potwór',
            'level' => 5,
            'rank' => 'regular',
            'stats' => ['hp' => 200, 'atk' => 30, 'def' => 0, 'agi' => 1],
        ]);

        $event = LocationEvent::where('rank', 1)->first(); // T1: 2-5 potworów
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 0)->first();
        $service = app(LocationEventService::class);

        // --- Tryb normalny: current_hp MUSI wrócić do maxa po pierwszej walce ---
        $normalChar = $this->makeCharacter(['attributes' => ['str' => 5, 'int' => 0, 'vit' => 10, 'agi' => 5]]);
        $normalRun = $service->startRun($normalChar, $map, $event, $upgradeLevel, false)->getPayload();

        $simResult = $service->simulateStage($normalRun);
        $this->assertTrue($simResult->isOk(), 'Normal-mode first fight should be won');
        $normalRun->refresh();

        $this->assertEquals($normalChar->getMaxHp(), $normalRun->current_hp, 'Normal mode must reset HP to max between monsters');

        // --- Tryb hardcore: current_hp NIE resetuje się, powinno być < max po walce ---
        $hardcoreChar = $this->makeCharacter(['attributes' => ['str' => 5, 'int' => 0, 'vit' => 10, 'agi' => 5]]);
        $hardcoreRun = $service->startRun($hardcoreChar, $map, $event, $upgradeLevel, true)->getPayload();

        $simResult2 = $service->simulateStage($hardcoreRun);
        $this->assertTrue($simResult2->isOk(), 'Hardcore-mode first fight should be won');
        $hardcoreRun->refresh();

        $this->assertLessThan($hardcoreChar->getMaxHp(), $hardcoreRun->current_hp, 'Hardcore mode must persist HP damage taken between monsters');
    }

    public function test_losing_a_fight_fails_run_and_forfeits_accumulated_loot(): void
    {
        $map = $this->makeMap();

        // Postać rażąco słabsza od potwora - musi przegrać pierwszą walkę.
        $character = $this->makeCharacter(['level' => 1, 'attributes' => ['str' => 1, 'int' => 0, 'vit' => 1, 'agi' => 1]]);
        $goldBefore = $character->gold;

        $strongMonster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Miażdżący Testowy Potwór',
            'level' => 50,
            'rank' => 'regular',
            'stats' => ['hp' => 100000, 'atk' => 100000, 'def' => 0, 'agi' => 1],
        ]);

        $event = LocationEvent::where('rank', 1)->first();
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 0)->first();
        $service = app(LocationEventService::class);

        $run = $service->startRun($character, $map, $event, $upgradeLevel, false)->getPayload();
        $result = $service->simulateStage($run);

        $this->assertTrue($result->isOk());
        $this->assertEquals('lose', $result->getPayload()['result']);

        $run->refresh();
        $this->assertTrue($run->is_failed);
        $this->assertFalse($run->is_completed);

        $character->refresh();
        $this->assertEquals($goldBefore, $character->gold, 'Loot must not be granted when the run fails');
    }

    public function test_completing_run_grants_gold_xp_and_map_themed_chest(): void
    {
        $map = $this->makeMap('Mroczny Las');

        ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Skrzynia Mrocznego Lasu',
            'type' => 'consumable',
            'sub_type' => 'chest',
            'slot' => 'consumable',
        ]);

        // Postać zdecydowanie silniejsza od potworów - musi wygrać cały łańcuch.
        $character = $this->makeCharacter(['level' => 50, 'attributes' => ['str' => 500, 'int' => 0, 'vit' => 200, 'agi' => 50]]);
        $goldBefore = $character->gold;

        Monster::create([
            'map_id' => $map->id,
            'name' => 'Trywialny Testowy Potwór',
            'level' => 1,
            'rank' => 'regular',
            'stats' => ['hp' => 10, 'atk' => 1, 'def' => 0, 'agi' => 1],
        ]);

        $event = LocationEvent::where('rank', 1)->first(); // T1, chest 0-1
        $upgradeLevel = LocationEventUpgradeLevel::where('level', 0)->first(); // chest bonus 1-1
        $service = app(LocationEventService::class);

        $run = $service->startRun($character, $map, $event, $upgradeLevel, false)->getPayload();

        $result = null;
        $guard = 0;
        do {
            $result = $service->simulateStage($run);
            $this->assertTrue($result->isOk());
            $run->refresh();
            $guard++;
        } while (!$run->is_completed && !$run->is_failed && $guard < 20);

        $this->assertTrue($run->is_completed, 'Overwhelmingly stronger character must complete the event');

        $character->refresh();
        $this->assertGreaterThan($goldBefore, $character->gold);

        $chestItem = \App\Infrastructure\Persistence\ItemInstance::where('owner_character_id', $character->id)
            ->whereHas('template', fn ($q) => $q->where('name', 'Skrzynia Mrocznego Lasu'))
            ->first();

        $this->assertNotNull($chestItem, 'Completing the event must grant the map-themed chest');
        // chest_min(0)+chest_bonus_min(1) .. chest_max(1)+chest_bonus_max(1)
        $this->assertGreaterThanOrEqual(1, $chestItem->stack_size);
        $this->assertLessThanOrEqual(2, $chestItem->stack_size);
    }
}
