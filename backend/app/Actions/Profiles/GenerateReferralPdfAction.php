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
 */
class GenerateReferralPdfAction
{
    public function render(
        JobPosting $offer,
        User $recruiter,
        ?Candidate $candidate = null,
        ?string $arrivalOverride = null
    ): string {
        $offer->loadMissing('company');

        $html = View::make('pdf.referral', [
            'offer' => $offer,
            'company' => $offer->company,
            'agencyName' => $offer->tenant?->agencyName() ?? config('app.name'),
            'candidateName' => $candidate?->fullName(),
            'arrivalOverride' => $arrivalOverride,
            'recruiterName' => $recruiter->name,
            'recruiterPhone' => $recruiter->phone,
            'recruiterEmail' => $recruiter->email,
            'generatedAt' => now()->format('d.m.Y'),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }
}
