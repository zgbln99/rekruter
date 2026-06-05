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

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}
