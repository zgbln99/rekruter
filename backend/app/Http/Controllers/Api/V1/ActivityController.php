<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityResource;
use App\Models\Activity;
use App\Models\Candidate;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ActivityController extends Controller
{
    /**
     * Audit log dla konkretnego kandydata (kto, kiedy, co).
     */
    public function forCandidate(Candidate $candidate): AnonymousResourceCollection
    {
        $activities = Activity::query()
            ->with('user')
            ->where('subject_type', $candidate->getMorphClass())
            ->where('subject_id', $candidate->id)
            ->latest('created_at')
            ->limit(100)
            ->get();

        return ActivityResource::collection($activities);
    }
}
