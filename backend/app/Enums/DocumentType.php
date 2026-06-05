<?php

namespace App\Enums;

enum DocumentType: string
{
    case Cv = 'cv';
    case IdCard = 'id_card';
    case Passport = 'passport';
    case DrivingLicense = 'driving_license';
    case DriverCard = 'driver_card';
    case Adr = 'adr';
    case Code95 = 'code_95';
    case Photo = 'photo';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cv => 'CV',
            self::IdCard => 'Dowód osobisty',
            self::Passport => 'Paszport',
            self::DrivingLicense => 'Prawo jazdy',
            self::DriverCard => 'Karta kierowcy',
            self::Adr => 'ADR',
            self::Code95 => 'Kod 95',
            self::Photo => 'Zdjęcie',
            self::Other => 'Inne',
        };
    }

    /**
     * Czy dokument zawiera dane szczególnie wrażliwe (RODO) —
     * wpływa na dłuższe logowanie dostępu i krótszy TTL linków.
     */
    public function isSensitive(): bool
    {
        return in_array($this, [self::IdCard, self::Passport, self::DriverCard], true);
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $t) => $t->value, self::cases());
    }
}
