<?php

namespace App\Enums;

/**
 * Status kandydata w ramach konkretnego ogłoszenia (pivot applications).
 */
enum ApplicationStatus: string
{
    case New = 'new';
    case Interested = 'interested';
    case MissingData = 'missing_data';
    case ReadyForPdf = 'ready_for_pdf';
    case SentToCompany = 'sent_to_company';
    case AcceptedByCompany = 'accepted_by_company';
    case RejectedByCompany = 'rejected_by_company';
    case Hired = 'hired';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Nowy',
            self::Interested => 'Zainteresowany',
            self::MissingData => 'Brakuje danych',
            self::ReadyForPdf => 'Gotowy do PDF',
            self::SentToCompany => 'Wysłany do firmy',
            self::AcceptedByCompany => 'Zaakceptowany przez firmę',
            self::RejectedByCompany => 'Odrzucony przez firmę',
            self::Hired => 'Zatrudniony',
            self::Failed => 'Nieudany',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::New => '#64748b',
            self::Interested => '#0ea5e9',
            self::MissingData => '#f59e0b',
            self::ReadyForPdf => '#8b5cf6',
            self::SentToCompany => '#6366f1',
            self::AcceptedByCompany => '#10b981',
            self::RejectedByCompany => '#ef4444',
            self::Hired => '#059669',
            self::Failed => '#9ca3af',
        };
    }
}
