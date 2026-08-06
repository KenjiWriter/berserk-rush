<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Characters\LevelUpService;
use App\Application\Mastery\ChampionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class ChampionLevelUpTest extends TestCase
{
    use RefreshDatabase;

    private function makeMaxLevelCharacter(int $championLevel = 0): Character
    {
        $user = \App\Models\User::factory()->create();

        return Character::create([
            'user_id' => $user->id,
            'name' => 'ChampionHero',
            'level' => 99,
            'xp' => 0,
            'gold' => 500_000_000,
            'champion_level' => $championLevel,
            'attributes' => ['str' => 10, 'int' => 5, 'vit' => 10, 'agi' => 5],
        ]);
    }

    /** Zaseeduje minimalną pulę materiałów (5 tierów) potrzebną do losowania ulepszaczy. */
    private function seedMaterialPool(): void
    {
        for ($tier = 1; $tier <= 5; $tier++) {
            ItemTemplate::create([
                'id' => (string) Str::ulid(),
                'name' => "Materiał Tier {$tier}",
                'type' => 'material',
                'level_requirement' => 1,
                'source_map_tier' => $tier,
            ]);
        }
    }

    public function test_xp_keeps_accumulating_past_old_level_99_cap_up_to_champion_target(): void
    {
        $character = $this->makeMaxLevelCharacter();
        $service = new LevelUpService();
        $championService = app(ChampionService::class);

        // Nadwyżka znacznie ponad stary cap 99 poziomu (xpToNext(99) ~138M), ale
        // wciąż poniżej progu championa (~2.6mld) - ten sam licznik `xp` powinien
        // ją zachować zamiast przycinać do starego capu.
        $overflow = 500_000_000;
        $character->increment('xp', $overflow);

        $result = $service->checkAndApply($character);
        $this->assertTrue($result->isOk());
        $character->refresh();

        $this->assertEquals(99, $character->level);
        $this->assertEquals($overflow, $character->xp);
        $this->assertGreaterThan($service->xpToNext(99), $character->xp);

        // Przekroczenie progu championa przycina xp dokładnie do xpTarget() (nie -1,
        // inaczej próg nigdy nie zostałby faktycznie osiągnięty), nie do starego capu.
        $character->increment('xp', $championService->xpTarget());
        $service->checkAndApply($character);
        $character->refresh();

        $this->assertEquals($championService->xpTarget(), $character->xp);
    }

    public function test_xp_still_capped_once_champion_level_maxed(): void
    {
        $character = $this->makeMaxLevelCharacter(ChampionService::LEVEL_CAP);
        $service = new LevelUpService();
        $championService = app(ChampionService::class);

        $character->increment('xp', $championService->xpTarget() + 999_999_999);
        $service->checkAndApply($character);
        $character->refresh();

        $this->assertEquals($championService->xpTarget(), $character->xp);
    }

    public function test_attempt_level_up_requires_both_full_xp_and_materials(): void
    {
        $this->seedMaterialPool();
        $championService = app(ChampionService::class);
        $character = $this->makeMaxLevelCharacter();

        // Brak expa i materiałów.
        $result = $championService->attemptLevelUp($character);
        $this->assertTrue($result->isError());
        $this->assertEquals('XP_NOT_FULL', $result->getErrorCode());

        // Pełny pasek expa, ale bez dostarczonych materiałów.
        $character->update(['xp' => $championService->xpTarget()]);
        $championService->rollMaterialRequirements($character);

        $result2 = $championService->attemptLevelUp($character->fresh());
        $this->assertTrue($result2->isError());
        $this->assertEquals('MATERIALS_MISSING', $result2->getErrorCode());
    }

    public function test_attempt_level_up_succeeds_when_both_conditions_met_and_rolls_new_materials(): void
    {
        $this->seedMaterialPool();
        $championService = app(ChampionService::class);
        $character = $this->makeMaxLevelCharacter();

        $championService->rollMaterialRequirements($character);
        $character->refresh();

        // Symulacja dostarczenia wszystkich wymaganych materiałów wprost do inventory.
        foreach ($character->champion_material_progress as $row) {
            ItemInstance::create([
                'id' => (string) Str::ulid(),
                'template_id' => $row['template_id'],
                'owner_character_id' => $character->id,
                'location' => 'inventory',
                'stack_size' => $row['required'],
            ]);

            $donateResult = $championService->donateMaterial($character, $row['template_id'], $row['required']);
            $this->assertTrue($donateResult->isOk(), (string) $donateResult->getErrorMessage());
        }

        $character->update(['xp' => $championService->xpTarget()]);
        $character->refresh();

        $result = $championService->attemptLevelUp($character);
        $this->assertTrue($result->isOk(), (string) $result->getErrorMessage());
        $character->refresh();

        $this->assertEquals(1, $character->champion_level);
        $this->assertEquals(1, $character->champion_points);
        $this->assertEquals(0, $character->xp);
        // Nowy zestaw wymagań wylosowany automatycznie na kolejny poziom.
        $this->assertNotEmpty($character->champion_material_progress);
        $this->assertEquals(1000, collect($character->champion_material_progress)->sum('required'));
    }

    public function test_champion_level_cannot_exceed_cap(): void
    {
        $championService = app(ChampionService::class);
        $character = $this->makeMaxLevelCharacter(ChampionService::LEVEL_CAP);

        $result = $championService->attemptLevelUp($character);
        $this->assertTrue($result->isError());
        $this->assertEquals('MAX_CHAMPION_LEVEL', $result->getErrorCode());
    }

    public function test_roll_material_requirements_sums_to_1000(): void
    {
        $this->seedMaterialPool();
        $championService = app(ChampionService::class);
        $character = $this->makeMaxLevelCharacter();

        $requirements = $championService->rollMaterialRequirements($character);

        $this->assertGreaterThanOrEqual(1, count($requirements));
        $this->assertEquals(1000, collect($requirements)->sum('required'));
        foreach ($requirements as $row) {
            $this->assertGreaterThan(0, $row['required']);
        }
    }
}
