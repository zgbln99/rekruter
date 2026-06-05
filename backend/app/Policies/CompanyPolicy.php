<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    // Usuwanie firm-klientów tylko przez administratora.
    public function delete(User $user, Company $company): bool
    {
        return $user->isAdmin() && $user->tenant_id === $company->tenant_id;
    }
}
