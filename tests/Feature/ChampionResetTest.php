<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ChampionSkill;
use App\Infrastructure\Persistence\CharacterChampionSkill;
use App\Application\Mastery\ChampionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ChampionResetTest extends TestCase
{
    use RefreshDatabase;

    private function makeCharacter(int $championLevel, int $gold): Character
    {
        $user = \App\Models\User::factory()->create();

        return Character::create([
            'user_id' => $user->id,
            'name' => 'ChampionResetter',
            'level' => 99,
            'champion_level' => $championLevel,
            'champion_points' => 0,
            'gold' => $gold,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);
    }

    public function test_reset_fails_without_enough_gold(): void
    {
        $character = $this->makeCharacter(5, 100);
        $service = app(ChampionService::class);

        $result = $service->resetSkills($character);

        $this->assertTrue($result->isError());
        $this->assertEquals('NOT_ENOUGH_GOLD', $result->getErrorCode());
    }

    public function test_reset_succeeds_and_refunds_all_invested_points(): void
    {
        $character = $this->makeCharacter(5, ChampionService::RESET_GOLD_COST);

        $skill = ChampionSkill::create([
            'id' => (string) Str::ulid(),
            'key' => 'test_strength',
            'name' => 'Siła Testowa',
            'stat_type' => 'phys_dmg_pct',
            'bonus_per_point' => 1,
            'max_points' => 10,
        ]);

        CharacterChampionSkill::create([
            'character_id' => $character->id,
            'champion_skill_id' => $skill->id,
            'points' => 5,
        ]);

        $service = app(ChampionService::class);
        $result = $service->resetSkills($character);

        $this->assertTrue($result->isOk());
        $character->refresh();

        $this->assertEquals(0, $character->gold);
        $this->assertEquals(5, $character->champion_points); // pełny zwrot = champion_level
        $this->assertEquals(0, CharacterChampionSkill::where('character_id', $character->id)->count());
        $this->assertNotNull($character->last_champion_reset_at);
    }

    public function test_reset_blocked_within_one_month_of_previous_reset(): void
    {
        $character = $this->makeCharacter(5, ChampionService::RESET_GOLD_COST * 2);
        $character->update(['last_champion_reset_at' => Carbon::now()->subDays(10)]);

        $service = app(ChampionService::class);
        $result = $service->resetSkills($character->fresh());

        $this->assertTrue($result->isError());
        $this->assertEquals('RESET_ON_COOLDOWN', $result->getErrorCode());
        // Złoto nie powinno zostać zabrane przy zablokowanym reset.
        $this->assertEquals(ChampionService::RESET_GOLD_COST * 2, $character->fresh()->gold);
    }

    public function test_reset_allowed_after_one_month(): void
    {
        $character = $this->makeCharacter(5, ChampionService::RESET_GOLD_COST);
        $character->update(['last_champion_reset_at' => Carbon::now()->subMonths(2)]);

        $service = app(ChampionService::class);
        $result = $service->resetSkills($character->fresh());

        $this->assertTrue($result->isOk());
    }
}
