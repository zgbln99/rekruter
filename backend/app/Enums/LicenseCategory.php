<?php

namespace App\Enums;

/**
 * Kategorie prawa jazdy istotne dla kierowców zawodowych
 * oraz uprawnienia dodatkowe (ADR, Kod 95).
 */
enum LicenseCategory: string
{
    case B = 'B';
    case C = 'C';
    case C1 = 'C1';
    case CE = 'C+E';
    case D = 'D';
    case D1 = 'D1';
    case DE = 'D+E';

    public function label(): string
    {
        return $this->value;
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
