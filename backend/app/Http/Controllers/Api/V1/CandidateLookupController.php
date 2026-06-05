<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Deduplikacja „w locie" podczas rozmowy telefonicznej (KPI 60s).
 * Frontend odpytuje ten endpoint przy wpisywaniu numeru.
 */
class CandidateLookupController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => ['required', 'string', 'max:32'],
        ]);

        $normalized = PhoneNumber::normalize($request->string('phone')->toString());

        $candidate = $normalized
            ? Candidate::where('phone_normalized', $normalized)->first()
            : null;

        return response()->json([
            'normalized' => $normalized,
            'exists' => $candidate !== null,
            'candidate' => $candidate ? new CandidateResource($candidate) : null,
        ]);
    }
}
