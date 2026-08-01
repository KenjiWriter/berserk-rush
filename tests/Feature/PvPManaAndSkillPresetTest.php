<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CombatSkill;
use App\Infrastructure\Persistence\CharacterCombatSkill;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
use App\Infrastructure\Persistence\CharacterSkillSetItem;
use App\Application\Items\EquipmentSetService;
use App\Application\PvP\PvPEncounterService;
use App\Infrastructure\Persistence\PvpEncounter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PvPManaAndSkillPresetTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_snapshot_resolves_preset_skills(): void
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Wojownik',
            'level' => 20,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
        ]);

        $skill = CombatSkill::create([
            'name' => 'Cięcie Miecza',
            'type' => 'active',
            'effect_type' => 'direct_dmg',
            'required_weapon_type' => 'sword',
            'base_cooldown' => 1,
            'base_duration' => 1,
            'base_value' => 1.5,
            'scaling_value' => 0.1,
            'base_mana_cost' => 10,
            'scaling_mana_cost' => 2,
        ]);

        $charSkill = CharacterCombatSkill::create([
            'character_id' => $character->id,
            'combat_skill_id' => $skill->id,
            'level' => 1,
            'is_equipped' => true,
        ]);

        $service = app(EquipmentSetService::class);
        $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_PVP);

        $this->assertDatabaseHas('character_skill_set_items', [
            'character_id' => $character->id,
            'set_type' => CharacterEquipmentSetItem::SET_PVP,
            'combat_skill_id' => $skill->id,
        ]);

        // Un-equip skill on active character
        $charSkill->update(['is_equipped' => false]);

        // Snapshot created for PVP set should still resolve the saved preset skill
        $snapshot = $character->createSnapshot(CharacterEquipmentSetItem::SET_PVP);
        $this->assertNotEmpty($snapshot['skills']);
        $this->assertEquals('Cięcie Miecza', $snapshot['skills'][0]['name']);
    }

    public function test_pvp_combat_simulation_tracks_and_regenerates_mana(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $attackerChar = Character::create([
            'user_id' => $user1->id,
            'name' => 'Atakujacy',
            'level' => 20,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
        ]);

        $defenderChar = Character::create([
            'user_id' => $user2->id,
            'name' => 'WidmoObronca',
            'level' => 20,
            'attributes' => ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10],
        ]);

        $skill = CombatSkill::create([
            'name' => 'Kula Ognia',
            'type' => 'active',
            'effect_type' => 'direct_dmg',
            'required_weapon_type' => 'all',
            'base_cooldown' => 1,
            'base_duration' => 1,
            'base_value' => 1.2,
            'scaling_value' => 0.1,
            'base_mana_cost' => 15,
            'scaling_mana_cost' => 1,
        ]);

        CharacterCombatSkill::create([
            'character_id' => $attackerChar->id,
            'combat_skill_id' => $skill->id,
            'level' => 1,
            'is_equipped' => true,
        ]);

        $pvpEncounter = PvpEncounter::create([
            'attacker_character_id' => $attackerChar->id,
            'defender_character_id' => $defenderChar->id,
            'state' => 'pending',
            'attacker_snapshot' => $attackerChar->createSnapshot(),
            'defender_snapshot' => $defenderChar->createSnapshot(CharacterEquipmentSetItem::SET_PVP),
        ]);

        $pvpService = app(PvPEncounterService::class);
        $result = $pvpService->simulate($pvpEncounter);

        $this->assertTrue($result->isOk());
        $turns = $result->getPayload()['turns'];

        $this->assertNotEmpty($turns);
        $firstTurn = $turns[0];

        $this->assertArrayHasKey('attackerMana', $firstTurn);
        $this->assertArrayHasKey('defenderMana', $firstTurn);
    }
}
