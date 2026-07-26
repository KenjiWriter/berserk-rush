<?php

use App\Application\Items\UpgradeService;
use App\Models\User;
use App\Infrastructure\Persistence\Character;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\Persistence\UpgradeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('UpgradeService upgrades item consuming materials from material_stash', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'ForgeMaster',
        'level' => 10,
        'gold' => 5000,
    ]);

    $weaponTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Stalowy Miecz',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
    ]);

    $materialTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Błotnisty Korzeń',
        'type' => 'material',
        'slot' => null,
        'level_requirement' => 1,
    ]);

    $weaponInstance = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $weaponTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'upgrade_level' => 0,
        'stack_size' => 1,
    ]);

    // Material in material_stash
    $materialInstance = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $materialTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'material_stash',
        'stack_size' => 5,
    ]);

    // Create UpgradeRule from +0 -> +1 with 100% success chance
    UpgradeRule::create([
        'id' => (string) Str::ulid(),
        'from_level' => 0,
        'to_level' => 1,
        'applies_to' => 'type',
        'applies_value' => 'weapon',
        'cost' => [
            'gold' => 500,
            'materials' => [
                [
                    'template_id' => $materialTemplate->id,
                    'quantity' => 2,
                ],
            ],
        ],
        'success_chance' => 1.0,
        'on_fail' => 'nothing',
    ]);

    $upgradeService = new UpgradeService();
    $result = $upgradeService->upgradeItem($character, $weaponInstance);

    expect($result['success'])->toBeTrue()
        ->and($character->fresh()->gold)->toBe(4500)
        ->and($weaponInstance->fresh()->upgrade_level)->toBe(1)
        ->and($materialInstance->fresh()->stack_size)->toBe(3);
});
