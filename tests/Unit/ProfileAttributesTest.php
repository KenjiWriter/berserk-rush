<?php

use App\Infrastructure\Persistence\Character;
use Illuminate\Support\Facades\Cache;

uses(Tests\TestCase::class);

test('character clearStatsCache clears total attributes cache', function () {
    $character = new Character();
    $character->id = 999;
    $cacheKey = $character->getCacheKey('total_attributes');

    Cache::put($cacheKey, ['str' => 10, 'int' => 10, 'vit' => 10, 'agi' => 10], 3600);
    expect(Cache::has($cacheKey))->toBeTrue();

    $character->clearStatsCache();
    expect(Cache::has($cacheKey))->toBeFalse();
});
