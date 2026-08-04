<?php

namespace Tests\Unit;

use App\Infrastructure\Persistence\Character;
use App\Livewire\Adventure\MapStub;
use App\Models\User;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CombatSpeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_speed_5_locked_for_level_under_30_without_vip(): void
    {
        $user = User::factory()->create(['premium_until' => null]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroLowLvl',
            'class' => 'warrior',
            'level' => 15,
            'experience' => 0,
            'gold' => 0,
        ]);
        $character->setRelation('user', $user);

        $component = new MapStub();
        $component->character = $character;

        $this->assertFalse($component->canUseSpeed5());

        $component->setPlaybackSpeed(5);
        $this->assertEquals(2, $component->playbackSpeed);
    }

    public function test_speed_5_unlocked_for_level_30_or_above(): void
    {
        $user = User::factory()->create(['premium_until' => null]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'HeroLvl30',
            'class' => 'warrior',
            'level' => 30,
            'experience' => 0,
            'gold' => 0,
        ]);
        $character->setRelation('user', $user);

        $component = new MapStub();
        $component->character = $character;

        $this->assertTrue($component->canUseSpeed5());

        $component->setPlaybackSpeed(5);
        $this->assertEquals(5, $component->playbackSpeed);
    }

    public function test_speed_5_unlocked_for_vip_user_even_at_low_level(): void
    {
        $user = User::factory()->create(['premium_until' => now()->addDays(7)]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'VipHero',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
            'gold' => 0,
        ]);
        $character->setRelation('user', $user);

        $component = new MapStub();
        $component->character = $character;

        $this->assertTrue($component->canUseSpeed5());

        $component->setPlaybackSpeed(5);
        $this->assertEquals(5, $component->playbackSpeed);
    }
}
