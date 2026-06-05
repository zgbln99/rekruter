<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\View;

/**
 * Generuje grafikę ogłoszenia (PNG) do publikacji w social media.
 * Format: feed 1080x1350 lub reels 1080x1920.
 */
class GeneratePosterAction
{
    public function render(JobPosting $offer, string $format = 'feed'): string
    {
        $offer->loadMissing('company');
        $tenant = $offer->tenant;
        $settings = $tenant?->settings ?? [];

        [$w, $h] = $format === 'reels' ? [1080, 1920] : [1080, 1350];

        $html = View::make('pdf.poster', [
            'offer' => $offer,
            'company' => $offer->company,
            'width' => $w,
            'height' => $h,
            'agencyName' => $tenant?->agencyName() ?? config('app.name'),
            'agencyPhone' => $settings['agency_phone'] ?? null,
            'agencyEmail' => $settings['agency_email'] ?? null,
            'agencyWebsite' => $settings['agency_website'] ?? null,
        ])->render();

        return GotenbergClient::make()->htmlToImage($html, $w, $h, 'png');
    }
}
