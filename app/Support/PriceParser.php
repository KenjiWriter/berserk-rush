<?php

namespace App\Support;

class PriceParser
{
    /**
     * Parse input price string with shortcuts (k, kk, kkk, m, b) into integer.
     * Examples:
     *  - "2k" or "2K" -> 2,000
     *  - "2.5k" or "2,5k" -> 2,500
     *  - "2kk" or "2KK" or "2m" or "2M" -> 2,000,000
     *  - "1.5kk" or "1,5kk" -> 1,500,000
     *  - "1kkk" or "1b" -> 1,000,000,000
     *  - "200000" or "200 000" -> 200,000
     *
     * @param string|int|float|null $input
     * @param int $max
     * @return int
     */
    public static function parse(string|int|float|null $input, int $max = 999_999_999): int
    {
        if ($input === null || $input === '') {
            return 0;
        }

        if (is_int($input)) {
            return max(0, min($input, $max));
        }

        if (is_float($input)) {
            return max(0, min((int) round($input), $max));
        }

        $str = trim((string) $input);
        if ($str === '') {
            return 0;
        }

        // Remove spaces, underscores
        $str = str_replace([' ', '_'], '', $str);
        // Normalize commas to dots for decimal numbers (e.g. 1,5kk -> 1.5kk)
        $str = str_replace(',', '.', $str);

        // Match number part and suffix (kkk, kk, k, m, b) case-insensitive
        if (preg_match('/^([0-9]+(?:\.[0-9]+)?)\s*([a-zA-Z]+)?$/i', $str, $matches)) {
            $val = (float) $matches[1];
            $suffix = strtolower($matches[2] ?? '');

            $multiplier = match ($suffix) {
                'kkk', 'b' => 1_000_000_000,
                'kk', 'm' => 1_000_000,
                'k' => 1_000,
                '' => 1,
                default => 1,
            };

            $result = (int) round($val * $multiplier);
            return max(0, min($result, $max));
        }

        // Fallback: strip non-numeric characters
        $clean = preg_replace('/[^0-9]/', '', $str);
        if ($clean === '') {
            return 0;
        }

        return max(0, min((int) $clean, $max));
    }

    /**
     * Formats integer price into readable string with spaces (e.g. 2 000 000).
     */
    public static function format(int $amount): string
    {
        return number_format($amount, 0, '', ' ');
    }
}
