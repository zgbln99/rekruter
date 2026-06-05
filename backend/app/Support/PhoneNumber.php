<?php

namespace App\Support;

/**
 * Normalizacja numerów telefonu do postaci zbliżonej do E.164,
 * używanej jako klucz deduplikacji kandydatów (DESIGN.md, ryzyko R2).
 *
 * Agencja jest PL-centryczna (kierowcy krajowi i międzynarodowi), więc
 * domyślnym krajem jest Polska (+48). Numery z prefiksem międzynarodowym
 * (+, 00) są zachowywane.
 *
 * Uwaga: to deterministyczny normalizator MVP. W przyszłości można podmienić
 * na libphonenumber bez zmiany kontraktu (ta sama sygnatura metody).
 */
class PhoneNumber
{
    public const DEFAULT_COUNTRY_CODE = '48';

    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $hasPlus = str_starts_with($trimmed, '+');

        // Zostaw tylko cyfry.
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';
        if ($digits === '') {
            return null;
        }

        if ($hasPlus) {
            return '+'.$digits;
        }

        // Prefiks międzynarodowy 00 -> +
        if (str_starts_with($digits, '00')) {
            return '+'.substr($digits, 2);
        }

        // Krajowy numer z zerem wiodącym (trunk) -> +48 bez zera
        if (str_starts_with($digits, '0')) {
            return '+'.self::DEFAULT_COUNTRY_CODE.substr($digits, 1);
        }

        // Już z kodem kraju PL
        if (str_starts_with($digits, self::DEFAULT_COUNTRY_CODE) && strlen($digits) > 9) {
            return '+'.$digits;
        }

        // Goły numer krajowy (np. 9 cyfr) -> doklej +48
        return '+'.self::DEFAULT_COUNTRY_CODE.$digits;
    }
}
