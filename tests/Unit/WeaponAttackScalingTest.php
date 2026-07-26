<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WeaponAttackScalingTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_attack_bonus_for_weapon_types(): void
    {
        $character = new Character([
            'level' => 1,
            'attributes' => [
                'str' => 10,
                'int' => 20,
                'agi' => 15,
                'vit' => 5,
            ]
        ]);

        // Axe: STR * 2 = 10 * 2 = 20
        $this->assertEquals(20, $character->getAttributeAttackBonus('axe'));

        // Bow: STR + AGI = 10 + 15 = 25
        $this->assertEquals(25, $character->getAttributeAttackBonus('bow'));

        // Sword: STR + AGI = 10 + 15 = 25
        $this->assertEquals(25, $character->getAttributeAttackBonus('sword'));

        // Dagger: STR + AGI = 10 + 15 = 25
        $this->assertEquals(25, $character->getAttributeAttackBonus('dagger'));

        // Bell: STR + INT = 10 + 20 = 30
        $this->assertEquals(30, $character->getAttributeAttackBonus('bell'));

        // Wand: INT * 2 = 20 * 2 = 40
        $this->assertEquals(40, $character->getAttributeAttackBonus('wand'));

        // Default / Barehands: STR * 2 = 10 * 2 = 20
        $this->assertEquals(20, $character->getAttributeAttackBonus('barehands'));
    }

    public function test_profile_renders_highlighted_scaling_stats(): void
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroTest',
            'level' => 1,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
            'character_points' => 0,
            'skill_points' => 0,
        ]);

        $this->actingAs($user);

        \Livewire\Livewire::test(\App\Livewire\City\Profile::class, ['character' => $character])
            ->assertViewHas('activeScalingStats')
            ->assertSee('Atrybuty wpływające na atak są podświetlone');
    }
}
