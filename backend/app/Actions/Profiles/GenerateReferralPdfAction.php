<?php

namespace App\Actions\Profiles;

use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\View;

/**
 * Generuje dokument „Skierowanie do pracy" dla kierowcy (z ogłoszenia):
 * dane pracodawcy, osoby kontaktowe, warunki pracy.
 *
 * Gdy podany jest konkretny kierowca i termin przyjazdu (ze skierowania —
 * Placement), dokument jest personalizowany: pokazuje nazwisko kierowcy
 * oraz wpisaną ręcznie datę i godzinę przyjazdu.
 *
 * `$overrides` pozwala uzupełnić/poprawić pola tuż przed wygenerowaniem PDF
 * (z modala) — bez zapisu w bazie. Klucz `candidate_name` ustawia nazwisko
 * kierowcy, pozostałe klucze nadpisują pola oferty użyte w dokumencie.
 */
class GenerateReferralPdfAction
{
    /** Pola oferty, które wolno nadpisać z modala (whitelista). */
    private const OVERRIDABLE = [
        'title', 'country', 'region_base', 'work_system', 'vehicle_type',
        'trailer_type', 'routes_info', 'cargo', 'points_per_day', 'loading_info',
        'daily_km', 'accommodation', 'contract_type', 'salary_amount', 'currency',
        'required_language', 'onsite_contact', 'public_description',
    ];

    public function render(
        JobPosting $offer,
        User $recruiter,
        ?Candidate $candidate = null,
        ?string $arrivalOverride = null,
        array $overrides = []
    ): string {
        $offer->loadMissing('company');

        // Nadpisania z modala (tylko w pamięci — bez zapisu w bazie).
        foreach (self::OVERRIDABLE as $field) {
            if (array_key_exists($field, $overrides) && $overrides[$field] !== null && $overrides[$field] !== '') {
                $offer->setAttribute($field, $overrides[$field]);
            }
        }

        $candidateName = $candidate?->fullName() ?: ($overrides['candidate_name'] ?? null);

        $html = View::make('pdf.referral', [
            'offer' => $offer,
            'company' => $offer->company,
            'agencyName' => $offer->tenant?->agencyName() ?? config('app.name'),
            'candidateName' => $candidateName,
            'arrivalOverride' => $arrivalOverride,
            'recruiterName' => $recruiter->name,
            'recruiterPhone' => $recruiter->phone,
            'recruiterEmail' => $recruiter->email,
            'generatedAt' => now()->format('d.m.Y'),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }
}
