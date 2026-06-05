<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Support\Ai\OpenAiClient;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Generuje grafikę ogłoszenia (PNG) do social media w dwóch etapach:
 *
 *  Etap A — OpenAI (gpt-image-1) generuje WYŁĄCZNIE tło/ilustrację (bez napisów).
 *  Etap B — backend deterministycznie nakłada tekst oferty (Chromium/Gotenberg
 *           renderuje HTML z tłem w data-URI). Wszystkie litery pochodzą z HTML,
 *           więc polska pisownia jest zawsze poprawna.
 *
 * Gdy tło z AI jest niedostępne (brak klucza / błąd / brak sieci), grafika i tak
 * powstaje na zaprojektowanym jasnym tle (z delikatnym znakiem wodnym ciężarówki).
 */
class GeneratePosterAction
{
    public function render(JobPosting $offer, string $format = 'feed'): string
    {
        $offer->loadMissing('company');
        $tenant = $offer->tenant;

        [$w, $h] = $format === 'reels' ? [1080, 1920] : [1080, 1350];

        // Rozbicie tytułu: „Kierowca C+E – Dystrybucja … – Niemcy (Lipsk)".
        [$headline, $subtitle, $locationFromTitle] = $this->splitTitle($offer->title ?? '');
        [$locationLine1, $locationLine2] = $this->buildLocation($offer, $locationFromTitle);

        $html = View::make('pdf.poster', [
            'format' => $format,
            'width' => $w,
            'height' => $h,
            'backgroundImage' => $this->generateBackground($offer, $format),
            'headline' => $headline,
            'subtitle' => $subtitle,
            'locationLine1' => $locationLine1,
            'locationLine2' => $locationLine2,
            'category' => $this->formatCategory($offer),
            'workSystem' => $offer->work_system ?: 'Bez systemu',
            'salary' => $this->formatSalary($offer),
            'headlineFontSize' => $this->valueMainFontSize($headline, $format),
            'agencyName' => $tenant?->agencyName() ?? config('app.name'),
        ])->render();

        return GotenbergClient::make()->htmlToImage($html, $w, $h, 'png');
    }

    /**
     * Rozbija tytuł oferty po półpauzie / myślniku na:
     * [headline, subtitle|null, locationFromTitle|null].
     * Dzielimy po „–"/„—" (z opcjonalnymi spacjami) lub „-" otoczonym spacjami,
     * by nie rozbijać np. „Mercedes-Benz".
     */
    private function splitTitle(string $title): array
    {
        $parts = preg_split('/\s*[–—]\s*|\s+-\s+/u', trim($title)) ?: [];
        $parts = array_values(array_filter(array_map('trim', $parts), fn ($p) => $p !== ''));

        return [
            $parts[0] ?? $title,
            $parts[1] ?? null,
            $parts[2] ?? null,
        ];
    }

    /**
     * Lokalizacja: preferuj osobne pola (kraj / region). Dopiero gdy ich brak —
     * użyj fragmentu wyłuskanego z tytułu.
     */
    private function buildLocation(JobPosting $offer, ?string $fromTitle): array
    {
        $country = trim((string) $offer->country);
        $region = trim((string) $offer->region_base);

        if ($country && $region) {
            return [$country, $region];
        }
        if ($country || $region) {
            return [$country ?: $region, null];
        }

        return [$fromTitle, null];
    }

    private function formatCategory(JobPosting $offer): ?string
    {
        $cats = $offer->required_categories ?? [];

        return is_array($cats) && $cats ? implode(', ', $cats) : null;
    }

    /**
     * Wynagrodzenie jako czytelny zakres z półpauzą: „2850–3150 EUR".
     * Usuwa spacje-separatory tysięcy i ujednolica myślnik.
     */
    private function formatSalary(JobPosting $offer): ?string
    {
        $raw = trim((string) $offer->salary_amount);
        if ($raw === '') {
            return null;
        }

        // Myślnik/półpauza (z opcjonalnymi spacjami) → półpauza bez spacji.
        $s = preg_replace('/\s*[-–—]\s*/u', '–', $raw);
        // Spacje używane jako separatory tysięcy (między cyframi) → usuń.
        $s = preg_replace('/(?<=\d)\s+(?=\d)/u', '', $s);

        $currency = trim((string) $offer->currency);

        return $currency ? trim($s).' '.$currency : trim($s);
    }

    /**
     * Dobiera rozmiar fontu dla wartości „Stanowisko" zależnie od długości,
     * aby długie nazwy nie wychodziły poza canvas.
     */
    private function valueMainFontSize(string $headline, string $format): int
    {
        $len = mb_strlen($headline);

        $steps = $format === 'reels'
            ? [[16, 76], [24, 64], [34, 54], [PHP_INT_MAX, 46]]
            : [[16, 62], [24, 54], [34, 46], [PHP_INT_MAX, 40]];

        foreach ($steps as [$max, $size]) {
            if ($len <= $max) {
                return $size;
            }
        }

        return $format === 'reels' ? 46 : 40;
    }

    /** Etap A — wygeneruj samo tło (PNG) jako data-URI. Null, gdy AI niedostępne. */
    private function generateBackground(JobPosting $offer, string $format): ?string
    {
        $client = OpenAiClient::fromTenant($offer->tenant);

        if (! $client) {
            return null;
        }

        try {
            $png = $client->image($this->buildBackgroundPrompt($format), '1024x1536', 'medium');

            return 'data:image/png;base64,'.base64_encode($png);
        } catch (\Throwable $e) {
            Log::warning('Nie udało się wygenerować tła plakatu przez OpenAI: '.$e->getMessage());

            return null;
        }
    }

    /** Prompt dla modelu obrazu — wyłącznie tło, bez jakiegokolwiek tekstu. */
    private function buildBackgroundPrompt(string $format): string
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
