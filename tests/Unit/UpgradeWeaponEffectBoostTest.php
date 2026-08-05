<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpgradeWeaponEffectBoostTest extends TestCase
{
    use RefreshDatabase;

    public function test_upgrade_level_5_adds_5_percentage_points_to_weapon_special_effect(): void
    {
        $user = \App\Models\User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'DaggerWielder',
            'level' => 20,
        ]);

        $template = ItemTemplate::create([
            'id' => 'test_dagger_effect',
            'name' => 'Sztylet Testowy',
            'type' => 'weapon',
            'sub_type' => 'dagger',
            'slot' => 'main_hand',
            'level_requirement' => 1,
            'base_stats' => ['attack_min' => 10, 'attack_max' => 20, 'poison_chance' => 15],
        ]);

        $item = ItemInstance::create([
            'owner_character_id' => $character->id,
            'template_id' => $template->id,
            'location' => 'equipped',
            'upgrade_level' => 4,
        ]);

        $character->clearStatsCache();
        $statsBelowThreshold = $character->getEquipmentStats();
        // +4: base 15 + zwykłe skalowanie percentageStats (+1pkt/3 poziomy, floor(4/3)=1) = 16, brak progu +5
        $this->assertEquals(16, $statsBelowThreshold['poison_chance']);

        $item->upgrade_level = 5;
        $item->save();
        $character->clearStatsCache();
        $statsAtThreshold = $character->getEquipmentStats();

        // +5: base 15 + zwykłe skalowanie (floor(5/3)=1) + 5pp (próg Kuźni +5) = 21
        $this->assertEquals(21, $statsAtThreshold['poison_chance']);
    }
}
