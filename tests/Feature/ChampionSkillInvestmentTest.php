<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ChampionSkill;
use App\Infrastructure\Persistence\CharacterChampionSkill;
use App\Application\Mastery\ChampionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ChampionSkillInvestmentTest extends TestCase
{
    use RefreshDatabase;

    private function makeCharacter(int $championPoints): Character
    {
        $user = \App\Models\User::factory()->create();

        return Character::create([
            'user_id' => $user->id,
            'name' => 'ChampionInvestor',
            'level' => 99,
            'champion_points' => $championPoints,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);
    }

    private function makeSkill(int $maxPoints = 10): ChampionSkill
    {
        return ChampionSkill::create([
            'id' => (string) Str::ulid(),
            'key' => 'test_strength',
            'name' => 'Siła Testowa',
            'description' => 'Test',
            'stat_type' => 'phys_dmg_pct',
            'bonus_per_point' => 1,
            'max_points' => $maxPoints,
        ]);
    }

    public function test_invest_point_increases_skill_and_decrements_champion_points(): void
    {
        $character = $this->makeCharacter(3);
        $skill = $this->makeSkill();
        $service = app(ChampionService::class);

        $result = $service->investPoint($character, $skill->id);

        $this->assertTrue($result->isOk());
        $character->refresh();
        $this->assertEquals(2, $character->champion_points);

        $charSkill = CharacterChampionSkill::where('character_id', $character->id)
            ->where('champion_skill_id', $skill->id)
            ->first();
        $this->assertNotNull($charSkill);
        $this->assertEquals(1, $charSkill->points);
    }

    public function test_invest_point_fails_without_available_points(): void
    {
        $character = $this->makeCharacter(0);
        $skill = $this->makeSkill();
        $service = app(ChampionService::class);

        $result = $service->investPoint($character, $skill->id);

        $this->assertTrue($result->isError());
        $this->assertEquals('NO_POINTS', $result->getErrorCode());
    }

    public function test_invest_point_fails_once_skill_is_maxed(): void
    {
        $character = $this->makeCharacter(5);
        $skill = $this->makeSkill(1);
        $service = app(ChampionService::class);

        $first = $service->investPoint($character, $skill->id);
        $this->assertTrue($first->isOk());

        $second = $service->investPoint($character->fresh(), $skill->id);
        $this->assertTrue($second->isError());
        $this->assertEquals('MAX_POINTS_REACHED', $second->getErrorCode());

        // Punkt nie powinien zostać zużyty przy nieudanej próbie.
        $this->assertEquals(4, $character->fresh()->champion_points);
    }

    public function test_champion_bonus_percent_reflects_invested_points(): void
    {
        $character = $this->makeCharacter(2);
        $skill = $this->makeSkill();
        $service = app(ChampionService::class);

        $service->investPoint($character, $skill->id);
        $service->investPoint($character->fresh(), $skill->id);

        $character = $character->fresh();
        // 2 punkty x 1%/pkt = 2% = 0.02
        $this->assertEqualsWithDelta(0.02, $character->getChampionBonusPercent('phys_dmg_pct'), 0.0001);
    }

    public function test_champion_bonus_is_zero_below_level_99(): void
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'NotYetChampion',
            'level' => 50,
            'champion_points' => 5,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);
        $skill = $this->makeSkill();
        $service = app(ChampionService::class);

        $service->investPoint($character, $skill->id);

        $this->assertEquals(0.0, $character->fresh()->getChampionBonusPercent('phys_dmg_pct'));
    }
}
