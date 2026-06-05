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
}
