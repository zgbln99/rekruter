<?php

namespace App\Enums;

/**
 * Źródło pozyskania kandydata.
 */
enum CandidateSource: string
{
    case Facebook = 'facebook';
    case Olx = 'olx';
    case Jooble = 'jooble';
    case FacebookGroup = 'facebook_group';
    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Referral = 'referral';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Facebook => 'Facebook',
            self::Olx => 'OLX',
            self::Jooble => 'Jooble',
            self::FacebookGroup => 'Grupa Facebook',
            self::WhatsApp => 'WhatsApp',
            self::Phone => 'Telefon',
            self::Referral => 'Polecenie',
            self::Other => 'Inne',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }
}
