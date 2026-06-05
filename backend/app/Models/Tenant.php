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

    /** Klucz API OpenAI (ChatGPT) — z ustawień organizacji. */
    public function openaiApiKey(): ?string
    {
        return $this->settings['openai_api_key'] ?? null;
    }

    public function openaiModel(): string
    {
        return $this->settings['openai_model'] ?? 'gpt-4o-mini';
    }
}
