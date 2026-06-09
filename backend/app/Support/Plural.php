<?php

namespace App\Support;

/**
 * Polska odmiana rzeczowników przez liczebnik (1 / 2-4 / 5+).
 */
class Plural
{
    public static function pl(int $n, string $one, string $few, string $many): string
    {
        $n = abs($n);
        if ($n === 1) {
            return $one;
        }

        $mod10 = $n % 10;
        $mod100 = $n % 100;

        if ($mod10 >= 2 && $mod10 <= 4 && ! ($mod100 >= 12 && $mod100 <= 14)) {
            return $few;
        }

        return $many;
    }
}
