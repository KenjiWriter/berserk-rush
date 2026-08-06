<?php

namespace Tests\Unit;

use App\Domain\Pets\PetFusionRules;
use App\Domain\Pets\PetGrowthStage;
use App\Domain\Pets\PetLevelCurve;
use App\Domain\Pets\PetStatCalculator;
use App\Domain\Pets\PetTier;
use Tests\TestCase;

class PetDomainTest extends TestCase
{
    public function test_pet_tier_feed_level_min_and_acceptance(): void
    {
        // Brak górnej granicy - mocniejszy przedmiot zawsze można skarmić.
        $this->assertSame(0, PetTier::feedLevelMin(1));
        $this->assertSame(75, PetTier::feedLevelMin(6));

        $this->assertTrue(PetTier::isItemLevelAccepted(1, 10));
        $this->assertTrue(PetTier::isItemLevelAccepted(1, 99)); // T1 może zjeść legendarny item poz. 99
        $this->assertTrue(PetTier::isItemLevelAccepted(6, 999));
        $this->assertFalse(PetTier::isItemLevelAccepted(6, 74));
    }

    public function test_pet_tier_fusion_boundaries(): void
    {
        $this->assertTrue(PetTier::canFuse(1));
        $this->assertTrue(PetTier::canFuse(5));
        $this->assertFalse(PetTier::canFuse(6));
    }

    public function test_growth_stage_thresholds(): void
    {
        $this->assertSame(0, PetGrowthStage::forLevel(1));
        $this->assertSame(0, PetGrowthStage::forLevel(9));
        $this->assertSame(1, PetGrowthStage::forLevel(10));
        $this->assertSame(1, PetGrowthStage::forLevel(24));
        $this->assertSame(2, PetGrowthStage::forLevel(25));
        $this->assertSame(2, PetGrowthStage::forLevel(49));
        $this->assertSame(3, PetGrowthStage::forLevel(50));
        $this->assertSame(3, PetGrowthStage::forLevel(99));
    }

    public function test_growth_stage_labels_and_stat_multiplier(): void
    {
        $this->assertSame('Pisklak', PetGrowthStage::label(0));
        $this->assertSame('Podrostek', PetGrowthStage::label(1));
        $this->assertSame('Okrzepły', PetGrowthStage::label(2));
        $this->assertSame('Forma Dorosła', PetGrowthStage::label(3));

        $this->assertEqualsWithDelta(1.00, PetGrowthStage::statMultiplier(0), 0.001);
        $this->assertEqualsWithDelta(1.10, PetGrowthStage::statMultiplier(1), 0.001);
        $this->assertEqualsWithDelta(1.22, PetGrowthStage::statMultiplier(2), 0.001);
        $this->assertEqualsWithDelta(1.35, PetGrowthStage::statMultiplier(3), 0.001);
    }

    public function test_growth_stage_sprite_variant_mapping(): void
    {
        $this->assertSame('baby', PetGrowthStage::spriteVariant(0));
        $this->assertSame('medium', PetGrowthStage::spriteVariant(1));
        $this->assertSame('medium', PetGrowthStage::spriteVariant(2));
        $this->assertSame('adult', PetGrowthStage::spriteVariant(3));
    }

    public function test_fusion_success_chance_uses_base_plus_growth_bonus(): void
    {
        // Baza dla T1->T2 = 80%, zero ewolucji -> dokładnie baza.
        $this->assertEqualsWithDelta(80.0, PetFusionRules::successChance(1, 0, 0), 0.001);

        // Oba pety w pełni dorosłe (growth_stage 3+3=6) -> +20% (6 * 3.3333%).
        $this->assertEqualsWithDelta(100.0, PetFusionRules::successChance(1, 3, 3), 0.01);

        // T5->T6 baza 20%, oba dorosłe -> 40%.
        $this->assertEqualsWithDelta(40.0, PetFusionRules::successChance(5, 3, 3), 0.01);
    }

    public function test_fusion_result_tier_and_max_tier_guard(): void
    {
        $this->assertSame(2, PetFusionRules::resultTier(1));
        $this->assertSame(6, PetFusionRules::resultTier(5));
        $this->assertNull(PetFusionRules::resultTier(6));
    }

    public function test_stat_calculator_total_pool_scales_with_tier_norm_and_fusion_count(): void
    {
        // level 1 (growth_stage 0, mnożnik etapu 1.0), tier 1 (norma 100%),
        // fusion_count 0 -> baza czysta, bez wpływu etapu wzrostu.
        $base = PetStatCalculator::baseStatTotal(1); // 1 * 1.175 = 1.175
        $this->assertEqualsWithDelta(1.175, $base, 0.001);

        $poolT1 = PetStatCalculator::totalPool(1, 1, 0);
        $this->assertEqualsWithDelta(1.175, $poolT1, 0.001);

        // Tier 6 (norma 250%) powinien dawać 2.5x pulę tieru 1.
        $poolT6 = PetStatCalculator::totalPool(6, 1, 0);
        $this->assertEqualsWithDelta($poolT1 * 2.5, $poolT6, 0.001);

        // fusion_count=1 dodaje +10% do puli.
        $poolFused = PetStatCalculator::totalPool(1, 1, 1);
        $this->assertEqualsWithDelta($poolT1 * 1.10, $poolFused, 0.001);
    }

    public function test_stat_calculator_total_pool_applies_growth_stage_multiplier(): void
    {
        // level 10 -> growth_stage 1 ("Podrostek"), mnożnik 1.10 (patrz
        // config('pets.growth_stage_stat_multiplier')).
        $expected = PetStatCalculator::baseStatTotal(10) * 1.10; // 11.75 * 1.10
        $this->assertEqualsWithDelta($expected, PetStatCalculator::totalPool(1, 10, 0), 0.001);

        // level 50 -> growth_stage 3 ("Forma Dorosła"), mnożnik 1.35.
        $expected50 = PetStatCalculator::baseStatTotal(50) * 1.35;
        $this->assertEqualsWithDelta($expected50, PetStatCalculator::totalPool(1, 50, 0), 0.001);
    }

    public function test_stat_calculator_distributes_pool_by_profile_weights(): void
    {
        $stats = PetStatCalculator::distribute(100, ['str' => 0.5, 'agi' => 0.3, 'int' => 0.1, 'vit' => 0.1]);

        $this->assertSame(50, $stats['str']);
        $this->assertSame(30, $stats['agi']);
        $this->assertSame(10, $stats['int']);
        $this->assertSame(10, $stats['vit']);
    }

    public function test_stat_calculator_falls_back_to_even_split_without_profile(): void
    {
        $stats = PetStatCalculator::distribute(100, null);

        foreach (['str', 'agi', 'int', 'vit'] as $stat) {
            $this->assertSame(25, $stats[$stat]);
        }
    }

    public function test_level_curve_required_exp_scales_with_level_and_fusion_count(): void
    {
        $this->assertSame(100, PetLevelCurve::requiredExp(1, 0));
        $this->assertSame(1000, PetLevelCurve::requiredExp(10, 0));

        // fusion_count=1 -> +10% wymaganego EXP.
        $this->assertSame(110, PetLevelCurve::requiredExp(1, 1));
        $this->assertSame(1100, PetLevelCurve::requiredExp(10, 1));
    }
}
