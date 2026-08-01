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

        // level 10, tier 1 (norma 100%): pula = 10 * 2.35 = 23.5, rozdzielona równo (25% każda) -> round(5.875) = 6.
        $this->assertSame(6, $pet->stats['str']);

        $pet->level = 10;
        $pet->tier = 6; // norma 250%
        $pet->recalculateStats();
        $pet->save();

        // pula = 23.5 * 2.5 = 58.75, /4 = 14.6875 -> round = 15.
        $this->assertSame(15, $pet->stats['str']);
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

    public function test_fusion_service_consumes_pets_even_on_failure(): void
    {
        $this->app->instance(RandomProvider::class, new DeterministicRandomProvider([999]));

        $character = $this->createTestCharacter();
        // Tier 5->6 bazowa szansa to tylko 20%, roll wymuszony na ~99.9% -> porażka.
        $p1 = $this->makePet($character, 5);
        $p2 = $this->makePet($character, 5);

        $service = app(PetFusionService::class);
        $result = $service->fusePets($character, [$p1->id, $p2->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertFalse($payload['success']);

        $this->assertDatabaseMissing('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
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
}
