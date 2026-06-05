<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ustala bieżącego tenanta na podstawie zalogowanego użytkownika
 * i wiąże go w kontenerze pod kluczem `currentTenantId`, dzięki czemu
 * TenantScope automatycznie filtruje wszystkie zapytania.
 *
 * Uruchamiany PO auth:sanctum.
 */
class IdentifyTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            app()->instance('currentTenantId', $user->tenant_id);
        }

        return $next($request);
    }
}
