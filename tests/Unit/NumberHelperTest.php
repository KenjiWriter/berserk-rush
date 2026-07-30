<?php

use App\Support\NumberHelper;

test('formats numbers below 1000 without suffix', function () {
    expect(NumberHelper::formatShort(0))->toBe('0');
    expect(NumberHelper::formatShort(123))->toBe('123');
    expect(NumberHelper::formatShort(999))->toBe('999');
});

test('formats numbers >= 1000 with k, M, B suffixes', function () {
    expect(NumberHelper::formatShort(1000))->toBe('1k');
    expect(NumberHelper::formatShort(1200))->toBe('1.2k');
    expect(NumberHelper::formatShort(1500))->toBe('1.5k');
    expect(NumberHelper::formatShort(2000))->toBe('2k');
    expect(NumberHelper::formatShort(10000))->toBe('10k');
    expect(NumberHelper::formatShort(12500))->toBe('12.5k');
    expect(NumberHelper::formatShort(1000000))->toBe('1M');
    expect(NumberHelper::formatShort(2500000))->toBe('2.5M');
    expect(NumberHelper::formatShort(1000000000))->toBe('1B');
});

test('handles negative values correctly', function () {
    expect(NumberHelper::formatShort(-1500))->toBe('-1.5k');
    expect(NumberHelper::formatShort(-2500000))->toBe('-2.5M');
});
