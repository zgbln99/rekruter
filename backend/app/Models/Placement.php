<?php

namespace App\Models;

use App\Enums\ArrivalStatus;
use App\Support\Audit\RecordsActivity;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Skierowanie kierowcy do pracy: konkretny kandydat → ogłoszenie/firma,
 * na wyznaczony termin przyjazdu. Podstawa kalendarza i rozliczeń ratalnych.
 */
class Placement extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'job_posting_id',
        'company_id',
        'created_by',
        'arrival_at',
        'arrival_status',
        'arrival_confirmed_at',
        'arrival_confirmed_by',
        'total_amount',
        'currency',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'arrival_at' => 'datetime',
            'arrival_status' => ArrivalStatus::class,
            'arrival_confirmed_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function jobPosting(): BelongsTo
    {
        return $this->belongsTo(JobPosting::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function installments(): HasMany
    {
        return $this->hasMany(PlacementInstallment::class)->orderBy('sequence');
    }
}
