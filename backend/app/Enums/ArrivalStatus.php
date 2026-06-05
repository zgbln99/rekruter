<?php

namespace App\Enums;

/**
 * Status przyjazdu kierowcy do pracy (weryfikacja w kalendarzu).
 */
enum ArrivalStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case NoShow = 'no_show';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje',
            self::Confirmed => 'Dotarł',
            self::NoShow => 'Nie dotarł',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#64748b',
            self::Confirmed => '#059669',
            self::NoShow => '#ef4444',
        };
    }
}
