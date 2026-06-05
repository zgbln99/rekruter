<?php

namespace App\Models;

use App\Enums\CandidateStatus;
use App\Support\Audit\RecordsActivity;
use App\Support\PhoneNumber;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Candidate extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'phone',
        'phone_normalized',
        'email',
        'city',
        'country',
        'status',
        'license_categories',
        'has_adr',
        'adr_expiry',
        'has_code_95',
        'code_95_expiry',
        'driver_card_expiry',
        'profile_photo_id',
        'source',
        'consent_rodo_at',
        'internal_notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => CandidateStatus::class,
            'license_categories' => 'array',
            'has_adr' => 'boolean',
            'has_code_95' => 'boolean',
            'adr_expiry' => 'date',
            'code_95_expiry' => 'date',
            'driver_card_expiry' => 'date',
            'consent_rodo_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Utrzymuj phone_normalized w synchronizacji z phone.
        static::saving(function (Candidate $candidate) {
            if ($candidate->isDirty('phone')) {
                $candidate->phone_normalized = PhoneNumber::normalize($candidate->phone);
            }
        });
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.($this->last_name ?? ''));
    }

    public function contactLogs(): HasMany
    {
        return $this->hasMany(ContactLog::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function profilePhoto(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'profile_photo_id');
    }
}
