<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\RNG\DefaultRandomProvider;
use Illuminate\Support\Str;

class EnchantmentStrategyGaussianTest extends TestCase
{
    public function test_bonus_values_are_within_range_for_all_item_types(): void
    {
        $rng = new DefaultRandomProvider();
        $strategy = new EnchantmentStrategy($rng);

        $weaponTemplate = new ItemTemplate(['id' => (string) Str::ulid(), 'name' => 'Miecz Testowy', 'type' => 'sword']);
        $weaponInstance = new ItemInstance(['id' => (string) Str::ulid(), 'template_id' => $weaponTemplate->id, 'roll_stats' => ['enchants' => []]]);
        $weaponInstance->setRelation('template', $weaponTemplate);

        $possibleBonuses = $strategy->getPossibleBonuses($weaponInstance);

        for ($i = 0; $i < 500; $i++) {
            $enchant = $strategy->generateRandomEnchantment($weaponInstance);
            $type = $enchant['type'];
            $value = $enchant['value'];

            $range = $possibleBonuses[$type];
            $this->assertGreaterThanOrEqual($range[0], $value, "Bonus {$type} value {$value} below min {$range[0]}");
            $this->assertLessThanOrEqual($range[1], $value, "Bonus {$type} value {$value} above max {$range[1]}");
        }
    }

    public function test_gaussian_distribution_favors_lower_and_mid_values_over_max(): void
    {
        $rng = new DefaultRandomProvider();
        $strategy = new EnchantmentStrategy($rng);

        $accessoryTemplate = new ItemTemplate(['id' => (string) Str::ulid(), 'name' => 'Pierścień Testowy', 'type' => 'accessory']);
        $accessoryInstance = new ItemInstance(['id' => (string) Str::ulid(), 'template_id' => $accessoryTemplate->id, 'roll_stats' => ['enchants' => []]]);
        $accessoryInstance->setRelation('template', $accessoryTemplate);

        // Test crit_chance range [1, 5] over 10,000 rolls
        $critCounts = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
        $totalRolls = 10000;

        for ($i = 0; $i < $totalRolls; $i++) {
            $enchants = $strategy->generateMultipleRandomEnchantments($accessoryInstance, 1);
            if (isset($enchants['crit_chance'])) {
                $val = $enchants['crit_chance'];
                if (isset($critCounts[$val])) {
                    $critCounts[$val]++;
                }
            }
        }

        $totalCritRolls = array_sum($critCounts);
        $this->assertGreaterThan(0, $totalCritRolls);

        // Check Gaussian behavior: min value (1) and low values (1, 2) occur much more often than max value (5)
        $this->assertGreaterThan($critCounts[5], $critCounts[1], "Min value (1) should occur more often than max value (5)");
        $this->assertGreaterThan($critCounts[4], $critCounts[2], "Value 2 should occur more often than value 4");
    }
}
