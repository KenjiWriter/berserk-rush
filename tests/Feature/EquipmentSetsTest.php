<?php

use App\Application\Items\EquipmentSetService;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\CharacterEquipmentSetItem;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Livewire\City\Profile;
use App\Models\User;
use Livewire\Livewire;

function makeCharacterWithHelmet(User $user, string $name, int $hpBonus = 10): array
{
    $character = Character::create([
        'user_id' => $user->id,
        'name' => $name,
        'level' => 5,
        'xp' => 0,
        'gold' => 0,
    ]);

    $template = ItemTemplate::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'name' => "Hełm {$name}",
        'type' => 'armor',
        'slot' => 'head',
        'level_requirement' => 1,
        'base_stats' => ['hp_bonus' => $hpBonus, 'defense' => 1],
    ]);

    $item = ItemInstance::create([
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'equipped',
        'bound_to_character' => true,
    ]);

    return [$character, $item, $template];
}

test('profile renders equipment set bar without blade errors', function () {
    $user = User::factory()->create();
    [$character] = makeCharacterWithHelmet($user, 'Renderowany');

    Livewire::actingAs($user)
        ->test(Profile::class, ['character' => $character])
        ->assertOk()
        ->assertSee('Arena PvP')
        ->assertSee('Wojna Gildii')
        ->assertSee('Set 1');
});

test('saving and applying set_1 swaps equipment back and forth', function () {
    $user = User::factory()->create();
    [$character, $originalHelmet, $template] = makeCharacterWithHelmet($user, 'Zamiennik', hpBonus: 10);

    $service = new EquipmentSetService();

    // Save current (original helmet) as set_1.
    $result = $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_1);
    expect($result->isOk())->toBeTrue();
    expect(CharacterEquipmentSetItem::where('character_id', $character->id)->where('set_type', 'set_1')->count())->toBe(1);

    // Equip a different helmet live.
    $otherHelmet = ItemInstance::create([
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'equipped',
        'bound_to_character' => true,
    ]);
    $originalHelmet->update(['location' => 'inventory', 'bound_to_character' => false]);
    $character->clearStatsCache();

    expect($character->fresh()->equippedItems()->first()->id)->toBe($otherHelmet->id);

    // Applying set_1 should bring the original helmet back.
    $applyResult = $service->applySet($character, CharacterEquipmentSetItem::SET_1);
    expect($applyResult->isOk())->toBeTrue();

    $character->refresh();
    $character->clearStatsCache();
    expect($character->equippedItems()->first()->id)->toBe($originalHelmet->id);
    expect($otherHelmet->fresh()->location)->toBe('inventory');
});

test('set_2 and set_3 require premium to save or apply', function () {
    $user = User::factory()->create(['premium_until' => null]);
    [$character] = makeCharacterWithHelmet($user, 'BezVipa');

    $service = new EquipmentSetService();

    $result = $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_2);
    expect($result->isError())->toBeTrue();
    expect($result->getErrorCode())->toBe('VIP_REQUIRED');

    $user->update(['premium_until' => now()->addDays(7)]);
    $character->refresh();

    $result = $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_2);
    expect($result->isOk())->toBeTrue();
});

test('pvp and guild_war sets cannot be physically worn', function () {
    $user = User::factory()->create();
    [$character] = makeCharacterWithHelmet($user, 'NieDoNoszenia');

    $service = new EquipmentSetService();
    $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_PVP);

    $result = $service->applySet($character, CharacterEquipmentSetItem::SET_PVP);
    expect($result->isError())->toBeTrue();
    expect($result->getErrorCode())->toBe('NOT_WEARABLE');
});

test('createSnapshot falls back per-slot when the pvp set item no longer belongs to the character', function () {
    $user = User::factory()->create();
    [$character, $helmet] = makeCharacterWithHelmet($user, 'Fallback', hpBonus: 40);

    $service = new EquipmentSetService();
    $service->saveCurrentAsSet($character, CharacterEquipmentSetItem::SET_PVP);

    // Simulate the saved helmet being traded away (owner changes, row is not deleted).
    $helmet->update(['owner_character_id' => null]);

    // Swap in a different live helmet with a different hp_bonus.
    $otherTemplate = ItemTemplate::create([
        'id' => (string) \Illuminate\Support\Str::ulid(),
        'name' => 'Hełm Zamienny',
        'type' => 'armor',
        'slot' => 'head',
        'level_requirement' => 1,
        'base_stats' => ['hp_bonus' => 5, 'defense' => 1],
    ]);
    ItemInstance::create([
        'template_id' => $otherTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'equipped',
        'bound_to_character' => true,
    ]);

    $character->refresh();
    $character->clearStatsCache();

    $pvpStats = $character->getEquipmentStats(CharacterEquipmentSetItem::SET_PVP);

    // Falls back to the currently equipped helmet (hp_bonus 5), not the orphaned saved one (40).
    expect($pvpStats['hp_bonus'])->toBe(5);
});

test('profile livewire component saves and applies a set through the actual UI actions', function () {
    $user = User::factory()->create();
    [$character, $originalHelmet, $template] = makeCharacterWithHelmet($user, 'LivewireUI', hpBonus: 10);

    Livewire::actingAs($user)
        ->test(Profile::class, ['character' => $character])
        ->call('saveEquipmentSet', CharacterEquipmentSetItem::SET_1)
        ->assertDispatched('notify', type: 'success');

    expect(CharacterEquipmentSetItem::where('character_id', $character->id)->where('set_type', 'set_1')->count())->toBe(1);

    $otherHelmet = ItemInstance::create([
        'template_id' => $template->id,
        'owner_character_id' => $character->id,
        'location' => 'equipped',
        'bound_to_character' => true,
    ]);
    $originalHelmet->update(['location' => 'inventory', 'bound_to_character' => false]);
    $character->clearStatsCache();

    Livewire::actingAs($user)
        ->test(Profile::class, ['character' => $character])
        ->call('applyEquipmentSet', CharacterEquipmentSetItem::SET_1)
        ->assertDispatched('notify', type: 'success');

    expect($character->fresh()->equippedItems()->first()->id)->toBe($originalHelmet->id);
    expect($otherHelmet->fresh()->location)->toBe('inventory');
});
