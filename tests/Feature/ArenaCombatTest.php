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
            'level' => 15,
            'experience' => 0,
            'gold' => 100,
        ]);
        $defender = Character::create([
            'user_id' => User::factory()->create()->id,
            'name' => 'Wojownik2',
            'class' => 'warrior',
            'level' => 15,
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

    public function test_gvg_arena_combat_renders_without_errors(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'GvGHero',
            'class' => 'warrior',
            'level' => 15,
            'experience' => 0,
            'gold' => 100,
        ]);

        $guildA = \App\Models\Guild::create([
            'name' => 'Smoki',
            'leader_character_id' => $character->id,
            'level' => 1,
            'experience' => 0,
            'gold' => 1000,
            'gems' => 100,
            'max_members' => 10,
        ]);

        $character->update(['guild_id' => $guildA->id]);

        $enemyChar = Character::create(['user_id' => User::factory()->create()->id, 'name' => 'EnemyLeader', 'class' => 'mage', 'level' => 10, 'experience' => 0, 'gold' => 0]);

        $guildB = \App\Models\Guild::create([
            'name' => 'Rycerze',
            'leader_character_id' => $enemyChar->id,
            'level' => 1,
            'experience' => 0,
            'gold' => 1000,
            'gems' => 100,
            'max_members' => 10,
        ]);

        $war = \App\Infrastructure\Persistence\GuildWar::create([
            'challenger_guild_id' => $guildA->id,
            'defender_guild_id' => $guildB->id,
            'status' => 'finished',
            'challenger_roster' => [$character->id],
            'defender_roster' => [$enemyChar->id],
            'winner_guild_id' => $guildA->id,
        ]);

        $fight = \App\Infrastructure\Persistence\GuildWarFight::create([
            'guild_war_id' => $war->id,
            'fight_order' => 1,
            'challenger_character_id' => $character->id,
            'defender_character_id' => $enemyChar->id,
            'winner_character_id' => $character->id,
            'challenger_snapshot' => [
                [
                    'id' => $character->id,
                    'name' => $character->name,
                    'level' => 10,
                    'max_hp' => 300,
                    'attributes' => ['str' => 20, 'int' => 5, 'vit' => 15, 'agi' => 10],
                    'equipment_stats' => [],
                    'weapon_type' => 'sword',
                    'skills' => [],
                ]
            ],
            'defender_snapshot' => [
                [
                    'id' => $enemyChar->id,
                    'name' => 'EnemyLeader',
                    'level' => 10,
                    'max_hp' => 250,
                    'attributes' => ['str' => 5, 'int' => 25, 'vit' => 10, 'agi' => 8],
                    'equipment_stats' => [],
                    'weapon_type' => 'wand',
                    'skills' => [],
                ]
            ],
            'turns' => [
                [
                    'actor_side' => 'challenger',
                    'actor_idx' => 0,
                    'actor_name' => 'GvGHero',
                    'target_side' => 'defender',
                    'target_idx' => 0,
                    'target_name' => 'EnemyLeader',
                    'type' => 'hit',
                    'value' => 50,
                    'crit' => false,
                    'round' => 1,
                    'team_state' => [
                        ['side' => 'challenger', 'idx' => 0, 'name' => 'GvGHero', 'level' => 10, 'hp' => 300, 'maxHp' => 300, 'alive' => true],
                        ['side' => 'defender', 'idx' => 0, 'name' => 'EnemyLeader', 'level' => 10, 'hp' => 200, 'maxHp' => 250, 'alive' => true],
                    ],
                ]
            ],
            'combat_data' => [],
            'challenger_survivors' => 1,
            'defender_survivors' => 0,
            'rounds' => 1,
        ]);

        Livewire::actingAs($user)
            ->test(ArenaCombat::class, ['character' => $character, 'gvgId' => $fight->id])
            ->assertOk()
            ->assertSee('Odtwarzacz Walki GvG')
            ->assertSee('Smoki');
    }
}
