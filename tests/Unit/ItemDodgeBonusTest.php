<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ItemDodgeBonusTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_equipment_stats_includes_dodge_chance(): void
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'DodgeTester',
            'level' => 10,
        ]);

        $template = ItemTemplate::create([
            'id' => 'test_armor_dodge',
            'name' => 'Boots of Speed',
            'type' => 'armor',
            'sub_type' => 'feet',
            'slot' => 'feet',
            'rarity' => 'rare',
            'level_requirement' => 1,
            'base_stats' => ['dodge_chance' => 5],
        ]);

        $item = ItemInstance::create([
            'owner_character_id' => $character->id,
            'template_id' => $template->id,
            'slot' => 'feet',
            'location' => 'equipped',
            'roll_stats' => [
                'dodge_chance' => 4,
                'enchants' => [
                    'dodge_chance' => 3
                ]
            ],
        ]);

        $character->clearStatsCache();
        $eqStats = $character->getEquipmentStats();

        // 5 (base) + 4 (roll) + 3 (enchant) = 12%
        $this->assertEquals(12, $eqStats['dodge_chance']);
    }
}
