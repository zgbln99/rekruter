<?php

namespace App\Policies;

use App\Models\JobPosting;
use App\Models\User;

class JobPostingPolicy
{
    // Usuwanie ogłoszeń tylko przez administratora.
    public function delete(User $user, JobPosting $jobPosting): bool
    {
        return $user->isAdmin() && $user->tenant_id === $jobPosting->tenant_id;
    }
}
