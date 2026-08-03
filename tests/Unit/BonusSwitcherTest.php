<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Livewire\City\Witch;
use App\Infrastructure\Persistence\Character;

class BonusSwitcherTest extends TestCase
{
    public function test_check_criteria_match_returns_true_when_all_targets_met(): void
    {
        $witch = new Witch();

        $itemEnchants = [
            'crit_chance' => 4,
            'attack_power' => 35,
            'poison_chance' => 5,
        ];

        $targetCriteria = [
            ['type' => 'crit_chance', 'min' => 3],
            ['type' => 'attack_power', 'min' => 30],
        ];

        $this->assertTrue($witch->checkCriteriaMatch($itemEnchants, $targetCriteria));
    }

    public function test_check_criteria_match_returns_false_when_target_value_too_low(): void
    {
        $witch = new Witch();

        $itemEnchants = [
            'crit_chance' => 2,
            'attack_power' => 35,
        ];

        $targetCriteria = [
            ['type' => 'crit_chance', 'min' => 3],
            ['type' => 'attack_power', 'min' => 30],
        ];

        $this->assertFalse($witch->checkCriteriaMatch($itemEnchants, $targetCriteria));
    }

    public function test_check_criteria_match_returns_false_when_target_type_missing(): void
    {
        $witch = new Witch();

        $itemEnchants = [
            'crit_chance' => 4,
        ];

        $targetCriteria = [
            ['type' => 'crit_chance', 'min' => 3],
            ['type' => 'poison_chance', 'min' => 2],
        ];

        $this->assertFalse($witch->checkCriteriaMatch($itemEnchants, $targetCriteria));
    }

    public function test_check_criteria_match_returns_false_when_empty_criteria(): void
    {
        $witch = new Witch();

        $itemEnchants = [
            'crit_chance' => 4,
        ];

        $targetCriteria = [];

        $this->assertFalse($witch->checkCriteriaMatch($itemEnchants, $targetCriteria));
    }
}
