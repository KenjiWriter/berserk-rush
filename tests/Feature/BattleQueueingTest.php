<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Application\PvP\PvPEncounterService;
use App\Application\Combat\EncounterService;
use App\Livewire\Adventure\MapStub;
use Livewire\Livewire;
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
            'level' => 20,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $defender1 = Character::create([
            'user_id' => $user2->id,
            'name' => 'Defender1',
            'level' => 20,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $defender2 = Character::create([
            'user_id' => $user3->id,
            'name' => 'Defender2',
            'level' => 20,
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

        // Second encounter attempt while first is state == 'ongoing' should fail with COMBAT_IN_PROGRESS
        $res2 = $service->start($character, $map);
        $this->assertTrue($res2->isError());
        $this->assertEquals('COMBAT_IN_PROGRESS', $res2->getErrorCode());

        $encounter = $res1->getPayload();
        $service->simulate($encounter);

        // Rewards are not applied yet (rewards_applied == false)
        $this->assertFalse($encounter->fresh()->rewards_applied);

        // Starting another encounter after simulation automatically auto-claims rewards and succeeds!
        \Illuminate\Support\Facades\Cache::forget("anticheat:last_encounter_start:{$character->id}");
        $res3 = $service->start($character, $map);
        $this->assertTrue($res3->isOk());
        $this->assertTrue($encounter->fresh()->rewards_applied);
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

    public function test_character_cannot_access_high_level_map_via_url_or_service(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Level1Hero',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $highLevelMap = Map::firstOrCreate(
            ['name' => 'Bagna Grozy'],
            ['level_min' => 50, 'level_max' => 70]
        );

        // Service should reject starting battle on high level map
        $service = app(EncounterService::class);
        $startResult = $service->start($character, $highLevelMap);
        $this->assertTrue($startResult->isError());
        $this->assertEquals('LEVEL_OUT_OF_RANGE', $startResult->getErrorCode());

        // Livewire MapStub mount should redirect back to adventure
        Livewire::actingAs($user)
            ->test(MapStub::class, ['character' => $character, 'map' => $highLevelMap])
            ->assertRedirect(route('city.adventure', $character));
    }

    public function test_character_above_max_level_cannot_access_map_or_world_boss(): void
    {
        $user = User::factory()->create();
        $highLevelCharacter = Character::create([
            'user_id' => $user->id,
            'name' => 'HighLevelHero',
            'level' => 40,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $lowLevelMap = Map::create([
            'name' => 'Test Low Level Map',
            'level_min' => 0,
            'level_max' => 15,
        ]);

        $bossMonster = Monster::create([
            'map_id' => $lowLevelMap->id,
            'name' => 'Król Lasu',
            'level' => 10,
            'rank' => 'worldboss',
            'stats' => ['hp' => 1000, 'atk' => 10, 'def' => 5, 'agi' => 2],
        ]);

        \App\Infrastructure\Persistence\WorldBossInstance::create([
            'monster_id' => $bossMonster->id,
            'map_id' => $lowLevelMap->id,
            'level_bracket' => 'low',
            'total_hp' => 1000,
            'current_hp' => 1000,
        ]);

        // Level 40 character is above map max level
        $this->assertTrue($lowLevelMap->isOverLevel($highLevelCharacter));

        // EncounterService start must be rejected for World Boss outside bracket
        $service = app(EncounterService::class);
        $startResult = $service->start($highLevelCharacter, $lowLevelMap, $bossMonster);
        $this->assertTrue($startResult->isError());
        $this->assertEquals('WRONG_LEVEL_BRACKET', $startResult->getErrorCode());
    }

    public function test_world_boss_can_be_challenged_via_map_stub_without_double_encounter_error(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'BossHunter',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $map = Map::create([
            'name' => 'Boss Map',
            'level_min' => 1,
            'level_max' => 5,
        ]);

        $bossMonster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Smoczy Król',
            'level' => 5,
            'rank' => 'worldboss',
            'stats' => ['hp' => 1000, 'atk' => 10, 'def' => 5, 'agi' => 2],
        ]);

        \App\Infrastructure\Persistence\WorldBossInstance::create([
            'monster_id' => $bossMonster->id,
            'map_id' => $map->id,
            'level_bracket' => 'low',
            'total_hp' => 1000,
            'current_hp' => 1000,
        ]);

        // When user opens map with ?world_boss={id}, mount() should NOT trigger startBattle() immediately.
        // Then startBattle() call on click should succeed without COMBAT_IN_PROGRESS error!
        Livewire::withQueryParams(['world_boss' => $bossMonster->id])
            ->actingAs($user)
            ->test(MapStub::class, ['character' => $character, 'map' => $map])
            ->assertSet('isWorldBoss', true)
            ->assertSet('worldBossMonsterId', $bossMonster->id)
            ->call('startBattle')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('world_boss_damage_logs', [
            'character_id' => $character->id,
        ]);
    }

    public function test_map_stub_tab_session_lock_deactivates_older_tabs(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'TabHero',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $map = Map::create([
            'name' => 'Tab Map',
            'level_min' => 1,
            'level_max' => 5,
        ]);

        Monster::create([
            'map_id' => $map->id,
            'name' => 'Slime',
            'level' => 1,
            'stats' => ['hp' => 20, 'atk' => 5, 'def' => 1, 'agi' => 1],
        ]);

        // Tab 1 mounts
        $tab1 = Livewire::actingAs($user)->test(MapStub::class, ['character' => $character, 'map' => $map]);
        $this->assertTrue($tab1->get('isInactiveTab') === false);

        // Tab 2 mounts (simulating opening second browser tab for same character)
        $tab2 = Livewire::actingAs($user)->test(MapStub::class, ['character' => $character, 'map' => $map]);
        $this->assertTrue($tab2->get('isInactiveTab') === false);

        // Tab 1 now tries to start battle, but should be rejected because Tab 2 took active status
        $tab1->call('startBattle')
            ->assertSet('isInactiveTab', true)
            ->assertSet('autoChain', false)
            ->assertHasErrors(['battle']);

        // Tab 1 claims active status back
        $tab1->call('claimActiveTab')
            ->assertSet('isInactiveTab', false);

        // Now Tab 2 is inactive when starting battle
        $tab2->call('startBattle')
            ->assertSet('isInactiveTab', true);
    }
}
