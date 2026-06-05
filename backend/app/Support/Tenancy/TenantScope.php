<?php

namespace App\Support\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Global scope ograniczający zapytania do bieżącego tenanta.
 *
 * Bieżący tenant jest ustalany na podstawie zalogowanego użytkownika
 * (patrz IdentifyTenant middleware) i przechowywany w kontenerze pod
 * kluczem `currentTenantId`. Gdy brak kontekstu tenanta (np. komendy
 * konsolowe, seeding), scope nie filtruje — odpowiedzialność spoczywa
 * wtedy na kodzie wywołującym.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app()->bound('currentTenantId')
            ? app('currentTenantId')
            : null;

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }
    }
}
