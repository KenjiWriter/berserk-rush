<?php

use App\Application\Items\ItemStatRoller;
use App\Infrastructure\Persistence\ItemInstance;
use App\Infrastructure\Persistence\ItemTemplate;
use App\Infrastructure\RNG\DefaultRandomProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(Tests\TestCase::class, RefreshDatabase::class);

test('ItemStatRoller rolls every ranged stat within its [min, max] bounds', function () {
    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowy Miecz Przedzialowy',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => [
            'attack_min' => [10, 20],
            'attack_max' => [30, 60],
            'crit_chance' => [5, 5],
        ],
    ]);

    $roller = new ItemStatRoller(new DefaultRandomProvider());

    for ($i = 0; $i < 50; $i++) {
        $result = $roller->roll($template);

        expect($result['rolled_stats']['attack_min'])->toBeGreaterThanOrEqual(10)
            ->toBeLessThanOrEqual(20);
        expect($result['rolled_stats']['attack_max'])->toBeGreaterThanOrEqual(30)
            ->toBeLessThanOrEqual(60);
        expect($result['rolled_stats']['crit_chance'])->toBe(5);
        expect($result['quality'])->toBeGreaterThanOrEqual(0.0)->toBeLessThanOrEqual(1.0);
        expect($result['rarity'])->toBeIn(['common', 'uncommon', 'rare', 'epic', 'legendary']);
    }
});

test('ItemStatRoller derives legendary rarity when every stat rolls its maximum', function () {
    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowy Miecz Bez Wariancji',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => [
            'attack_min' => [15, 15],
            'attack_max' => [15, 15],
        ],
    ]);

    $roller = new ItemStatRoller(new DefaultRandomProvider());
    $result = $roller->roll($template);

    expect($result['rolled_stats'])->toBe(['attack_min' => 15, 'attack_max' => 15])
        ->and($result['quality'])->toBe(1.0)
        ->and($result['rarity'])->toBe('legendary');
});

test('ItemStatRoller rarityFromQuality maps quality thresholds to the right tier', function () {
    $roller = new ItemStatRoller(new DefaultRandomProvider());

    expect($roller->rarityFromQuality(0.99))->toBe('legendary')
        ->and($roller->rarityFromQuality(0.85))->toBe('epic')
        ->and($roller->rarityFromQuality(0.65))->toBe('rare')
        ->and($roller->rarityFromQuality(0.40))->toBe('uncommon')
        ->and($roller->rarityFromQuality(0.10))->toBe('common');
});

test('ItemInstance getResolvedBaseStats resolves ranged stats to the rolled value with midpoint fallback', function () {
    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowa Zbroja Przedzialowa',
        'type' => 'armor',
        'slot' => 'chest',
        'level_requirement' => 1,
        'base_stats' => [
            'defense' => [10, 20],
            'hp_bonus' => [40, 60],
        ],
    ]);

    $rolledItem = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'location' => 'inventory',
        'rolled_stats' => ['defense' => 18, 'hp_bonus' => 60],
    ]);

    expect($rolledItem->getResolvedBaseStats())->toBe(['defense' => 18, 'hp_bonus' => 60])
        ->and($rolledItem->isStatMaxed('defense'))->toBeFalse()
        ->and($rolledItem->isStatMaxed('hp_bonus'))->toBeTrue();

    $legacyItem = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'location' => 'inventory',
    ]);

    // No rolled_stats saved (legacy/incomplete data) -> falls back to range midpoint.
    expect($legacyItem->getResolvedBaseStats())->toBe(['defense' => 15, 'hp_bonus' => 50]);
});

test('ItemInstance getUpgradeBonusStats computes 10% per level off the resolved (rolled) value', function () {
    $template = ItemTemplate::create([
        'id' => (string) Str::ulid(),
        'name' => 'Testowy Topor Przedzialowy',
        'type' => 'weapon',
        'slot' => 'main_hand',
        'level_requirement' => 1,
        'base_stats' => [
            'attack_min' => [100, 200],
            'attack_max' => [300, 500],
        ],
    ]);

    $item = ItemInstance::create([
        'id' => (string) Str::ulid(),
        'template_id' => $template->id,
        'location' => 'inventory',
        'upgrade_level' => 2,
        'rolled_stats' => ['attack_min' => 100, 'attack_max' => 400],
    ]);

    expect($item->getUpgradeBonusStats())->toBe([
        'attack_min' => 20,
        'attack_max' => 80,
    ]);
});
