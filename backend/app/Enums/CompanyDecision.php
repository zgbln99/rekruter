<?php

namespace App\Enums;

/**
 * Decyzja firmy klienta po wysłaniu profilu kandydata.
 */
enum CompanyDecision: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Hired = 'hired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Oczekuje',
            self::Accepted => 'Zaakceptowany',
            self::Rejected => 'Odrzucony',
            self::Hired => 'Zatrudniony',
        };
    }

    /**
     * Mapowanie decyzji firmy na status kandydata w ogłoszeniu.
     */
    public function toApplicationStatus(): ?ApplicationStatus
    {
        return match ($this) {
            self::Accepted => ApplicationStatus::AcceptedByCompany,
            self::Rejected => ApplicationStatus::RejectedByCompany,
            self::Hired => ApplicationStatus::Hired,
            self::Pending => null,
        };
    }
}
