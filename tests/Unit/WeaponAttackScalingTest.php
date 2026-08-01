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

        // Axe: (STR * 2) * 1.5 = (10 * 2) * 1.5 = 30
        $this->assertEquals(30, $character->getAttributeAttackBonus('axe'));

        // Bow: (STR + AGI) * 1.5 = (10 + 15) * 1.5 = 38
        $this->assertEquals(38, $character->getAttributeAttackBonus('bow'));

        // Sword: (STR + AGI) * 1.5 = (10 + 15) * 1.5 = 38
        $this->assertEquals(38, $character->getAttributeAttackBonus('sword'));

        // Dagger: (STR + AGI) * 1.5 = (10 + 15) * 1.5 = 38
        $this->assertEquals(38, $character->getAttributeAttackBonus('dagger'));

        // Bell: (STR + INT) * 1.5 = (10 + 20) * 1.5 = 45
        $this->assertEquals(45, $character->getAttributeAttackBonus('bell'));

        // Wand: (INT * 2) * 1.5 = (20 * 2) * 1.5 = 60
        $this->assertEquals(60, $character->getAttributeAttackBonus('wand'));

        // Default / Barehands: (STR * 2) * 1.5 = (10 * 2) * 1.5 = 30
        $this->assertEquals(30, $character->getAttributeAttackBonus('barehands'));
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
