<?php

namespace App\Actions\Profiles;

use App\Models\JobPosting;
use App\Support\Ai\OpenAiClient;
use Illuminate\Validation\ValidationException;

/**
 * Generuje grafikę ogłoszenia (PNG) do social media — przez OpenAI (gpt-image-1).
 * Format: feed (pionowy 4:5) lub reels (pionowy 9:16) → obraz pionowy 1024x1536.
 */
class GeneratePosterAction
{
    public function render(JobPosting $offer, string $format = 'feed'): string
    {
        $offer->loadMissing('company');
        $tenant = $offer->tenant;

        $client = OpenAiClient::fromTenant($tenant);

        if (! $client) {
            throw ValidationException::withMessages([
                'openai' => ['Skonfiguruj klucz API OpenAI w Ustawieniach, aby generować grafiki.'],
            ]);
        }

        $prompt = $this->buildPrompt($offer, $tenant?->agencyName() ?? config('app.name'), $format);

        return $client->image($prompt, '1024x1536', 'medium');
    }

    private function buildPrompt(JobPosting $offer, string $agencyName, string $format): string
    {
        $location = trim(implode(', ', array_filter([
            $offer->country,
            $offer->region_base,
        ])));

        $salary = trim(($offer->salary_amount ?? '').' '.($offer->currency ?? ''));
        $categories = is_array($offer->required_categories)
            ? implode(', ', $offer->required_categories)
            : '';

        // Krótkie, konkretne napisy do umieszczenia na grafice (po polsku).
        $lines = collect([
            'NAGŁÓWEK' => 'PRACA DLA KIEROWCY',
            'STANOWISKO' => $offer->title,
            'LOKALIZACJA' => $location ?: null,
            'KATEGORIE' => $categories ?: null,
            'SYSTEM PRACY' => $offer->work_system,
            'WYNAGRODZENIE' => $salary ?: null,
            'CTA' => 'APLIKUJ TERAZ',
            'AGENCJA' => $agencyName,
        ])->filter()->map(fn ($v, $k) => "{$k}: {$v}")->implode("\n");

        $ratio = $format === 'reels'
            ? 'format pionowy pod Instagram/Facebook Reels (proporcje ok. 9:16)'
            : 'format pionowy pod post na Instagramie/Facebooku (proporcje ok. 4:5)';

        return <<<PROMPT
        Zaprojektuj profesjonalny, nowoczesny i czysty plakat rekrutacyjny dla kierowców
        zawodowych (transport ciężarowy), {$ratio}.

        Styl: elegancki, minimalistyczny, korporacyjny. Jasne, czyste tło (biel/jasny szary)
        z mocnymi akcentami w kolorze czerwonym (#dc2626) i ciemnym grafitowym (#0f172a).
        Subtelna, realistyczna fotografia lub czysta ilustracja nowoczesnej ciężarówki
        (naczepa/ciągnik siodłowy) jako element tła lub boczny, dobrze skomponowana,
        nie zasłaniająca tekstu. Dużo światła, wyraźna hierarchia, profesjonalna typografia
        bezszeryfowa. Ma wyglądać jak praca zawodowego grafika, NIE jak amatorski baner.

        Umieść na grafice DOKŁADNIE poniższe napisy po polsku (poprawna polska pisownia,
        bez literówek, czytelne, dobrze rozmieszczone — nagłówek u góry, wynagrodzenie
        wyróżnione dużą czcionką, „APLIKUJ TERAZ" jako wyraźny przycisk/baner na dole,
        nazwa agencji dyskretnie przy dole):

        {$lines}

        Nie dodawaj żadnych innych, zmyślonych napisów, numerów telefonu ani logotypów.
        Tekst ma być ostry i w pełni czytelny.
        PROMPT;
    }
}
