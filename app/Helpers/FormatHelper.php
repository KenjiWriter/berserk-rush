<?php

namespace App\Helpers;

class FormatHelper
{
    /**
     * Formats large numbers into short strings like 1k, 25.7k, 4.5M, 1.2B.
     */
    public static function short(int|float|string|null $number): string
    {
        if ($number === null || !is_numeric($number)) {
            return '0';
        }

        $num = (float)$number;
        $abs = abs($num);
        $sign = $num < 0 ? '-' : '';

        if ($abs >= 1_000_000_000) {
            $val = round($abs / 1_000_000_000, 1);
            return $sign . (floor($val) == $val ? (int)$val : $val) . 'B';
        }

        if ($abs >= 1_000_000) {
            $val = round($abs / 1_000_000, 1);
            return $sign . (floor($val) == $val ? (int)$val : $val) . 'M';
        }

        if ($abs >= 1_000) {
            $val = round($abs / 1_000, 1);
            return $sign . (floor($val) == $val ? (int)$val : $val) . 'k';
        }

        return $sign . (int)$abs;
    }
}
