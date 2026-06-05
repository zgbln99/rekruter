<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Matching\MatchCandidateToOfferAction;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;

class MatchController extends Controller
{
    /**
     * Dopasowanie kandydata do ogłoszenia (pasuje / częściowo / nie pasuje + braki).
     */
    public function __invoke(
        Candidate $candidate,
        JobPosting $jobOffer,
        MatchCandidateToOfferAction $action
    ): JsonResponse {
        return response()->json($action->execute($candidate, $jobOffer));
    }
}
