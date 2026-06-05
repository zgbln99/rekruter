<?php

namespace App\Enums;

enum JobPostingStatus: string
{
    case Open = 'open';
    case Paused = 'paused';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Otwarte',
            self::Paused => 'Wstrzymane',
            self::Closed => 'Zamknięte',
        };
    }
}
