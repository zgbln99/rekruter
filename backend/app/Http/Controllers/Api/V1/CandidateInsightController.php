<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Candidates\CandidateCompletenessAction;
use App\Actions\Candidates\CandidateTimelineAction;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;

class CandidateInsightController extends Controller
{
    /**
     * Checklista kompletności profilu + braki do wysłania do firmy.
     */
    public function completeness(Candidate $candidate, CandidateCompletenessAction $action): JsonResponse
    {
        return response()->json($action->execute($candidate));
    }

    /**
     * Chronologiczny timeline kandydata (z audit logu).
     */
    public function timeline(Candidate $candidate, CandidateTimelineAction $action): JsonResponse
    {
        return response()->json($action->execute($candidate));
    }
}
