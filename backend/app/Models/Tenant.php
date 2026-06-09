<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'settings' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Nazwa agencji używana w całej aplikacji i dokumentach (PDF).
     * Konfigurowalna w ustawieniach; fallback do nazwy tenanta / nazwy aplikacji.
     */
    public function agencyName(): string
    {
        return $this->settings['agency_name']
            ?? $this->name
            ?? config('app.name', 'Rekruter');
    }

    /** Telefon kontaktowy agencji (na grafiki / materiały) — z ustawień. */
    public function agencyPhone(): ?string
    {
        $phone = trim((string) ($this->settings['agency_phone'] ?? ''));

        return $phone !== '' ? $phone : null;
    }

    /** Edytowalny tekst strony kariery (z ustawień lub domyślny z configu). */
    public function careersText(string $key): string
    {
        $custom = $this->settings['careers_texts'][$key] ?? null;
        if (is_string($custom) && trim($custom) !== '') {
            return $custom;
        }

        return (string) (config('rekruter.careers_texts.'.$key.'.default') ?? '');
    }

    /** Zdjęcie hero publicznej strony kariery (ustawione w panelu lub domyślne). */
    public function careersHeroImage(): string
    {
        $url = trim((string) ($this->settings['careers_hero_image'] ?? ''));

        return $url !== '' ? $url : (string) config('rekruter.hero_image');
    }

    /** Klucz API OpenAI (ChatGPT) — z ustawień organizacji. */
    public function openaiApiKey(): ?string
    {
        return $this->settings['openai_api_key'] ?? null;
    }

    /**
     * Klucz dostępu Unsplash (Access Key) — z ustawień organizacji.
     * Fallback do globalnej konfiguracji (env). Pusty → null (pula domyślna).
     */
    public function unsplashKey(): ?string
    {
        $key = trim((string) ($this->settings['unsplash_key'] ?? ''));
        if ($key !== '') {
            return $key;
        }

        $config = trim((string) config('rekruter.unsplash_key'));

        return $config !== '' ? $config : null;
    }

    public function openaiModel(): string
    {
        return $this->settings['openai_model'] ?? 'gpt-4o-mini';
    }

    /** Stała kwota rozliczenia za skierowanego kierowcę (ustalana z góry przez admina). */
    public function placementFee(): ?float
    {
        $fee = $this->settings['placement_fee'] ?? null;

        return ($fee === null || $fee === '') ? null : (float) $fee;
    }

    public function placementCurrency(): string
    {
        return $this->settings['placement_currency'] ?? 'EUR';
    }

    /**
     * Branding (logo / ikona / favicon). Każdy wpis: ['path' => ..., 'mime' => ...].
     *
     * @return array<string, mixed>
     */
    public function branding(): array
    {
        return $this->settings['branding'] ?? [];
    }

    /**
     * Szablony wiadomości (WhatsApp/SMS). Placeholdery: {imie} {nazwisko}
     * {telefon} {agencja}. Zwraca skonfigurowane lub domyślne.
     *
     * @return array<int, array{name: string, body: string}>
     */
    public function messageTemplates(): array
    {
        $tpl = $this->settings['message_templates'] ?? null;

        if (is_array($tpl) && $tpl) {
            return array_values(array_filter($tpl, fn ($t) => ! empty($t['body'])));
        }

        return [
            ['name' => 'Pierwszy kontakt', 'body' => 'Dzień dobry {imie}, z tej strony {agencja}. Mam ciekawą ofertę pracy dla kierowcy — czy ma Pan/Pani chwilę, żeby porozmawiać?'],
            ['name' => 'Szczegóły oferty', 'body' => '{imie}, zgodnie z rozmową przesyłam szczegóły oferty. Proszę o wiadomość, czy jest Pan/Pani zainteresowany/a.'],
            ['name' => 'Przypomnienie', 'body' => 'Dzień dobry {imie}, czy udało się zapoznać z ofertą? Chętnie odpowiem na pytania. Pozdrawiam, {agencja}.'],
        ];
    }
}
