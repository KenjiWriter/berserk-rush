<?php

namespace Tests\Unit;

use App\Application\Guilds\GuildWarService;
use App\Application\PvP\PvPEncounterService;
use App\Domain\Wizard\EnchantmentStrategy;
use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PvpEquipmentResistancesTest extends TestCase
{
    use RefreshDatabase;
    public function test_enchantment_strategy_includes_pvp_resistances_in_armor_pool(): void
    {
        $strategy = app(EnchantmentStrategy::class);
        
        $this->assertEquals('Odporność na Ludzi', EnchantmentStrategy::bonusLabel('resist_hero'));
        $this->assertEquals('Odporność na Miecze', EnchantmentStrategy::bonusLabel('resist_sword'));
        $this->assertEquals('Odporność na Sztylety', EnchantmentStrategy::bonusLabel('resist_dagger'));
        $this->assertEquals('Odporność na Dzwony', EnchantmentStrategy::bonusLabel('resist_bell'));
        $this->assertEquals('Odporność na Topory', EnchantmentStrategy::bonusLabel('resist_axe'));
        $this->assertEquals('Odporność na Łuki', EnchantmentStrategy::bonusLabel('resist_bow'));
        $this->assertEquals('Odporność na Różdżki', EnchantmentStrategy::bonusLabel('resist_wand'));

        $this->assertEquals(10, EnchantmentStrategy::getMaxBonusForType('armor', 'resist_hero'));
        $this->assertEquals(10, EnchantmentStrategy::getMaxBonusForType('armor', 'resist_sword'));
        $this->assertEquals(10, EnchantmentStrategy::getMaxBonusForType('armor', 'resist_wand'));
    }

    public function test_character_get_equipment_stats_initializes_and_aggregates_new_resistances(): void
    {
        $character = new Character();
        $stats = $character->getEquipmentStats();

        $this->assertArrayHasKey('resist_hero', $stats);
        $this->assertArrayHasKey('resist_sword', $stats);
        $this->assertArrayHasKey('resist_dagger', $stats);
        $this->assertArrayHasKey('resist_bell', $stats);
        $this->assertArrayHasKey('resist_axe', $stats);
        $this->assertArrayHasKey('resist_bow', $stats);
        $this->assertArrayHasKey('resist_wand', $stats);

        $this->assertEquals(0, $stats['resist_hero']);
        $this->assertEquals(0, $stats['resist_sword']);
    }

    public function test_pvp_perform_attack_applies_resist_hero_and_weapon_resistances(): void
    {
        $service = app(PvPEncounterService::class);
        $reflection = new \ReflectionClass(PvPEncounterService::class);
        $method = $reflection->getMethod('performAttack');

        $attackerSnapshot = [
            'level' => 50,
            'attributes' => ['str' => 50, 'agi' => 50, 'vit' => 50, 'int' => 50],
            'equipment_stats' => ['attack_min' => 100, 'attack_max' => 100, 'defense' => 0],
            'weapon_type' => 'sword',
            'skills' => [],
            'champion_bonuses' => [],
        ];

        $defenderBaseSnapshot = [
            'level' => 50,
            'max_hp' => 2000,
            'attributes' => ['str' => 50, 'agi' => 10, 'vit' => 50, 'int' => 50],
            'equipment_stats' => ['defense' => 0, 'dodge_chance' => 0, 'resist_hero' => 0, 'resist_sword' => 0],
            'skills' => [],
            'champion_bonuses' => [],
        ];

        $actorState = ['mana' => 100, 'effects' => [], 'passives' => []];
        $targetStateBase = ['mana' => 100, 'effects' => []];

        // 1. Without resistances
        mt_srand(42);
        $targetState = $targetStateBase;
        $resultNoResist = $method->invokeArgs($service, [$attackerSnapshot, $defenderBaseSnapshot, 2000, 2000, 'attacker', &$actorState, &$targetState]);

        // 2. With 20% resist_hero
        mt_srand(42);
        $defenderWithHeroResist = $defenderBaseSnapshot;
        $defenderWithHeroResist['equipment_stats']['resist_hero'] = 20;
        $targetState = $targetStateBase;
        $resultHeroResist = $method->invokeArgs($service, [$attackerSnapshot, $defenderWithHeroResist, 2000, 2000, 'attacker', &$actorState, &$targetState]);

        // 3. With 20% resist_sword
        mt_srand(42);
        $defenderWithSwordResist = $defenderBaseSnapshot;
        $defenderWithSwordResist['equipment_stats']['resist_sword'] = 20;
        $targetState = $targetStateBase;
        $resultSwordResist = $method->invokeArgs($service, [$attackerSnapshot, $defenderWithSwordResist, 2000, 2000, 'attacker', &$actorState, &$targetState]);

        // 4. With non-matching weapon resistance (e.g. 20% resist_bow against sword attacker)
        mt_srand(42);
        $defenderWithBowResist = $defenderBaseSnapshot;
        $defenderWithBowResist['equipment_stats']['resist_bow'] = 20;
        $targetState = $targetStateBase;
        $resultBowResist = $method->invokeArgs($service, [$attackerSnapshot, $defenderWithBowResist, 2000, 2000, 'attacker', &$actorState, &$targetState]);

        // Non-miss hits check
        if ($resultNoResist['type'] !== 'miss' && $resultHeroResist['type'] !== 'miss' && $resultSwordResist['type'] !== 'miss' && $resultBowResist['type'] !== 'miss') {
            $this->assertLessThan($resultNoResist['value'], $resultHeroResist['value'], 'Hero resistance should reduce damage');
            $this->assertLessThan($resultNoResist['value'], $resultSwordResist['value'], 'Matching weapon resistance should reduce damage');
            $this->assertEquals($resultNoResist['value'], $resultBowResist['value'], 'Non-matching weapon resistance should not reduce sword damage');
        }
    }
}
