<?php

namespace Tests\Feature;

use App\Application\Combat\EncounterService;
use App\Application\Rankings\WeeklyRankingService;
use App\Domain\Combat\Enums\MonsterRank;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\Map;
use App\Infrastructure\Persistence\Monster;
use App\Models\User;
use App\Infrastructure\Persistence\WeeklyRankingEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class WeeklyRankingCombatTest extends TestCase
{
    use RefreshDatabase;

    public function test_increment_score_creates_and_increments_weekly_ranking_entry(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Hero',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $service = app(WeeklyRankingService::class);
        $service->incrementScore($character->id, 'monsters_killed', 1);

        $weekStart = WeeklyRankingService::currentWeekStart();
        $entry = WeeklyRankingEntry::where('character_id', $character->id)
            ->where('week_start', $weekStart)
            ->where('category', 'monsters_killed')
            ->first();

        $this->assertNotNull($entry, 'WeeklyRankingEntry should exist');
        $this->assertEquals(1, $entry->score);
        $this->assertEquals(26, strlen($entry->id)); // ULID length check

        $service->incrementScore($character->id, 'monsters_killed', 2);
        $entry->refresh();
        $this->assertEquals(3, $entry->score);
    }

    public function test_encounter_simulation_with_weekly_ranking_inside_db_transaction(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'Fighter',
            'level' => 1,
            'gold' => 100,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);

        $map = Map::create([
            'name' => 'Forest',
            'level_min' => 1,
            'level_max' => 10,
        ]);

        $monster = Monster::create([
            'map_id' => $map->id,
            'name' => 'Goblin',
            'level' => 1,
            'rank' => MonsterRank::REGULAR,
            'stats' => ['hp' => 10, 'atk' => 1, 'def' => 1, 'agi' => 1, 'int' => 1],
        ]);

        $encounterService = app(EncounterService::class);

        $startResult = $encounterService->start($character, $map, $monster);
        $this->assertFalse($startResult->isError(), $startResult->getErrorMessage() ?? '');

        $encounter = $startResult->getPayload();

        $simResult = $encounterService->simulate($encounter);
        $this->assertFalse($simResult->isError(), $simResult->getErrorMessage() ?? '');

        $weekStart = WeeklyRankingService::currentWeekStart();
        $entry = WeeklyRankingEntry::where('character_id', $character->id)
            ->where('week_start', $weekStart)
            ->first();

        $this->assertNotNull($entry);
        $this->assertGreaterThan(0, $entry->score);
    }
}
