<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Support\Ai\OpenAiClient;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;

/**
 * Generuje grafikę ogłoszenia (PNG) do social media w dwóch etapach:
 *
 *  Etap A — OpenAI (gpt-image-1) generuje WYŁĄCZNIE tło/ilustrację (bez żadnych
 *           napisów). Dzięki temu nie ma literówek w polskich tekstach.
 *  Etap B — backend deterministycznie nakłada tekst oferty (Chromium/Gotenberg
 *           renderuje HTML z tłem w data-URI), gwarantując poprawną pisownię,
 *           dokładny rozmiar (1080x1350 / 1080x1920) i kontrolowany layout.
 *
 * Gdy tło z AI jest niedostępne (brak klucza API / błąd / brak sieci), grafika
 * i tak powstaje — na czystym, jasnym tle (bez ilustracji z AI).
 */
class GeneratePosterAction
{
    public function render(JobPosting $offer, string $format = 'feed'): string
    {
        $offer->loadMissing('company');
        $tenant = $offer->tenant;
        $settings = $tenant?->settings ?? [];

        [$w, $h] = $format === 'reels' ? [1080, 1920] : [1080, 1350];

        // Etap A: tło z AI (opcjonalne — bez napisów).
        $backgroundUri = $this->generateBackground($offer, $format);

        // Etap B: deterministyczny render tekstu na tle.
        $html = View::make('pdf.poster', [
            'offer' => $offer,
            'company' => $offer->company,
            'width' => $w,
            'height' => $h,
            'backgroundUri' => $backgroundUri,
            'agencyName' => $tenant?->agencyName() ?? config('app.name'),
            'agencyPhone' => $settings['agency_phone'] ?? null,
            'agencyEmail' => $settings['agency_email'] ?? null,
            'agencyWebsite' => $settings['agency_website'] ?? null,
        ])->render();

        return GotenbergClient::make()->htmlToImage($html, $w, $h, 'png');
    }

    /**
     * Etap A — wygeneruj samo tło (PNG) i zwróć je jako data-URI gotowe do
     * osadzenia w HTML. Zwraca null, gdy AI jest niedostępne.
     */
    private function generateBackground(JobPosting $offer, string $format): ?string
    {
        $client = OpenAiClient::fromTenant($offer->tenant);

        if (! $client) {
            return null;
        }

        try {
            $png = $client->image($this->buildBackgroundPrompt($offer, $format), '1024x1536', 'medium');

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable $e) {
            // Tło to dodatek — nie blokujemy generowania grafiki przy błędzie AI.
            Log::warning('Nie udało się wygenerować tła plakatu przez OpenAI: '.$e->getMessage());

            return null;
        }
    }

    /**
     * Prompt dla modelu obrazu — wyłącznie tło, bez jakiegokolwiek tekstu.
     */
    private function buildBackgroundPrompt(JobPosting $offer, string $format): string
    {
        $ratio = $format === 'reels'
            ? 'vertical 9:16 social media story/reels background'
            : 'vertical 4:5 social media feed background';

        return <<<PROMPT
        Create a {$ratio} for a professional truck driver recruitment ad.

        Scene:
        - modern white semi-truck / tractor trailer
        - truck positioned on the right side or lower-right corner
        - clean white and light-gray corporate background
        - subtle red accent shapes, no text
        - large empty bright space on the left and upper area for later text overlay
        - realistic, premium, polished recruitment agency style
        - sharp, high-quality, balanced composition

        STRICT RULES:
        - no text
        - no letters
        - no numbers
        - no logos
        - no brand names
        - no license plate text
        - no watermark
        - no signs or banners
        - no fake typography

        Avoid:
        - text
        - letters
        - fake words
        - misspellings
        - logos
        - phone numbers
        - QR codes
        - readable license plates
        - brand names
        - watermark
        - UI elements
        - crowded layout
        - dark background
        - low contrast
        PROMPT;
    }
}
