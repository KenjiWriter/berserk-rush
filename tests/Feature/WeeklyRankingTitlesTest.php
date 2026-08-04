<?php

namespace Tests\Feature;

use App\Application\Rankings\WeeklyRankingService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterTitle;
use App\Infrastructure\Persistence\Title;
use App\Models\User;
use App\Infrastructure\Persistence\WeeklyRankingEntry;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WeeklyRankingTitlesTest extends TestCase
{
    use RefreshDatabase;

    private function createTestCharacter(User $user, string $name): Character
    {
        return Character::create([
            'id'         => (string) Str::ulid(),
            'user_id'    => $user->id,
            'name'       => $name,
            'level'      => 10,
            'gold'       => 100,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
        ]);
    }

    public function test_compute_and_award_weekly_grants_7_day_temporary_titles_for_top_3()
    {
        $this->seed(\Database\Seeders\TitleSeeder::class);

        $user = User::factory()->create();
        $char1 = $this->createTestCharacter($user, 'Hero1');
        $char2 = $this->createTestCharacter($user, 'Hero2');
        $char3 = $this->createTestCharacter($user, 'Hero3');

        $lastWeek = Carbon::now()->subWeek()->startOfWeek(Carbon::MONDAY)->toDateString();

        WeeklyRankingEntry::create(['character_id' => $char1->id, 'week_start' => $lastWeek, 'category' => 'arena_wins', 'score' => 100]);
        WeeklyRankingEntry::create(['character_id' => $char2->id, 'week_start' => $lastWeek, 'category' => 'arena_wins', 'score' => 50]);
        WeeklyRankingEntry::create(['character_id' => $char3->id, 'week_start' => $lastWeek, 'category' => 'arena_wins', 'score' => 25]);

        $service = app(WeeklyRankingService::class);
        $service->computeAndAwardWeekly();

        $top1Title = Title::where('name', 'Top 1 Gladiator')->firstOrFail();
        $top2Title = Title::where('name', 'Top 2 Gladiator')->firstOrFail();
        $top3Title = Title::where('name', 'Top 3 Gladiator')->firstOrFail();

        $this->assertDatabaseHas('character_titles', [
            'character_id' => $char1->id,
            'title_id'     => $top1Title->id,
        ]);
        $this->assertDatabaseHas('character_titles', [
            'character_id' => $char2->id,
            'title_id'     => $top2Title->id,
        ]);
        $this->assertDatabaseHas('character_titles', [
            'character_id' => $char3->id,
            'title_id'     => $top3Title->id,
        ]);

        $char1Title = CharacterTitle::where('character_id', $char1->id)->where('title_id', $top1Title->id)->first();
        $this->assertNotNull($char1Title->expires_at);
        $this->assertTrue($char1Title->expires_at->isFuture());
    }

    public function test_expired_titles_are_cleaned_up_and_active_title_reset()
    {
        $this->seed(\Database\Seeders\TitleSeeder::class);

        $user = User::factory()->create();
        $char = $this->createTestCharacter($user, 'Hero');
        $title = Title::where('name', 'Top 1 Gladiator')->firstOrFail();

        // Unlock expired title
        $charTitle = CharacterTitle::create([
            'character_id' => $char->id,
            'title_id'     => $title->id,
            'unlocked_at'  => Carbon::now()->subDays(10),
            'expires_at'   => Carbon::now()->subDays(3),
        ]);

        $char->active_title_id = $title->id;
        $char->save();

        $service = app(WeeklyRankingService::class);
        $service->computeAndAwardWeekly();

        $this->assertDatabaseMissing('character_titles', ['id' => $charTitle->id]);

        $char->refresh();
        $this->assertNull($char->active_title_id);
    }

    public function test_active_temporary_title_provides_strong_vs_hero_stat()
    {
        $this->seed(\Database\Seeders\TitleSeeder::class);

        $user = User::factory()->create();
        $char = $this->createTestCharacter($user, 'Hero');
        $title = Title::where('name', 'Top 1 Gladiator')->firstOrFail();

        CharacterTitle::create([
            'character_id' => $char->id,
            'title_id'     => $title->id,
            'unlocked_at'  => Carbon::now(),
            'expires_at'   => Carbon::now()->addDays(7),
        ]);

        $char->active_title_id = $title->id;
        $char->save();

        $eqStats = $char->getEquipmentStats();
        $this->assertEquals(5, $eqStats['strong_vs_hero'] ?? 0);
        $this->assertEquals(2, $eqStats['dodge_chance'] ?? 0);
    }
}
