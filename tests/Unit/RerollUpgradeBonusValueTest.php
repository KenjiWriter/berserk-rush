<?php

namespace Tests\Unit;

use App\Application\Wizard\RerollUpgradeBonusValue;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RerollUpgradeBonusValueTest extends TestCase
{
    use RefreshDatabase;

    public function test_reroll_upgrade_bonus_value_preserves_stat_type_and_updates_value(): void
    {
        $user = User::factory()->create(['gems' => 10]);
        $character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'TestHero',
            'level' => 50,
            'gold' => 5000,
        ]);

        $template = ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Testowy Miecz',
            'type' => 'weapon',
            'sub_type' => 'sword',
            'slot' => 'main_hand',
            'rarity' => 'common',
        ]);

        $item = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'rare',
            'upgrade_level' => 3,
            'roll_stats' => [
                'upgrade_bonuses' => ['attack_power' => 5],
            ],
        ]);

        $action = app(RerollUpgradeBonusValue::class);
        $result = $action->execute($item, $character, 'gold');

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertTrue($payload['success']);
        $this->assertEquals('attack_power', $payload['bonus']['type']);
        $this->assertEquals(1500, $character->fresh()->gold);

        $item->refresh();
        $upgradeBonuses = $item->getUpgradeBonuses();
        $this->assertArrayHasKey('attack_power', $upgradeBonuses);
        $this->assertCount(1, $upgradeBonuses);
    }

    public function test_reroll_upgrade_bonus_value_returns_error_if_no_upgrade_bonus(): void
    {
        $user = User::factory()->create(['gems' => 10]);
        $character = Character::create([
            'id' => (string) Str::ulid(),
            'user_id' => $user->id,
            'name' => 'TestHero2',
            'level' => 50,
            'gold' => 1000,
        ]);

        $template = ItemTemplate::create([
            'id' => (string) Str::ulid(),
            'name' => 'Testowy Miecz',
            'type' => 'weapon',
            'sub_type' => 'sword',
            'slot' => 'main_hand',
            'rarity' => 'common',
        ]);

        $item = ItemInstance::create([
            'id' => (string) Str::ulid(),
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'common',
            'upgrade_level' => 0,
            'roll_stats' => [],
        ]);

        $action = app(RerollUpgradeBonusValue::class);
        $result = $action->execute($item, $character, 'gold');

        $this->assertTrue($result->isError());
        $this->assertEquals('NO_UPGRADE_BONUS', $result->getErrorCode());
    }
}
