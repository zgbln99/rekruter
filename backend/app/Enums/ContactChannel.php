<?php

namespace App\Enums;

enum ContactChannel: string
{
    case Phone = 'phone';
    case WhatsApp = 'whatsapp';
    case Sms = 'sms';
    case Email = 'email';

    public function label(): string
    {
        return match ($this) {
            self::Phone => 'Telefon',
            self::WhatsApp => 'WhatsApp',
            self::Sms => 'SMS',
            self::Email => 'E-mail',
        };
    }
}
