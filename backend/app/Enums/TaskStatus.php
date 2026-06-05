<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Open = 'open';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Otwarte',
            self::Done => 'Zrobione',
            self::Cancelled => 'Anulowane',
        };
    }
}
