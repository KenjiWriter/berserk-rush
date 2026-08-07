<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Pets\PetFeedingService;
use App\Application\Pets\PetFusionService;
use App\Infrastructure\RNG\DeterministicRandomProvider;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createTestCharacter(int $level = 10): Character
    {
        $user = User::factory()->create();
        return Character::create([
            'user_id' => $user->id,
            'name' => 'PetTester',
            'level' => $level,
            'xp' => 0,
            'gold' => 100,
        ]);
    }

    private function makePet(Character $character, int $tier, array $overrides = []): Pet
    {
        $pet = new Pet(array_merge([
            'character_id' => $character->id,
            'name' => 'Test Pet',
            'tier' => $tier,
            'level' => 1,
            'exp' => 0,
            'growth_stage' => 0,
            'fusion_count' => 0,
            'is_equipped' => false,
            'stat_profile' => ['str' => 0.25, 'agi' => 0.25, 'int' => 0.25, 'vit' => 0.25],
        ], $overrides));
        $pet->recalculateStats();
        $pet->save();

        return $pet;
    }

    public function test_pet_recalculate_stats_scales_with_level_and_tier(): void
    {
        $character = $this->createTestCharacter();
        $pet = $this->makePet($character, 1, ['level' => 10]);

        // level 10, tier 1 (norma 100%), growth_stage 1 (mnożnik 1.10):
        // pula = 10 * 1.175 * 1.10 = 12.925, rozdzielona równo (25% każda) -> round(3.23125) = 3.
        $this->assertSame(3, $pet->stats['str']);

        $pet->level = 10;
        $pet->tier = 6; // norma 250%
        $pet->recalculateStats();
        $pet->save();

        // pula = 12.925 * 2.5 = 32.3125, /4 = 8.078125 -> round = 8.
        $this->assertSame(8, $pet->stats['str']);
    }

    public function test_pet_effective_stats_are_dampened_when_character_level_below_pet_level(): void
    {
        $character = $this->createTestCharacter(level: 10);
        $pet = $this->makePet($character, 1, ['level' => 100]);

        $effective = $pet->getEffectiveStatsFor($character);
        $undampened = $pet->stats;

        // 10 / 100 = 10% mocy.
        foreach ($effective as $stat => $value) {
            $this->assertSame((int) round($undampened[$stat] * 0.10), $value);
        }
    }

    public function test_fusion_service_fuses_two_pets_of_same_tier_on_success(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([1]));

        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 1);

        // Domyślny DeterministicRandomProvider testowy ([1,2,3,4,5]) zawsze daje
        // niski roll (~0.1%), więc fuzja z dodatnią szansą bazową zawsze się uda.
        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertTrue($payload['success']);
        $this->assertSame(2, $payload['pet']->tier);
        $this->assertSame(1, $payload['pet']->fusion_count);

        $this->assertDatabaseMissing('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
    }

    /**
     * DeterministicRandomProvider consumes values in call order: [0] the
     * chance roll (float, forced to 99.9% so it always exceeds tier1's 80%
     * base chance), [1] the failure-outcome roll (int 1-100, chosen to land
     * in the desired bucket of pets.fusion_failure_outcomes), [2] the
     * lucky-pet coin flip (0 -> petA survives/is spared where relevant).
     */
    public function test_fusion_service_failure_no_loss_outcome_keeps_both_pets(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 999, 0]));

        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 1);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);
        $this->assertSame('no_loss', $payload['outcome']);

        $this->assertDatabaseHas('pets', ['id' => $p1->id]);
        $this->assertDatabaseHas('pets', ['id' => $p2->id]);
        $this->assertSame(0, $character->fresh()->gold);
    }

    public function test_fusion_service_failure_lose_both_outcome_deletes_both_pets(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 100, 0]));

        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 1);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);
        $this->assertSame('lose_both', $payload['outcome']);

        $this->assertDatabaseMissing('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
    }

    public function test_fusion_service_failure_lose_one_outcome_deletes_unlucky_pet_only(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 5, 0]));

        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 1);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);
        $this->assertSame('lose_one', $payload['outcome']);

        $this->assertDatabaseHas('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
    }

    public function test_fusion_service_failure_devolve_both_outcome_downgrades_both_pets_to_lower_tier(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 20, 0]));

        $character = $this->createTestCharacter();
        $character->update(['gold' => 1000]); // T2 fuzja kosztuje 250, domyślne testowe 100 to za mało.
        $p1 = $this->makePet($character, 2, ['archetype' => 'attacker']);
        $p2 = $this->makePet($character, 2, ['archetype' => 'defense']);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);
        $this->assertSame('devolve_both', $payload['outcome']);

        // Oba pety tier2 są USUWANE i zastępowane świeżymi petami tier1
        // (level 1, fusion_count 0) - "cofka" degraduje tier, nie tylko level.
        $this->assertDatabaseMissing('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
        $this->assertDatabaseCount('pets', 2);

        $this->assertCount(2, $payload['downgradedPets']);
        foreach ($payload['downgradedPets'] as $newPet) {
            $this->assertSame(1, $newPet->tier);
            $this->assertSame(1, $newPet->level);
            $this->assertSame(0, $newPet->fusion_count);
            $this->assertSame(0, $newPet->growth_stage);
        }

        $archetypes = collect($payload['downgradedPets'])->pluck('archetype')->sort()->values()->all();
        $this->assertSame(['attacker', 'defense'], $archetypes);
    }

    public function test_fusion_service_failure_devolve_one_outcome_downgrades_unlucky_pet_only(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 40, 0]));

        $character = $this->createTestCharacter();
        $character->update(['gold' => 1000]); // T2 fuzja kosztuje 250, domyślne testowe 100 to za mało.
        $p1 = $this->makePet($character, 2);
        $p2 = $this->makePet($character, 2);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);
        $this->assertSame('devolve_one', $payload['outcome']);

        // luckyPet (coin flip 0 -> petA) przetrwa nietknięty na tier2, drugi
        // pet zostaje usunięty i zastąpiony świeżym petem tier1.
        $this->assertDatabaseHas('pets', ['id' => $p1->id, 'tier' => 2]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
        $this->assertDatabaseCount('pets', 2);

        $this->assertCount(1, $payload['downgradedPets']);
        $this->assertSame(1, $payload['downgradedPets'][0]->tier);
        $this->assertSame(1, $payload['downgradedPets'][0]->level);
        $this->assertSame(0, $payload['downgradedPets'][0]->fusion_count);
    }

    public function test_fusion_service_failure_devolve_on_tier_one_pet_deletes_it_instead(): void
    {
        // Edge case: pet T1 nie ma niżej gdzie spaść - "devolve" na tierze 1
        // jest równoznaczne z utratą peta (jak lose_one/lose_both).
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999, 40, 0]));

        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 1);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertSame('devolve_one', $payload['outcome']);

        $this->assertDatabaseHas('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
        $this->assertDatabaseCount('pets', 1);
        $this->assertCount(0, $payload['downgradedPets']);
    }

    public function test_fusion_service_rejects_mismatched_tiers(): void
    {
        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1);
        $p2 = $this->makePet($character, 2);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertTrue($result->isError());
        $this->assertSame('TIER_MISMATCH', $result->getErrorCode());
        $this->assertDatabaseHas('pets', ['id' => $p1->id]);
        $this->assertDatabaseHas('pets', ['id' => $p2->id]);
    }

    public function test_fusion_service_rejects_equipped_pet(): void
    {
        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 1, ['is_equipped' => true]);
        $p2 = $this->makePet($character, 1);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertTrue($result->isError());
        $this->assertDatabaseHas('pets', ['id' => $p1->id]);
        $this->assertDatabaseHas('pets', ['id' => $p2->id]);
    }

    public function test_fusion_service_rejects_max_tier(): void
    {
        $character = $this->createTestCharacter();
        $p1 = $this->makePet($character, 6);
        $p2 = $this->makePet($character, 6);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertTrue($result->isError());
    }

    public function test_feeding_pet_with_item_in_level_bracket_grants_exp(): void
    {
        $character = $this->createTestCharacter();
        $pet = $this->makePet($character, 1); // T1 przyjmuje itemy poz. 0-20

        $template = ItemTemplate::create([
            'id' => 'sword-lvl-15',
            'name' => 'Miecz Poziomu 15',
            'type' => 'weapon',
            'level_requirement' => 15,
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'common',
        ]);

        $service = app(PetFeedingService::class);
        $result = $service->feedPet($character, $pet->id, [$item->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertEquals(15, $payload['gainedExp']);
        $this->assertDatabaseMissing('item_instances', ['id' => $item->id]);
    }

    public function test_feeding_pet_rejects_item_below_tier_minimum_level(): void
    {
        $character = $this->createTestCharacter();
        $pet = $this->makePet($character, 4); // T4 przyjmuje wyłącznie itemy od poz. 45+

        $template = ItemTemplate::create([
            'id' => 'sword-lvl-10',
            'name' => 'Miecz Poziomu 10',
            'type' => 'weapon',
            'level_requirement' => 10,
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'common',
        ]);

        $service = app(PetFeedingService::class);
        $result = $service->feedPet($character, $pet->id, [$item->id]);

        $this->assertTrue($result->isError());
        $this->assertDatabaseHas('item_instances', ['id' => $item->id, 'location' => 'inventory']);
    }

    public function test_feeding_pet_accepts_item_above_tier_minimum_with_no_upper_cap(): void
    {
        $character = $this->createTestCharacter();
        $pet = $this->makePet($character, 1); // T1 przyjmuje od poz. 0 - bez górnej granicy

        $template = ItemTemplate::create([
            'id' => 'sword-lvl-99',
            'name' => 'Legendarny Miecz Poziomu 99',
            'type' => 'weapon',
            'level_requirement' => 99,
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'legendary',
        ]);

        $service = app(PetFeedingService::class);
        $result = $service->feedPet($character, $pet->id, [$item->id]);

        // T1 może zjeść nawet legendarny item poz. 99 - brak górnej granicy.
        $this->assertFalse($result->isError());
        $this->assertDatabaseMissing('item_instances', ['id' => $item->id]);
    }

    public function test_pet_archetype_bonus_percent_scales_with_fusion_count_and_tier(): void
    {
        $character = $this->createTestCharacter(level: 50);
        $pet = $this->makePet($character, 3, ['level' => 50, 'fusion_count' => 2, 'archetype' => 'attacker']);

        // bonus% = fusion_count(2) * 1% * tier(3) = 6%, bez tłumienia (poziom postaci == poziom peta).
        $this->assertEqualsWithDelta(6.0, $pet->getArchetypeBonusPercentFor($character), 0.001);
    }

    public function test_pet_archetype_bonus_percent_is_zero_without_fusion_or_archetype(): void
    {
        $character = $this->createTestCharacter(level: 50);

        $unfused = $this->makePet($character, 3, ['level' => 50, 'fusion_count' => 0, 'archetype' => 'attacker']);
        $this->assertSame(0.0, $unfused->getArchetypeBonusPercentFor($character));

        $noArchetype = $this->makePet($character, 3, ['level' => 50, 'fusion_count' => 2, 'archetype' => null]);
        $this->assertSame(0.0, $noArchetype->getArchetypeBonusPercentFor($character));
    }

    public function test_pet_archetype_bonus_percent_is_dampened_by_character_level(): void
    {
        $character = $this->createTestCharacter(level: 25);
        $pet = $this->makePet($character, 3, ['level' => 50, 'fusion_count' => 2, 'archetype' => 'attacker']);

        // Undampened 6%, tłumienie mocy = 25/50 = 0.5 -> 3%.
        $this->assertEqualsWithDelta(3.0, $pet->getArchetypeBonusPercentFor($character), 0.001);
    }

    public function test_character_equipment_stats_apply_support_pet_passive(): void
    {
        $character = $this->createTestCharacter(level: 50);
        $this->makePet($character, 3, [
            'level' => 50, 'fusion_count' => 2, 'archetype' => 'support', 'is_equipped' => true,
        ]);

        $stats = $character->fresh()->getEquipmentStats();

        // bonus% = 2 * 1% * 3 = 6%, dodawane wprost do dodge_chance i mana_cost_reduction_pct.
        $this->assertEqualsWithDelta(6.0, $stats['dodge_chance'], 0.001);
        $this->assertEqualsWithDelta(6.0, $stats['mana_cost_reduction_pct'], 0.001);
    }

    public function test_character_equipment_stats_ignore_unequipped_or_unfused_pet(): void
    {
        $character = $this->createTestCharacter(level: 50);
        $this->makePet($character, 3, [
            'level' => 50, 'fusion_count' => 2, 'archetype' => 'support', 'is_equipped' => false,
        ]);

        $stats = $character->fresh()->getEquipmentStats();

        $this->assertSame(0, $stats['dodge_chance']);
        $this->assertSame(0, $stats['mana_cost_reduction_pct']);
    }

    public function test_combat_skill_mana_cost_is_reduced_by_support_pet_passive(): void
    {
        $character = $this->createTestCharacter(level: 50);
        $this->makePet($character, 3, [
            'level' => 50, 'fusion_count' => 2, 'archetype' => 'support', 'is_equipped' => true,
        ]);

        $skill = \App\Infrastructure\Persistence\CombatSkill::create([
            'name' => 'Testowa Umiejętność',
            'type' => 'active',
            'effect_type' => 'direct_dmg',
            'base_mana_cost' => 100,
            'scaling_mana_cost' => 0,
        ]);

        $characterSkill = \App\Infrastructure\Persistence\CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 1,
            'is_equipped' => true,
        ]);

        // Koszt bazowy 100, tłumiona pasywka -6% -> 94.
        $this->assertSame(94, $characterSkill->fresh()->getManaCost());
    }
}
