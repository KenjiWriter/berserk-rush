<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\ItemInstance;
use App\Application\Characters\CreateCharacter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CreateCharacterTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_new_character_receives_rusty_sword_on_creation()
    {
        $starterWeapon = ItemTemplate::create([
            'id' => '01k4jpx94j70x2vv10b835prm4',
            'name' => 'Zardzewiały Miecz',
            'type' => 'weapon',
            'sub_type' => 'sword',
            'slot' => 'main_hand',
            'level_requirement' => 1,
            'base_stats' => ['attack_min' => 2, 'attack_max' => 4, 'str_bonus' => 1],
            'description' => 'Podstawowa broń.',
            'icon' => 'zardzewialy-miecz.png',
            'rarity_weights' => ['common' => 100],
        ]);

        $user = User::factory()->create(['game_stage' => 25]); // Tutorial already completed

        $createService = new CreateCharacter();

        // Create 1st character
        $result1 = $createService->handle($user, 'Wojownik1', 4, 2, 2, 2);
        $this->assertTrue($result1->isOk());
        /** @var Character $char1 */
        $char1 = $result1->getPayload();

        $items1 = ItemInstance::where('owner_character_id', $char1->id)->get();
        $this->assertCount(1, $items1);
        $this->assertEquals('Zardzewiały Miecz', $items1->first()->template->name);

        // Create 2nd character (not tutorial character)
        $result2 = $createService->handle($user, 'Wojownik2', 2, 4, 2, 2);
        $this->assertTrue($result2->isOk());
        /** @var Character $char2 */
        $char2 = $result2->getPayload();

        $items2 = ItemInstance::where('owner_character_id', $char2->id)->get();
        $this->assertCount(1, $items2);
        $this->assertEquals('Zardzewiały Miecz', $items2->first()->template->name);
    }

    public function test_new_character_broadcasts_chat_notification()
    {
        \Illuminate\Support\Facades\Event::fake([\App\Domain\Social\Events\MessageSent::class]);

        $user = User::factory()->create();
        $createService = new CreateCharacter();

        $result = $createService->handle($user, 'Guts', 5, 2, 2, 1);
        $this->assertTrue($result->isOk());

        \Illuminate\Support\Facades\Event::assertDispatched(\App\Domain\Social\Events\MessageSent::class, function ($event) {
            return $event->characterName === 'System' &&
                   $event->characterId === 'system' &&
                   $event->message === 'Wojownik Guts właśnie zaczął swoją przygodę!';
        });
    }
}
