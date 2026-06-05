<?php

namespace App\Models;

use App\Enums\CompanyDecision;
use App\Enums\ProfileSendStatus;
use App\Support\Audit\RecordsActivity;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProfileSend extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, RecordsActivity;

    protected $fillable = [
        'candidate_id',
        'company_id',
        'job_posting_id',
        'pdf_path',
        'recipient_email',
        'status',
        'decision',
        'decision_at',
        'sent_by',
        'sent_at',
        'viewed_at',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileSendStatus::class,
            'decision' => CompanyDecision::class,
            'decision_at' => 'datetime',
            'sent_at' => 'datetime',
            'viewed_at' => 'datetime',
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
}
