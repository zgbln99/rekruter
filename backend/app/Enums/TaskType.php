<?php

namespace App\Enums;

enum TaskType: string
{
    case FollowUp = 'follow_up';
    case DocumentCollect = 'document_collect';
    case Interview = 'interview';
    case Custom = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::FollowUp => 'Kontakt follow-up',
            self::DocumentCollect => 'Zebranie dokumentów',
            self::Interview => 'Rozmowa',
            self::Custom => 'Inne',
        };
    }
}
