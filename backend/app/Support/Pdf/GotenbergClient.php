<?php

namespace App\Support\Pdf;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimalny klient Gotenberg (Chromium) do konwersji HTML -> PDF.
 *
 * Wysyła samodzielny dokument HTML (z inline CSS oraz obrazami w data-URI),
 * dzięki czemu nie wymaga dostępu Gotenberg do zewnętrznych zasobów.
 */
class GotenbergClient
{
    public function __construct(
        private readonly string $baseUrl,
    ) {}

    public static function make(): self
    {
        return new self(rtrim((string) config('services.gotenberg.url'), '/'));
    }

    /**
     * Konwertuje HTML do PDF i zwraca zawartość binarną pliku.
     */
    public function htmlToPdf(string $html): string
    {
        $response = $this->request()
            ->attach('files', $html, 'index.html')
            ->post($this->baseUrl.'/forms/chromium/convert/html', [
                'paperWidth' => '8.27',   // A4
                'paperHeight' => '11.69',
                'marginTop' => '0.3',
                'marginBottom' => '0.3',
                'marginLeft' => '0.22',
                'marginRight' => '0.22',
                'printBackground' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gotenberg nie wygenerował PDF (HTTP '.$response->status().').'
            );
        }

        return $response->body();
    }

    /**
     * Konwertuje HTML do obrazu (PNG/JPEG) — grafika na social media.
     */
    public function htmlToImage(string $html, int $width = 1080, int $height = 1350, string $format = 'png'): string
    {
        $response = $this->request()
            ->attach('files', $html, 'index.html')
            ->post($this->baseUrl.'/forms/chromium/screenshot/html', [
                'format' => $format,
                'width' => (string) $width,
                'height' => (string) $height,
                'clip' => 'true',
                'optimizeForSpeed' => 'true',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Gotenberg nie wygenerował obrazu (HTTP '.$response->status().').'
            );
        }

        return $response->body();
    }

    private function request(): PendingRequest
    {
        return Http::timeout(45);
    }
}
