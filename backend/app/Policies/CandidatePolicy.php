<?php

namespace App\Policies;

use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    /**
     * Trwałe usunięcie danych (RODO „prawo do bycia zapomnianym")
     * zarezerwowane dla administratora.
     */
    public function forget(User $user, Candidate $candidate): bool
    {
        return $user->isAdmin() && $user->tenant_id === $candidate->tenant_id;
    }
}
