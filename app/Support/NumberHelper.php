<?php

namespace App\Support;

class NumberHelper
{
    /**
     * Formatuje liczby na czytelną skróconą postać w statystykach (np. 1000 => 1k, 1500 => 1.5k, 1000000 => 1M).
     * Liczby < 1000 zwracane są bez modyfikacji.
     */
    public static function formatShort(int|float|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $num = (float) $value;
        $sign = $num < 0 ? '-' : '';
        $abs = abs($num);

        if ($abs < 1000) {
            return $sign . (fmod($abs, 1.0) === 0.0 ? (string) (int) $abs : rtrim(rtrim(number_format($abs, 2, '.', ''), '0'), '.'));
        }

        if ($abs >= 1_000_000_000) {
            $unit = 'B';
            $short = round($abs / 1_000_000_000, 1);
        } elseif ($abs >= 1_000_000) {
            $unit = 'M';
            $short = round($abs / 1_000_000, 1);
        } else {
            $unit = 'k';
            $short = round($abs / 1000, 1);
        }

        $formatted = ((float) (int) $short === $short) ? (string) (int) $short : rtrim(rtrim(number_format($short, 1, '.', ''), '0'), '.');

        return $sign . $formatted . $unit;
    }
}
