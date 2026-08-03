<?php

namespace Tests\Unit;

use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\Character;
use PHPUnit\Framework\TestCase;

class WeaponSpecializationsTest extends TestCase
{
    public function test_character_aggregates_weapon_specialization_stats(): void
    {
        $item1 = new class {
            public function getResolvedBaseStats(): array {
                return [
                    'bleed_chance' => 25,
                    'double_strike_chance' => 15,
                    'armor_pen_pct' => 20,
                    'magic_infusion_chance' => 10,
                ];
            }
            public function getUpgradeBonusStats(): array { return []; }
            public array $roll_stats = [];
        };

        $base = $item1->getResolvedBaseStats();
        
        $this->assertEquals(25, $base['bleed_chance']);
        $this->assertEquals(15, $base['double_strike_chance']);
        $this->assertEquals(20, $base['armor_pen_pct']);
        $this->assertEquals(10, $base['magic_infusion_chance']);
    }

    public function test_enchantment_strategy_bonus_labels(): void
    {
        $this->assertEquals('Szansa na Krwawienie', EnchantmentStrategy::bonusLabel('bleed_chance'));
        $this->assertEquals('Szansa na Podwójny Cios', EnchantmentStrategy::bonusLabel('double_strike_chance'));
        $this->assertEquals('Przebicie Pancerza', EnchantmentStrategy::bonusLabel('armor_pen_pct'));
        $this->assertEquals('Infuzja Magiczna', EnchantmentStrategy::bonusLabel('magic_infusion_chance'));
        $this->assertEquals('Szansa na Otrucie', EnchantmentStrategy::bonusLabel('poison_chance'));
    }
}
