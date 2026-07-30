<?php

use App\Support\PriceParser;

test('parses plain numbers correctly', function () {
    expect(PriceParser::parse('100'))->toBe(100);
    expect(PriceParser::parse('2000000'))->toBe(2000000);
    expect(PriceParser::parse('2 000 000'))->toBe(2000000);
    expect(PriceParser::parse(500))->toBe(500);
    expect(PriceParser::parse(null))->toBe(0);
});

test('parses k suffix shorthand correctly', function () {
    expect(PriceParser::parse('2k'))->toBe(2000);
    expect(PriceParser::parse('2K'))->toBe(2000);
    expect(PriceParser::parse('1.5k'))->toBe(1500);
    expect(PriceParser::parse('1,5k'))->toBe(1500);
    expect(PriceParser::parse('500k'))->toBe(500000);
});

test('parses kk and m suffix shorthand correctly', function () {
    expect(PriceParser::parse('2kk'))->toBe(2000000);
    expect(PriceParser::parse('2KK'))->toBe(2000000);
    expect(PriceParser::parse('1.5kk'))->toBe(1500000);
    expect(PriceParser::parse('1,5kk'))->toBe(1500000);
    expect(PriceParser::parse('2m'))->toBe(2000000);
    expect(PriceParser::parse('2.5m'))->toBe(2500000);
});

test('parses kkk and b suffix shorthand correctly', function () {
    expect(PriceParser::parse('1b'))->toBe(999999999);
    expect(PriceParser::parse('1kkk'))->toBe(999999999);
});

test('respects max capping', function () {
    expect(PriceParser::parse('9999999999'))->toBe(999999999);
});
