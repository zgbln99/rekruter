<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Support\Ai\OpenAiClient;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

/**
 * Generuje grafikę ogłoszenia (PNG) do social media:
 *
 *  - Tło generuje AI (gpt-image-1) WYŁĄCZNIE jako obraz bez napisów — ale tylko
 *    RAZ. Jest zapisywane w storage (S3) i reużywane. Ponowne wywołanie AI
 *    następuje tylko, gdy jawnie poprosimy o odświeżenie tła ($refreshBackground).
 *  - Tekst oferty nakłada deterministycznie Chromium (Gotenberg) z HTML, więc
 *    polska pisownia jest zawsze poprawna.
 *  - Gotowe grafiki (oraz tło) są zapisywane w S3, aby szybko je pobrać.
 */
class GeneratePosterAction
{
    public function render(JobPosting $offer, string $format = 'feed', bool $refreshBackground = false): string
    {
        $offer->loadMissing('company');
        $tenant = $offer->tenant;

        [$w, $h] = $format === 'reels' ? [1080, 1920] : [1080, 1350];

        [$headline, $subtitle, $locationFromTitle] = $this->splitTitle($offer->title ?? '');
        [$locationLine1, $locationLine2] = $this->buildLocation($offer, $locationFromTitle);

        $html = View::make('pdf.poster', [
            'format' => $format,
            'width' => $w,
            'height' => $h,
            'backgroundImage' => $this->resolveBackground($offer, $refreshBackground),
            'headline' => $headline,
            'subtitle' => $subtitle,
            'locationLine1' => $locationLine1,
            'locationLine2' => $locationLine2,
            'category' => $this->formatCategory($offer),
            'workSystem' => $offer->work_system ?: 'Bez systemu',
            'salary' => $this->formatSalary($offer),
            'salarySuffix' => 'na rękę',
            'headlineFontSize' => $this->valueMainFontSize($headline, $format),
            'agencyName' => $tenant?->agencyName() ?? config('app.name'),
        ])->render();

        $png = GotenbergClient::make()->htmlToImage($html, $w, $h, 'png');

        // Zapisz gotową grafikę w S3 (szybkie pobranie / archiwum).
        try {
            $this->disk()->put($offer->storageFolder().'/poster-'.$format.'.png', $png);
        } catch (\Throwable $e) {
            Log::warning('Nie udało się zapisać plakatu w storage: '.$e->getMessage());
        }

        return $png;
    }

    /** Dysk dokumentów (S3 w produkcji). */
    private function disk(): Filesystem
    {
        return Storage::disk(config('rekruter.documents_disk'));
    }

    /**
     * Zwraca tło jako data-URI. Reużywa zapisanego tła; AI woła tylko gdy brak
     * tła lub wymuszono odświeżenie. Null → szablon użyje zaprojektowanego
     * fallbacku (bez AI).
     */
    private function resolveBackground(JobPosting $offer, bool $refresh): ?string
    {
        $disk = $this->disk();
        $existing = $offer->poster_bg_path;

        // Reużyj istniejącego tła (domyślnie — bez ponownego AI).
        if (! $refresh && $existing && $this->safeExists($disk, $existing)) {
            return $this->toDataUri($disk->get($existing));
        }

        $client = OpenAiClient::fromTenant($offer->tenant);

        // Brak AI: spróbuj reużyć stare tło, inaczej fallback.
        if (! $client) {
            return ($existing && $this->safeExists($disk, $existing))
                ? $this->toDataUri($disk->get($existing))
                : null;
        }

        // Wygeneruj nowe tło i zapisz w S3.
        try {
            $png = $client->image($this->buildBackgroundPrompt(), '1024x1536', 'medium');
            $path = $offer->storageFolder().'/poster-bg.png';
            $disk->put($path, $png);
            $offer->forceFill(['poster_bg_path' => $path])->saveQuietly();

            return $this->toDataUri($png);
        } catch (\Throwable $e) {
            Log::warning('Nie udało się wygenerować tła plakatu przez OpenAI: '.$e->getMessage());

            return ($existing && $this->safeExists($disk, $existing))
                ? $this->toDataUri($disk->get($existing))
                : null;
        }
    }

    private function safeExists(Filesystem $disk, string $path): bool
    {
        try {
            return $disk->exists($path);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function toDataUri(string $png): string
    {
        return 'data:image/png;base64,'.base64_encode($png);
    }

    /**
     * Rozbija tytuł oferty po półpauzie / myślniku na:
     * [headline, subtitle|null, locationFromTitle|null].
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

    /** Lokalizacja: preferuj osobne pola (kraj / region), fallback z tytułu. */
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

    /** Wynagrodzenie jako zakres z półpauzą: „2850–3150 EUR". */
    private function formatSalary(JobPosting $offer): ?string
    {
        $raw = trim((string) $offer->salary_amount);
        if ($raw === '') {
            return null;
        }

        $s = preg_replace('/\s*[-–—]\s*/u', '–', $raw);
        $s = preg_replace('/(?<=\d)\s+(?=\d)/u', '', $s);

        $currency = trim((string) $offer->currency);

        return $currency ? trim($s).' '.$currency : trim($s);
    }

    /** Dobiera rozmiar fontu „Stanowiska" zależnie od długości. */
    private function valueMainFontSize(string $headline, string $format): int
    {
        $len = mb_strlen($headline);

        $steps = $format === 'reels'
            ? [[16, 58], [24, 50], [34, 44], [PHP_INT_MAX, 38]]
            : [[16, 46], [24, 40], [34, 34], [PHP_INT_MAX, 30]];

        foreach ($steps as [$max, $size]) {
            if ($len <= $max) {
                return $size;
            }
        }

        return $format === 'reels' ? 38 : 30;
    }

    /** Prompt dla modelu obrazu — wyłącznie tło, bez jakiegokolwiek tekstu. */
    private function buildBackgroundPrompt(): string
    {
        return <<<PROMPT
        Create a clean vertical background image for a professional truck driver
        recruitment ad (portrait orientation).

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
