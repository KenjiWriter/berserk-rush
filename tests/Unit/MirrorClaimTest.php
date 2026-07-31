<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\CharacterMirrorSession;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Mirror\MirrorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MirrorClaimTest extends TestCase
{
    use RefreshDatabase;

    public function test_stop_and_claim_grants_rewards_and_detects_level_up(): void
    {
        $user = User::factory()->create();

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'MirrorClaimer',
            'level' => 1,
            'xp' => 0,
            'gold' => 100,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5],
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        $map = Map::create([
            'name' => 'Dolina Testowa',
            'level_min' => 1,
            'level_max' => 10,
        ]);

        $itemTemplate = ItemTemplate::create([
            'id' => \Illuminate\Support\Str::ulid(),
            'name' => 'Ruda Żelaza Test',
            'type' => 'material',
            'sub_type' => 'crafting',
            'rarity' => 'common',
            'base_price' => 10,
        ]);

        $session = CharacterMirrorSession::create([
            'character_id' => $character->id,
            'map_id' => $map->id,
            'duration_hours' => 1,
            'exp_per_minute' => 500, // 500 EXP/min for 10 min = 5000 EXP => Level Up from Level 1
            'gold_per_minute' => 50,
            'started_at' => now()->subMinutes(10),
            'ends_at' => now()->addMinutes(50),
            'status' => 'active',
        ]);

        $service = app(MirrorService::class);
        $summary = $service->stopAndClaim($character);

        $this->assertEquals('claimed', $session->fresh()->status);
        $this->assertGreaterThan(0, $summary['xp']);
        $this->assertGreaterThan(0, $summary['gold']);
        $this->assertTrue($summary['had_level_up']);
        $this->assertGreaterThan(1, $summary['new_level']);
    }
}
