<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Candidates\CreateCandidateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidates\StoreCandidateRequest;
use App\Http\Requests\Candidates\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CandidateController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Candidate::query()->latest();

        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        if ($term = trim($request->string('q')->toString())) {
            $like = '%'.$term.'%';
            $normalized = PhoneNumber::normalize($term);
            $query->where(function ($q) use ($like, $normalized) {
                $q->where('first_name', 'ilike', $like)
                    ->orWhere('last_name', 'ilike', $like)
                    ->orWhere('city', 'ilike', $like)
                    ->orWhere('phone_normalized', 'ilike', '%'.$normalized.'%');
            });
        }

        return CandidateResource::collection(
            $query->paginate($request->integer('per_page', 25))
        );
    }

    public function store(StoreCandidateRequest $request, CreateCandidateAction $action): JsonResponse
    {
        // Deduplikacja: jeśli numer już istnieje, zwróć istniejącego kandydata (409).
        $normalized = PhoneNumber::normalize($request->string('phone')->toString());
        $existing = Candidate::where('phone_normalized', $normalized)->first();

        if ($existing !== null) {
            return response()->json([
                'message' => 'Kandydat z tym numerem już istnieje.',
                'candidate' => new CandidateResource($existing),
                'duplicate' => true,
            ], 409);
        }

        $candidate = $action->execute($request->validated(), $request->user());

        return (new CandidateResource($candidate->load('contactLogs', 'tasks')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Candidate $candidate): CandidateResource
    {
        $candidate->load([
            'contactLogs' => fn ($q) => $q->with('user')->latest('contacted_at'),
            'tasks' => fn ($q) => $q->latest('due_at'),
        ]);

        return new CandidateResource($candidate);
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): CandidateResource
    {
        $candidate->fill($request->validated())->save();

        return new CandidateResource($candidate->refresh());
    }

    public function destroy(Candidate $candidate): JsonResponse
    {
        $candidate->delete();

        return response()->json(['message' => 'Kandydat zarchiwizowany.']);
    }
}
