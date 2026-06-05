<?php

namespace App\Models;

use App\Enums\InstallmentStatus;
use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Rata rozliczenia za skierowanego kierowcę (faktura agencji).
 * Płatność dzielona na 2 raty (faktury co 2 tygodnie).
 */
class PlacementInstallment extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'placement_id',
        'sequence',
        'due_date',
        'amount',
        'status',
        'invoiced_at',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'status' => InstallmentStatus::class,
            'invoiced_at' => 'date',
            'paid_at' => 'date',
        ];
    }

    public function placement(): BelongsTo
    {
        return $this->belongsTo(Placement::class);
    }
}
