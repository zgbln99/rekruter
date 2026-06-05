<?php

namespace App\Enums;

enum CandidateStatus: string
{
    case New = 'new';
    case Active = 'active';
    case Placed = 'placed';
    case Unavailable = 'unavailable';
    case Blacklisted = 'blacklisted';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nowy',
            self::Active => 'Aktywny',
            self::Placed => 'Zatrudniony',
            self::Unavailable => 'Niedostępny',
            self::Blacklisted => 'Czarna lista',
            self::Archived => 'Zarchiwizowany',
        };
    }
}
