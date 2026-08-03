<?php

namespace Tests\Feature\Guild;

use App\Infrastructure\Persistence\Character;
use App\Livewire\City\GuildComponent;
use App\Models\Guild;
use App\Models\GuildMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuildEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_guild_leader_can_update_guild_settings_and_description(): void
    {
        $user = User::factory()->create(['game_stage' => 35]);
        $guild = Guild::create([
            'name' => 'Stary Zakon',
            'title' => 'Stary Tytuł',
            'description' => 'Stary opis gildii',
            'min_level' => 5,
            'is_public' => true,
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'LeaderHero',
            'class' => 'warrior',
            'level' => 10,
            'experience' => 0,
            'gold' => 1000,
            'guild_id' => $guild->id,
        ]);

        GuildMember::create([
            'guild_id' => $guild->id,
            'character_id' => $character->id,
            'role' => 'leader',
        ]);

        $this->actingAs($user);

        Livewire::test(GuildComponent::class, ['character' => $character])
            ->set('panelTab', 'settings')
            ->set('editGuildName', 'Nowy Zakon Światła')
            ->set('editGuildTitle', 'Nowy Wspaniały Tytuł')
            ->set('editGuildDescription', 'Nowy rozbudowany regulamin i opis gildii.')
            ->set('editGuildMinLevel', 15)
            ->set('editGuildIsPublic', false)
            ->call('updateGuildSettings')
            ->assertDispatched('notify');

        $freshGuild = $guild->fresh();
        $this->assertEquals('Nowy Zakon Światła', $freshGuild->name);
        $this->assertEquals('Nowy Wspaniały Tytuł', $freshGuild->title);
        $this->assertEquals('Nowy rozbudowany regulamin i opis gildii.', $freshGuild->description);
        $this->assertEquals(15, $freshGuild->min_level);
        $this->assertFalse((bool)$freshGuild->is_public);
    }

    public function test_regular_guild_member_cannot_update_guild_settings(): void
    {
        $user = User::factory()->create(['game_stage' => 35]);
        $guild = Guild::create([
            'name' => 'Zakon Rycerzy',
            'title' => 'Tytuł',
            'description' => 'Opis',
            'min_level' => 1,
            'is_public' => true,
        ]);

        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'MemberHero',
            'class' => 'mage',
            'level' => 5,
            'experience' => 0,
            'gold' => 500,
            'guild_id' => $guild->id,
        ]);

        GuildMember::create([
            'guild_id' => $guild->id,
            'character_id' => $character->id,
            'role' => 'member',
        ]);

        $this->actingAs($user);

        Livewire::test(GuildComponent::class, ['character' => $character])
            ->set('panelTab', 'settings')
            ->set('editGuildName', 'Hacked Guild Name')
            ->call('updateGuildSettings')
            ->assertHasErrors(['settings']);

        $this->assertEquals('Zakon Rycerzy', $guild->fresh()->name);
    }

    public function test_guild_creation_defaults_to_public_and_can_be_toggled(): void
    {
        $user = User::factory()->create(['game_stage' => 35, 'gems' => 500]);
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'FounderHero',
            'class' => 'warrior',
            'level' => 10,
            'experience' => 0,
            'gold' => 1000,
        ]);

        $this->actingAs($user);

        // Test creating default public guild
        Livewire::test(GuildComponent::class, ['character' => $character])
            ->set('newGuildName', 'Nowa Otowarta Gildia')
            ->set('newGuildTitle', 'Tytuł')
            ->set('newGuildMinLevel', 1)
            ->call('createGuild');

        $createdGuild = Guild::where('name', 'Nowa Otowarta Gildia')->first();
        $this->assertNotNull($createdGuild);
        $this->assertTrue((bool)$createdGuild->is_public);

        // Test toggling settings to false and then true
        $character->refresh();
        Livewire::test(GuildComponent::class, ['character' => $character])
            ->call('initEditGuild')
            ->set('editGuildIsPublic', false)
            ->call('updateGuildSettings');

        $this->assertFalse((bool)$createdGuild->fresh()->is_public);

        Livewire::test(GuildComponent::class, ['character' => $character])
            ->call('initEditGuild')
            ->set('editGuildIsPublic', true)
            ->call('updateGuildSettings');

        $this->assertTrue((bool)$createdGuild->fresh()->is_public);
    }
}
