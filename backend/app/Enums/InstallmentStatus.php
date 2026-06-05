<?php

namespace App\Enums;

/**
 * Status raty rozliczenia (faktury za skierowanego kierowcę).
 */
enum InstallmentStatus: string
{
    case Pending = 'pending';
    case Invoiced = 'invoiced';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Do wystawienia',
            self::Invoiced => 'Wystawiona',
            self::Paid => 'Opłacona',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#f59e0b',
            self::Invoiced => '#6366f1',
            self::Paid => '#059669',
        };
    }
}
