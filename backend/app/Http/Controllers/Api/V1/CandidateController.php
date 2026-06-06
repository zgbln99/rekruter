<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Candidates\CreateCandidateAction;
use App\Actions\Candidates\MergeCandidatesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Candidates\StoreCandidateRequest;
use App\Http\Requests\Candidates\UpdateCandidateRequest;
use App\Http\Resources\CandidateResource;
use App\Models\Candidate;
use App\Models\ProfileSend;
use App\Support\PhoneNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

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
            'applications' => fn ($q) => $q->with('jobPosting')->latest(),
        ]);

        return new CandidateResource($candidate);
    }

    public function update(UpdateCandidateRequest $request, Candidate $candidate): CandidateResource
    {
        $candidate->fill($request->validated())->save();

        return new CandidateResource($candidate->refresh());
    }

    /**
     * Łączy duplikat (source) z bieżącym kandydatem (target).
     * Powiązania przechodzą na target, źródło jest usuwane.
     */
    public function merge(Request $request, Candidate $candidate, MergeCandidatesAction $action): CandidateResource
    {
        $data = $request->validate([
            'source_id' => ['required', 'uuid', \Illuminate\Validation\Rule::notIn([$candidate->id]), 'exists:candidates,id'],
        ]);

        $source = Candidate::findOrFail($data['source_id']);
        $merged = $action->execute($candidate, $source);

        return new CandidateResource($merged->load('applications.jobPosting'));
    }

    public function destroy(Candidate $candidate): JsonResponse
    {
        // Usuń pliki z storage (dokumenty + wygenerowane PDF).
        foreach ($candidate->documents()->withTrashed()->get() as $document) {
            Storage::disk($document->disk)->delete($document->path);
        }
        foreach (ProfileSend::where('candidate_id', $candidate->id)->whereNotNull('pdf_path')->pluck('pdf_path') as $pdf) {
            Storage::disk(config('rekruter.documents_disk'))->delete($pdf);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($candidate) {
            // Usuń wszystkie powiązania, żeby nie zostawały „sieroty"
            // (zadania na pulpicie, przyjazdy/raty w kalendarzu, pipeline).
            $placementIds = $candidate->placements()->pluck('id');
            \App\Models\PlacementInstallment::whereIn('placement_id', $placementIds)->delete();
            $candidate->placements()->forceDelete();

            $candidate->tasks()->forceDelete();
            $candidate->contactLogs()->delete();
            $candidate->applications()->delete();
            $candidate->profileSends()->delete();
            $candidate->documents()->forceDelete();

            $candidate->forceFill(['profile_photo_id' => null])->save();
            $candidate->delete();
        });

        return response()->json(['message' => 'Kandydat usunięty wraz z dokumentami i powiązaniami.']);
    }
}

