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

test('UpgradeService grants a free threshold bonus crossing +3 and removes it on downgrade below +3', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'ThresholdTester',
        'level' => 10,
        'gold' => 100000,
    ]);

    $weaponTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowy Sztylet',
        'type' => 'weapon',
        'sub_type' => 'dagger',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => ['attack_min' => 10, 'attack_max' => 20],
    ]);

    $weaponInstance = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $weaponTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'upgrade_level' => 2,
        'stack_size' => 1,
    ]);

    // +2 -> +3: 100% powodzenia, przekracza próg +3.
    UpgradeRule::create([
        'id' => (string) Str::ulid(),
        'from_level' => 2,
        'to_level' => 3,
        'applies_to' => 'type',
        'applies_value' => 'weapon',
        'cost' => ['gold' => 500, 'materials' => []],
        'success_chance' => 1.0,
        'on_fail' => 'nothing',
    ]);

    $upgradeService = new UpgradeService();
    $result = $upgradeService->upgradeItem($character, $weaponInstance);

    expect($result['success'])->toBeTrue()
        ->and($weaponInstance->fresh()->upgrade_level)->toBe(3)
        ->and($weaponInstance->fresh()->getUpgradeBonuses())->not->toBeEmpty();

    // +3 -> +4 attempt: 0% powodzenia, downgrade -1 z powrotem na +2, cofa próg +3.
    UpgradeRule::create([
        'id' => (string) Str::ulid(),
        'from_level' => 3,
        'to_level' => 4,
        'applies_to' => 'type',
        'applies_value' => 'weapon',
        'cost' => ['gold' => 500, 'materials' => []],
        'success_chance' => 0.0,
        'on_fail' => 'downgrade',
    ]);

    $result2 = $upgradeService->upgradeItem($character, $weaponInstance->fresh());

    expect($result2['success'])->toBeFalse()
        ->and($weaponInstance->fresh()->upgrade_level)->toBe(2)
        ->and($weaponInstance->fresh()->getUpgradeBonuses())->toBeEmpty();
});

test('UpgradeService backfills a missing threshold bonus on the next upgrade when item is already at +3 or above', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'BackfillTester',
        'level' => 10,
        'gold' => 100000,
    ]);

    $weaponTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowy Topór',
        'type' => 'weapon',
        'sub_type' => 'axe',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => ['attack_min' => 10, 'attack_max' => 20],
    ]);

    // Item stworzony bezpośrednio na +5 (np. przez /give lub legacy dane) - BEZ
    // bonusu z progu +3, mimo że jest już powyżej niego.
    $weaponInstance = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $weaponTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'upgrade_level' => 5,
        'stack_size' => 1,
    ]);

    expect($weaponInstance->getUpgradeBonuses())->toBeEmpty();

    // +5 -> +6: nie przekracza progu 2->3, ale powinno dograć brakujący bonus,
    // bo poziom po ulepszeniu (6) jest >= +3.
    UpgradeRule::create([
        'id' => (string) Str::ulid(),
        'from_level' => 5,
        'to_level' => 6,
        'applies_to' => 'type',
        'applies_value' => 'weapon',
        'cost' => ['gold' => 500, 'materials' => []],
        'success_chance' => 1.0,
        'on_fail' => 'nothing',
    ]);

    $upgradeService = new UpgradeService();
    $result = $upgradeService->upgradeItem($character, $weaponInstance);

    expect($result['success'])->toBeTrue()
        ->and($weaponInstance->fresh()->upgrade_level)->toBe(6)
        ->and($weaponInstance->fresh()->getUpgradeBonuses())->not->toBeEmpty();
});

test('UpgradeService does not reroll an existing threshold bonus on a normal upgrade above +3', function () {
    $user = User::factory()->create();
    $character = Character::create([
        'user_id' => $user->id,
        'name' => 'NoRerollTester',
        'level' => 10,
        'gold' => 100000,
    ]);

    $weaponTemplate = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowa Różdżka',
        'type' => 'weapon',
        'sub_type' => 'wand',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => ['magic_attack_min' => 10, 'magic_attack_max' => 20],
    ]);

    $weaponInstance = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $weaponTemplate->id,
        'owner_character_id' => $character->id,
        'location' => 'inventory',
        'upgrade_level' => 4,
        'stack_size' => 1,
    ]);
    $weaponInstance->setUpgradeBonus('crit_chance', 3);
    $weaponInstance->save();

    UpgradeRule::create([
        'id' => (string) Str::ulid(),
        'from_level' => 4,
        'to_level' => 5,
        'applies_to' => 'type',
        'applies_value' => 'weapon',
        'cost' => ['gold' => 500, 'materials' => []],
        'success_chance' => 1.0,
        'on_fail' => 'nothing',
    ]);

    $upgradeService = new UpgradeService();
    $result = $upgradeService->upgradeItem($character, $weaponInstance);

    expect($result['success'])->toBeTrue()
        ->and($weaponInstance->fresh()->upgrade_level)->toBe(5)
        ->and($weaponInstance->fresh()->getUpgradeBonuses())->toBe(['crit_chance' => 3]);
});

test('ItemInstance getUpgradeBonusStats uses the Faza 5 accelerating curve per level', function () {
    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Topór Kamiennego Golema',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 65,
        'base_stats' => [
            'attack_min' => 400,
            'attack_max' => 3200,
            'str_bonus' => 1200,
        ],
    ]);

    $itemPlus1 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'location' => 'inventory',
        'upgrade_level' => 1,
    ]);

    $itemPlus2 = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'location' => 'inventory',
        'upgrade_level' => 2,
    ]);

    // Faza 5 rebalansu (2026-08-05): +1 daje 4% bazowych statów (ItemInstance::UPGRADE_BONUS_PERCENT_BY_LEVEL)
    expect($itemPlus1->getUpgradeBonusStats())->toBe([
        'attack_min' => 16,
        'attack_max' => 128,
        'str_bonus' => 48,
    ]);

    // +2 daje 8% bazowych statów
    expect($itemPlus2->getUpgradeBonusStats())->toBe([
        'attack_min' => 32,
        'attack_max' => 256,
        'str_bonus' => 96,
    ]);
});

