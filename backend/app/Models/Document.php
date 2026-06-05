<?php

namespace App\Models;

use App\Enums\DocumentType;
use App\Support\Audit\RecordsActivity;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    use BelongsToTenant, HasFactory, HasUuids, RecordsActivity, SoftDeletes;

    protected $fillable = [
        'candidate_id',
        'type',
        'disk',
        'path',
        'original_name',
        'mime',
        'size',
        'is_profile_photo',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'is_profile_photo' => 'boolean',
            'size' => 'integer',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Czasowy, podpisany URL do pobrania (dokumenty nigdy nie są publiczne).
     */
    public function temporaryUrl(int $minutes = 5): string
    {
        return Storage::disk($this->disk)->temporaryUrl(
            $this->path,
            now()->addMinutes($minutes)
        );
    }
}
