<?php

namespace App\Support\Tenancy;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait dla modeli należących do tenanta.
 *
 * - Dokłada globalny TenantScope (filtrowanie po tenant_id).
 * - Automatycznie ustawia tenant_id przy tworzeniu rekordu na podstawie
 *   bieżącego kontekstu tenanta.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            if (empty($model->tenant_id) && app()->bound('currentTenantId')) {
                $model->tenant_id = app('currentTenantId');
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
