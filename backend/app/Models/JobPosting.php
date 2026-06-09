<?php

namespace App\Models;

use App\Enums\JobPostingStatus;
use App\Support\Audit\RecordsActivity;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPosting extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'company_id',
        'title',
        'driver_type',
        'trailer_type',
        'vehicle_type',
        'cargo',
        'routes_info',
        'accommodation',
        'onsite_contact',
        'arrival_info',
        'contract_type',
        'points_per_day',
        'loading_info',
        'daily_km',
        'pdf_url',
        'poster_bg_path',
        'country',
        'region_base',
        'work_system',
        'salary_amount',
        'currency',
        'start_date',
        'required_language',
        'required_experience',
        'description',
        'public_description',
        'recruiter_notes',
        'call_script',
        'faq',
        'required_categories',
        'requirements',
        'location',
        'salary_range',
        'status',
        'is_public',
        'published_at',
        'cover_image_url',
        'external_ref',
    ];

    protected function casts(): array
    {
        return [
            'required_categories' => 'array',
            'requirements' => 'array',
            'call_script' => 'array',
            'faq' => 'array',
            'start_date' => 'date',
            'published_at' => 'datetime',
            'is_public' => 'boolean',
            'status' => JobPostingStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Czytelny folder ogłoszenia w storage (np. „oferty/kierowca-c-e-a1f3c2d1").
     */
    public function storageFolder(): string
    {
        $slug = \Illuminate\Support\Str::slug($this->title ?? '') ?: 'oferta';

        return 'oferty/'.$slug.'-'.substr((string) $this->id, 0, 8);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /** Ogłoszenia widoczne publicznie: ręcznie opublikowane i otwarte. */
    public function scopePublished(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('is_public', true)
            ->where('status', JobPostingStatus::Open->value);
    }

    /** Czy ogłoszenie może być pokazane na publicznej stronie kariery. */
    public function isPublished(): bool
    {
        return (bool) $this->is_public && $this->status === JobPostingStatus::Open;
    }

    /**
     * Zdjęcie okładkowe oferty: najpierw ustawione ręcznie (np. z Unsplash),
     * inaczej deterministyczne z puli europejskich ciężarówek (config).
     */
    public function coverImage(): string
    {
        if (! empty($this->cover_image_url)) {
            return $this->cover_image_url;
        }

        $imgs = config('rekruter.stock_images', []);
        if (empty($imgs)) {
            return '';
        }

        return $imgs[abs(crc32((string) $this->id)) % count($imgs)];
    }

    /** Slug z tytułu do ładnego, „SEO" adresu. */
    public function publicSlug(): string
    {
        return \Illuminate\Support\Str::slug($this->title ?? '') ?: 'oferta-pracy';
    }

    /** Ścieżka publiczna ogłoszenia (z kluczowym slugiem + UUID). */
    public function publicPath(): string
    {
        return '/kariera/'.$this->publicSlug().'/'.$this->id;
    }

    /**
     * Pełny publiczny URL ogłoszenia — bazuje na adresie strony kariery
     * (domena główna), bo panel API żyje pod inną subdomeną.
     */
    public function publicUrl(): string
    {
        $base = config('rekruter.careers_url');
        if ($base) {
            return $base.$this->publicPath();
        }

        // Fallback: bieżący host bez prefiksu „panel." — panel żyje pod subdomeną,
        // a publiczne oferty na domenie głównej (np. panel.edgejobs.pl → edgejobs.pl).
        $url = url($this->publicPath());

        return preg_replace('#^(https?://)panel\.#i', '$1', $url) ?: $url;
    }
}
