<?php

namespace Tests\Unit;

use App\Infrastructure\Persistence\CombatSkill;
use PHPUnit\Framework\TestCase;

class CombatSkillDamageScalingTest extends TestCase
{
    public function test_is_magic_skill_identification(): void
    {
        $wandSkill = new CombatSkill([
            'required_weapon_type' => 'wand',
            'is_magic' => true,
        ]);

        $bellSkill = new CombatSkill([
            'required_weapon_type' => 'bell',
            'is_magic' => false,
        ]);

        $swordSkill = new CombatSkill([
            'required_weapon_type' => 'sword',
            'is_magic' => false,
        ]);

        $this->assertTrue($wandSkill->isMagicSkill());
        $this->assertTrue($bellSkill->isMagicSkill());
        $this->assertFalse($swordSkill->isMagicSkill());
    }
}
