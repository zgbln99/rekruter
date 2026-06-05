<?php

namespace App\Enums;

enum ContactOutcome: string
{
    case Interested = 'interested';
    case NotInterested = 'not_interested';
    case NoAnswer = 'no_answer';
    case Callback = 'callback';
    case WrongNumber = 'wrong_number';
    case HiredElsewhere = 'hired_elsewhere';
    case DocumentsRequested = 'documents_requested';

    public function label(): string
    {
        return match ($this) {
            self::Interested => 'Zainteresowany',
            self::NotInterested => 'Niezainteresowany',
            self::NoAnswer => 'Nie odebrał',
            self::Callback => 'Oddzwonić',
            self::WrongNumber => 'Zły numer',
            self::HiredElsewhere => 'Zatrudniony gdzie indziej',
            self::DocumentsRequested => 'Poproszono o dokumenty',
        };
    }
}
