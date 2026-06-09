<?php

namespace App\Actions\Offers;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Zwraca adres zdjęcia europejskiej ciężarówki.
 *
 * Preferuje Unsplash API (losowe zdjęcie wg zapytania o europejskie marki).
 * Bez klucza API albo przy błędzie — losuje z konfiguracyjnej puli domyślnej.
 */
class FetchTruckPhotoAction
{
    public function __invoke(?Tenant $tenant = null): string
    {
        $key = $this->resolveKey($tenant);
        $queries = config('rekruter.truck_queries', ['european truck']);
        $query = $queries[array_rand($queries)];

        if ($key) {
            $url = $this->fromUnsplash($key, $query);
            if ($url !== null) {
                return $url;
            }
        }

        return $this->fromPool();
    }

    /**
     * Klucz Unsplash: najpierw z ustawień organizacji (panel),
     * potem z globalnej konfiguracji (env). Tenant z argumentu lub z
     * zalogowanego użytkownika (akcja wywoływana w kontekście żądania API).
     */
    private function resolveKey(?Tenant $tenant): ?string
    {
        $tenant ??= Auth::user()?->tenant;

        if ($tenant instanceof Tenant) {
            return $tenant->unsplashKey();
        }

        $config = trim((string) config('rekruter.unsplash_key'));

        return $config !== '' ? $config : null;
    }

    private function fromUnsplash(string $key, string $query): ?string
    {
        try {
            $res = Http::timeout(12)->get('https://api.unsplash.com/photos/random', [
                'query' => $query,
                'orientation' => 'landscape',
                'content_filter' => 'high',
                'client_id' => $key,
            ]);

            if (! $res->successful()) {
                return null;
            }

            $raw = $res->json('urls.raw') ?? $res->json('urls.regular');
            if (! $raw) {
                return null;
            }

            // Dokładamy parametry kadrowania/jakości (Imgix Unsplasha).
            $sep = str_contains($raw, '?') ? '&' : '?';

            return $raw.$sep.'auto=format&fit=crop&w=1200&q=72';
        } catch (\Throwable $e) {
            Log::warning('Unsplash: nie pobrano zdjęcia ciężarówki: '.$e->getMessage());

            return null;
        }
    }

    private function fromPool(): string
    {
        $pool = config('rekruter.stock_images', []);
        if (empty($pool)) {
            return '';
        }

        return $pool[array_rand($pool)];
    }
}
