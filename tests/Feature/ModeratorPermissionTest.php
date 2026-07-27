<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Admin\Characters;

class ModeratorPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_permission_helpers(): void
    {
        $userNormal = User::factory()->create(['permission_level' => 0]);
        $userMod = User::factory()->create(['permission_level' => 9]);
        $userAdmin = User::factory()->create(['permission_level' => 10]);

        $this->assertFalse($userNormal->isModerator());
        $this->assertFalse($userNormal->isAdmin());
        $this->assertFalse($userNormal->hasAdminAccess());

        $this->assertTrue($userMod->isModerator());
        $this->assertFalse($userMod->isAdmin());
        $this->assertTrue($userMod->hasAdminAccess());

        $this->assertFalse($userAdmin->isModerator());
        $this->assertTrue($userAdmin->isAdmin());
        $this->assertTrue($userAdmin->hasAdminAccess());
    }

    public function test_moderator_can_access_allowed_admin_routes_but_forbidden_from_admin_only_routes(): void
    {
        $moderator = User::factory()->create(['permission_level' => 9]);

        // Allowed routes
        $this->actingAs($moderator)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.monsters'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.maps'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.item-templates'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.merchant-items'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.item-recipes'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.upgrade-rules'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.loot-tables'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.pet-templates'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.quests'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.titles'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.achievements'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.dungeons'))->assertOk();
        $this->actingAs($moderator)->get(route('admin.combat-skills'))->assertOk();

        // Forbidden routes for moderator
        $this->actingAs($moderator)->get(route('admin.news'))->assertStatus(403);
        $this->actingAs($moderator)->get(route('admin.characters'))->assertStatus(403);
        $this->actingAs($moderator)->get(route('admin.suggestions'))->assertStatus(403);
        $this->actingAs($moderator)->get(route('admin.events'))->assertStatus(403);
        $this->actingAs($moderator)->get(route('admin.gallery'))->assertStatus(403);
        $this->actingAs($moderator)->get(route('admin.item-shop-packages'))->assertStatus(403);
    }

    public function test_admin_can_grant_and_revoke_moderator_in_characters_panel(): void
    {
        $admin = User::factory()->create(['permission_level' => 10]);
        $targetUser = User::factory()->create(['permission_level' => 0]);
        $targetChar = Character::create([
            'user_id' => $targetUser->id,
            'name' => 'TestHero',
            'class' => 'warrior',
            'level' => 1,
            'experience' => 0,
        ]);

        $this->actingAs($admin);

        Livewire::test(Characters::class)
            ->call('grantModerator', (string) $targetUser->id)
            ->assertDispatched('notify');

        $this->assertEquals(9, $targetUser->fresh()->permission_level);
        $this->assertTrue($targetUser->fresh()->isModerator());

        Livewire::test(Characters::class)
            ->call('revokeModerator', (string) $targetUser->id)
            ->assertDispatched('notify');

        $this->assertEquals(0, $targetUser->fresh()->permission_level);
        $this->assertFalse($targetUser->fresh()->isModerator());
    }
}
