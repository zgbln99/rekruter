<?php

namespace App\Support\Ai;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimalny klient OpenAI (ChatGPT) — Chat Completions.
 * Klucz API i model pochodzą z ustawień organizacji.
 */
class OpenAiClient
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {}

    public static function fromTenant(?Tenant $tenant): ?self
    {
        $key = $tenant?->openaiApiKey();

        return $key ? new self($key, $tenant->openaiModel()) : null;
    }

    public function chat(string $system, string $user, float $temperature = 0.7): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->model,
                'temperature' => $temperature,
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $user],
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Błąd OpenAI (HTTP '.$response->status().'): '.$response->json('error.message', 'nieznany')
            );
        }

        return trim((string) $response->json('choices.0.message.content', ''));
    }

    /**
     * Generuje obraz (PNG) przez OpenAI Images API (model gpt-image-1).
     * Zwraca surowe bajty PNG.
     *
     * @param  string  $size  np. '1024x1536' (pionowy), '1024x1024', '1536x1024'
     * @param  string  $quality  'low' | 'medium' | 'high' | 'auto'
     */
    public function image(string $prompt, string $size = '1024x1536', string $quality = 'medium'): string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(180)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => 'gpt-image-1',
                'prompt' => $prompt,
                'size' => $size,
                'quality' => $quality,
                'n' => 1,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException(
                'Błąd OpenAI (HTTP '.$response->status().'): '.$response->json('error.message', 'nieznany')
            );
        }

        $b64 = $response->json('data.0.b64_json');

        if (! $b64) {
            throw new RuntimeException('OpenAI nie zwrócił obrazu.');
        }

        return base64_decode($b64);
    }
}
