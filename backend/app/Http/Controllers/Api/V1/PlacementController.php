<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Placements\CreatePlacementAction;
use App\Actions\Profiles\GenerateReferralPdfAction;
use App\Enums\ArrivalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Placements\StorePlacementRequest;
use App\Http\Resources\PlacementResource;
use App\Models\Candidate;
use App\Models\Placement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class PlacementController extends Controller
{
    /** Lista skierowań danego kierowcy. */
    public function index(Candidate $candidate): AnonymousResourceCollection
    {
        $placements = $candidate->placements()
            ->with(['installments', 'jobPosting.company'])
            ->latest('arrival_at')
            ->get();

        return PlacementResource::collection($placements);
    }

    /** Utwórz skierowanie (+ harmonogram dwóch rat). */
    public function store(
        StorePlacementRequest $request,
        Candidate $candidate,
        CreatePlacementAction $action
    ): JsonResponse {
        $placement = $action->execute($candidate, $request->validated(), $request->user());

        return (new PlacementResource($placement))->response()->setStatusCode(201);
    }

    /** PDF skierowania — z datą przyjazdu wpisaną przy tworzeniu skierowania. */
    public function referralPdf(Placement $placement, GenerateReferralPdfAction $action, Request $request): Response
    {
        $placement->loadMissing(['jobPosting.company', 'candidate']);

        $arrival = $placement->arrival_at?->format('d.m.Y H:i');

        $pdf = $action->render(
            $placement->jobPosting,
            $request->user(),
            $placement->candidate,
            $arrival,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="skierowanie.pdf"',
        ]);
    }

    /** Oznacz przyjazd: dotarł / nie dotarł / oczekuje. */
    public function updateArrival(Request $request, Placement $placement): PlacementResource
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(ArrivalStatus::class)],
        ]);

        $status = ArrivalStatus::from($validated['status']);
        $placement->arrival_status = $status;

        if ($status === ArrivalStatus::Pending) {
            $placement->arrival_confirmed_at = null;
            $placement->arrival_confirmed_by = null;
        } else {
            $placement->arrival_confirmed_at = Carbon::now();
            $placement->arrival_confirmed_by = $request->user()->id;
        }
        $placement->save();

        return new PlacementResource($placement->load(['installments', 'jobPosting.company']));
    }

    public function destroy(Placement $placement): JsonResponse
    {
        $placement->delete();

        return response()->json(null, 204);
    }
}
