<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Application\PvP\PvPEncounterService;
use App\Application\Combat\EncounterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BattleQueueingTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_queue_multiple_pvp_fights_concurrently(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $attacker = Character::create([
            'user_id' => $user1->id,
            'name' => 'Attacker',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $defender1 = Character::create([
            'user_id' => $user2->id,
            'name' => 'Defender1',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $defender2 = Character::create([
            'user_id' => $user3->id,
            'name' => 'Defender2',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $service = app(PvPEncounterService::class);

        // First challenge should succeed
        $res1 = $service->startEncounter($attacker, $defender1);
        $this->assertTrue($res1->isOk());

        // Immediate second challenge (within 5s cooldown/active fight block) should fail
        $res2 = $service->startEncounter($attacker, $defender2);
        $this->assertTrue($res2->isError());
        $this->assertEquals('COMBAT_IN_PROGRESS', $res2->getErrorCode());
    }

    public function test_cannot_queue_multiple_pve_fights_concurrently(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Hero',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $map = Map::create([
            'name' => 'Test Map',
            'level_min' => 1,
            'level_max' => 5,
        ]);

        $monster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Goblin',
            'level' => 1,
            'stats' => ['hp' => 20, 'atk' => 5, 'def' => 1, 'agi' => 1],
        ]);

        $service = app(EncounterService::class);

        // First encounter start should succeed
        $res1 = $service->start($character, $map);
        $this->assertTrue($res1->isOk());

        $encounter = $res1->getPayload();
        $service->simulate($encounter);

        // Rewards are not applied yet (rewards_applied == false)
        $this->assertFalse($encounter->fresh()->rewards_applied);

        // Second encounter attempt BEFORE rewards_applied should fail with COMBAT_IN_PROGRESS
        $res2 = $service->start($character, $map);
        $this->assertTrue($res2->isError());
        $this->assertEquals('COMBAT_IN_PROGRESS', $res2->getErrorCode());

        // Now apply rewards for the first encounter
        $service->applyRewards($encounter);
        $this->assertTrue($encounter->fresh()->rewards_applied);

        // After rewards applied, starting another encounter should succeed!
        $res3 = $service->start($character, $map);
        $this->assertTrue($res3->isOk());
    }

    public function test_multi_tab_rapid_requests_are_all_rejected_except_first(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'MultiTabHero',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $map = Map::create([
            'name' => 'Test Map',
            'level_min' => 1,
            'level_max' => 5,
        ]);

        $monster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Orc',
            'level' => 1,
            'stats' => ['hp' => 20, 'atk' => 5, 'def' => 1, 'agi' => 1],
        ]);

        $service = app(EncounterService::class);

        $results = [];
        // Simulate 10 rapid multi-tab requests
        for ($i = 0; $i < 10; $i++) {
            $results[] = $service->start($character, $map);
        }

        // Exactly 1 request should be OK
        $okCount = count(array_filter($results, fn($r) => $r->isOk()));
        $errorCount = count(array_filter($results, fn($r) => $r->isError() && $r->getErrorCode() === 'COMBAT_IN_PROGRESS'));

        $this->assertEquals(1, $okCount);
        $this->assertEquals(9, $errorCount);
    }
}
