<?php

namespace Tests\Feature;

use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\PvpEncounter;
use App\Livewire\City\ArenaCombat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArenaCombatTest extends TestCase
{
    use RefreshDatabase;

    public function test_arena_combat_renders_without_blade_syntax_errors(): void
    {
        $user = User::factory()->create();
        $attacker = Character::create([
            'user_id' => $user->id,
            'name' => 'Wojownik1',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
            'gold' => 100,
        ]);
        $defender = Character::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Wojownik2',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
            'gold' => 100,
        ]);

        $pvp = PvpEncounter::create([
            'attacker_character_id' => $attacker->id,
            'defender_character_id' => $defender->id,
            'attacker_snapshot' => [
                'name' => $attacker->name,
                'level' => 1,
                'max_hp' => 100,
                'attributes' => [],
            ],
            'defender_snapshot' => [
                'name' => $defender->name,
                'level' => 1,
                'max_hp' => 100,
                'attributes' => [],
            ],
            'state' => 'finished',
            'turns' => [],
            'combat_data' => ['attacker_first' => true],
            'winner_character_id' => $attacker->id,
            'attacker_elo_change' => 15,
            'defender_elo_change' => -15,
            'arena_tokens_reward' => 10,
        ]);

        Livewire::actingAs($user)
            ->test(ArenaCombat::class, ['character' => $attacker, 'pvpId' => $pvp->id])
            ->assertOk()
            ->assertSee('Odtwarzacz Walki PvP');
    }
}
