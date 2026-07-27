<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Application\Characters\AllocateAttributesAction;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AllocateAttributesActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_successfully_allocates_attribute_points()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Tester',
            'level' => 5,
            'xp' => 100,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5],
            'character_points' => 12,
        ]);

        $action = new AllocateAttributesAction();
        $result = $action->execute($character, ['str' => 3, 'int' => 2, 'vit' => 1, 'agi' => 4]);

        $this->assertTrue($result->isOk());
        /** @var Character $updatedCharacter */
        $updatedCharacter = $result->getPayload();

        $this->assertEquals(2, $updatedCharacter->character_points);
        $this->assertEquals([
            'str' => 8,
            'int' => 7,
            'vit' => 6,
            'agi' => 9,
        ], $updatedCharacter->attributes);
    }

    public function test_rejects_allocation_when_insufficient_points()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Tester2',
            'level' => 2,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5],
            'character_points' => 3,
        ]);

        $action = new AllocateAttributesAction();
        $result = $action->execute($character, ['str' => 5]);

        $this->assertTrue($result->isError());
        $this->assertEquals('INSUFFICIENT_POINTS', $result->getErrorCode());

        $character->refresh();
        $this->assertEquals(3, $character->character_points);
        $this->assertEquals(5, $character->attributes['str']);
    }

    public function test_rejects_negative_stat_allocation()
    {
        $user = User::factory()->create();
        $character = Character::create([
            'user_id' => $user->id,
            'name' => 'Tester3',
            'level' => 2,
            'attributes' => ['str' => 5, 'int' => 5, 'vit' => 5, 'agi' => 5],
            'character_points' => 10,
        ]);

        $action = new AllocateAttributesAction();
        $result = $action->execute($character, ['str' => -2, 'int' => 5]);

        $this->assertTrue($result->isError());
        $this->assertEquals('INVALID_VALUE', $result->getErrorCode());

        $character->refresh();
        $this->assertEquals(10, $character->character_points);
    }
}
