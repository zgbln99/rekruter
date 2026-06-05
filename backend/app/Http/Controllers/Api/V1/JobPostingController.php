<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Candidates\CreateCandidateFromOfferAction;
use App\Actions\Profiles\GeneratePosterAction;
use App\Actions\Profiles\GenerateReferralPdfAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\JobPostings\CreateCandidateFromOfferRequest;
use App\Http\Requests\JobPostings\StoreJobPostingRequest;
use App\Http\Requests\JobPostings\UpdateJobPostingRequest;
use App\Http\Resources\CandidateResource;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class JobPostingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = JobPosting::query()->with('company')->withCount('applications')->latest();

        if ($companyId = $request->string('company_id')->toString()) {
            $query->where('company_id', $companyId);
        }
        if ($status = $request->string('status')->toString()) {
            $query->where('status', $status);
        }

        return JobPostingResource::collection($query->paginate($request->integer('per_page', 25)));
    }

    public function store(StoreJobPostingRequest $request): JsonResponse
    {
        $posting = JobPosting::create($request->validated());

        return (new JobPostingResource($posting->refresh()->load('company')))->response()->setStatusCode(201);
    }

    public function show(JobPosting $jobPosting): JobPostingResource
    {
        return new JobPostingResource($jobPosting->load('company')->loadCount('applications'));
    }

    public function update(UpdateJobPostingRequest $request, JobPosting $jobPosting): JobPostingResource
    {
        $jobPosting->update($request->validated());

        return new JobPostingResource($jobPosting->refresh()->load('company'));
    }

    public function destroy(JobPosting $jobPosting): JsonResponse
    {
        $this->authorize('delete', $jobPosting);

        $jobPosting->delete();

        return response()->json(['message' => 'Ogłoszenie usunięte.']);
    }

    /**
     * Szybkie utworzenie kandydata z ogłoszenia (auto-przypisanie do oferty/firmy).
     */
    public function createCandidate(
        CreateCandidateFromOfferRequest $request,
        JobPosting $jobPosting,
        CreateCandidateFromOfferAction $action
    ): JsonResponse {
        $result = $action->execute($jobPosting, $request->validated(), $request->user());

        $payload = (new CandidateResource($result['candidate']->load('applications')))
            ->resolve($request);
        $payload['duplicate'] = $result['duplicate'];

        return response()->json($payload, $result['duplicate'] ? 200 : 201);
    }

    /**
     * Dokument „Skierowanie do pracy" (PDF) dla kierowcy.
     */
    public function referralPdf(
        JobPosting $jobPosting,
        GenerateReferralPdfAction $action,
        Request $request
    ): \Illuminate\Http\Response {
        $pdf = $action->render($jobPosting, $request->user());

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="skierowanie.pdf"',
        ]);
    }

    /**
     * Grafika ogłoszenia (PNG) do social media — feed (1080×1350) lub reels (1080×1920).
     */
    public function poster(
        JobPosting $jobPosting,
        GeneratePosterAction $action,
        Request $request
    ): \Illuminate\Http\Response {
        $format = $request->string('format')->toString() === 'reels' ? 'reels' : 'feed';
        $png = $action->render($jobPosting, $format);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="oferta.png"',
        ]);
    }
}
