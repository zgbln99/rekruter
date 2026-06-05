<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Models\User;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\View;

/**
 * Generuje dokument „Skierowanie do pracy" dla kierowcy (z ogłoszenia):
 * dane pracodawcy, osoby kontaktowe, warunki pracy.
 */
class GenerateReferralPdfAction
{
    public function render(JobPosting $offer, User $recruiter): string
    {
        $offer->loadMissing('company');

        $html = View::make('pdf.referral', [
            'offer' => $offer,
            'company' => $offer->company,
            'agencyName' => $offer->tenant?->agencyName() ?? config('app.name'),
            'recruiterName' => $recruiter->name,
            'recruiterPhone' => $recruiter->phone,
            'recruiterEmail' => $recruiter->email,
            'generatedAt' => now()->format('d.m.Y'),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }
}
