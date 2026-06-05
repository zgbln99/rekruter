<?php

namespace App\Models;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactLog extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'candidate_id',
        'user_id',
        'channel',
        'outcome',
        'note',
        'contacted_at',
        'next_contact_at',
        'task_id',
    ];

    protected function casts(): array
    {
        return [
            'channel' => ContactChannel::class,
            'outcome' => ContactOutcome::class,
            'contacted_at' => 'datetime',
            'next_contact_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
