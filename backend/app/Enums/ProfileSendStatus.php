<?php

namespace App\Enums;

enum ProfileSendStatus: string
{
    case Queued = 'queued';
    case Sent = 'sent';
    case Failed = 'failed';
    case Viewed = 'viewed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => 'W kolejce',
            self::Sent => 'Wysłano',
            self::Failed => 'Błąd',
            self::Viewed => 'Otwarto',
        };
    }
}
