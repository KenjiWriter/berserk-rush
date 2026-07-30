<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\RNG\RandomProvider;
use Illuminate\Support\Str;

class EnchantmentStrategyHpFixTest extends TestCase
{
    public function test_hp_bonus_range_for_accessories_and_armors_is_minus_20_to_50(): void
    {
        $rng = app(RandomProvider::class);
        $strategy = new EnchantmentStrategy($rng);

        $accessoryTemplate = new ItemTemplate([
            'id' => (string) Str::ulid(),
            'name' => 'Pierścień Testowy',
            'type' => 'accessory',
            'slot' => 'ring',
        ]);

        $itemInstance = new ItemInstance([
            'id' => (string) Str::ulid(),
            'template_id' => $accessoryTemplate->id,
            'roll_stats' => ['enchants' => []],
        ]);
        $itemInstance->setRelation('template', $accessoryTemplate);

        $bonuses = $strategy->getPossibleBonuses($itemInstance);
        $this->assertEquals([-20, 50], $bonuses['hp_bonus']);

        // Roll 200 random enchantments and check hp_bonus values are within [-20, 50]
        for ($i = 0; $i < 200; $i++) {
            $enchant = $strategy->generateRandomEnchantment($itemInstance);
            if ($enchant['type'] === 'hp_bonus') {
                $this->assertGreaterThanOrEqual(-20, $enchant['value']);
                $this->assertLessThanOrEqual(50, $enchant['value']);
            }
        }
    }

    public function test_max_bonus_for_hp_bonus_is_50(): void
    {
        $this->assertEquals(50, EnchantmentStrategy::getMaxBonusForType('accessory', 'hp_bonus'));
        $this->assertEquals(50, EnchantmentStrategy::getMaxBonusForType('armor', 'hp_bonus'));
        $this->assertTrue(EnchantmentStrategy::isEnchantMaxed('accessory', 'hp_bonus', 50));
        $this->assertFalse(EnchantmentStrategy::isEnchantMaxed('accessory', 'hp_bonus', 40));
    }
}
