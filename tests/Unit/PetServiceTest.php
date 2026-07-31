<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Pet;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Application\Pets\PetService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PetServiceTest extends TestCase
{
    use RefreshDatabase;

    private function createTestCharacter(): Character
    {
        $user = User::factory()->create();
        return Character::create([
            'user_id' => $user->id,
            'name' => 'PetTester',
            'class' => 'warrior',
            'level' => 10,
            'experience' => 0,
            'gold' => 100,
        ]);
    }

    public function test_pet_stats_scale_with_level(): void
    {
        $character = $this->createTestCharacter();

        $pet = Pet::create([
            'character_id' => $character->id,
            'name' => 'Test Smok',
            'rarity' => 'common',
            'stats' => ['str' => 10, 'agi' => 10, 'int' => 10, 'vit' => 10],
            'level' => 1,
            'exp' => 0,
            'is_equipped' => false,
            'icon' => 'pet_dragon',
        ]);

        // Level 1: 100% stats (10 each)
        $this->assertEquals(['str' => 10, 'agi' => 10, 'int' => 10, 'vit' => 10], $pet->getTotalStats());

        // Level 11: +100% stats (20 each)
        $pet->level = 11;
        $this->assertEquals(['str' => 20, 'agi' => 20, 'int' => 20, 'vit' => 20], $pet->getTotalStats());
    }

    public function test_pet_service_synthesizes_three_pets(): void
    {
        $character = $this->createTestCharacter();

        $p1 = Pet::create([
            'character_id' => $character->id,
            'name' => 'Pet 1',
            'rarity' => 'common',
            'stats' => ['str' => 2, 'agi' => 2],
            'level' => 1,
            'exp' => 0,
            'is_equipped' => false,
        ]);

        $p2 = Pet::create([
            'character_id' => $character->id,
            'name' => 'Pet 2',
            'rarity' => 'common',
            'stats' => ['str' => 2, 'agi' => 2],
            'level' => 1,
            'exp' => 0,
            'is_equipped' => false,
        ]);

        $p3 = Pet::create([
            'character_id' => $character->id,
            'name' => 'Pet 3',
            'rarity' => 'common',
            'stats' => ['str' => 2, 'agi' => 2],
            'level' => 1,
            'exp' => 0,
            'is_equipped' => false,
        ]);

        $service = new PetService();
        $result = $service->synthesizePets($character, [$p1->id, $p2->id, $p3->id]);

        $this->assertFalse($result->isError());
        $this->assertDatabaseMissing('pets', ['id' => $p1->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p2->id]);
        $this->assertDatabaseMissing('pets', ['id' => $p3->id]);
    }

    public function test_feeding_pet_with_inventory_item_grants_exp(): void
    {
        $character = $this->createTestCharacter();

        $pet = Pet::create([
            'character_id' => $character->id,
            'name' => 'Glutton Pet',
            'rarity' => 'common',
            'stats' => ['str' => 5],
            'level' => 1,
            'exp' => 0,
            'is_equipped' => false,
        ]);

        $template = ItemTemplate::create([
            'id' => 'sword-lvl-50',
            'name' => 'Miecz Poziomu 50',
            'type' => 'weapon',
            'level_requirement' => 50,
        ]);

        $item = ItemInstance::create([
            'template_id' => $template->id,
            'owner_character_id' => $character->id,
            'location' => 'inventory',
            'rarity' => 'common',
        ]);

        $service = new PetService();
        $result = $service->feedPet($character, $pet->id, [$item->id]);

        $this->assertFalse($result->isError());
        $payload = $result->getPayload();
        $this->assertEquals(50, $payload['gainedExp']);
        $this->assertDatabaseMissing('item_instances', ['id' => $item->id]);
    }
}
