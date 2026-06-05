<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Ai\GenerateOfferDescriptionAction;
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
     * Przyjmuje opcjonalne dane z modala (uzupełnienie/poprawa pól + termin
     * przyjazdu + nazwisko kierowcy) — nadpisania działają tylko na ten PDF.
     */
    public function referralPdf(
        JobPosting $jobPosting,
        GenerateReferralPdfAction $action,
        Request $request
    ): \Illuminate\Http\Response {
        $data = $request->validate([
            'candidate_name' => ['nullable', 'string', 'max:120'],
            'arrival_at' => ['nullable', 'date'],
            'title' => ['nullable', 'string', 'max:160'],
            'country' => ['nullable', 'string', 'max:120'],
            'region_base' => ['nullable', 'string', 'max:160'],
            'work_system' => ['nullable', 'string', 'max:120'],
            'vehicle_type' => ['nullable', 'string', 'max:160'],
            'trailer_type' => ['nullable', 'string', 'max:120'],
            'routes_info' => ['nullable', 'string', 'max:2000'],
            'cargo' => ['nullable', 'string', 'max:500'],
            'points_per_day' => ['nullable', 'string', 'max:120'],
            'loading_info' => ['nullable', 'string', 'max:500'],
            'daily_km' => ['nullable', 'string', 'max:120'],
            'accommodation' => ['nullable', 'string', 'max:2000'],
            'contract_type' => ['nullable', 'string', 'max:160'],
            'salary_amount' => ['nullable', 'string', 'max:120'],
            'currency' => ['nullable', 'string', 'max:8'],
            'required_language' => ['nullable', 'string', 'max:160'],
            'onsite_contact' => ['nullable', 'string', 'max:2000'],
            'public_description' => ['nullable', 'string', 'max:4000'],
        ]);

        $arrival = ! empty($data['arrival_at'])
            ? \Illuminate\Support\Carbon::parse($data['arrival_at'])->format('d.m.Y H:i')
            : null;

        $pdf = $action->render(
            $jobPosting,
            $request->user(),
            null,
            $arrival,
            $data,
        );

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
        // Tło reużywamy; AI generuje ponownie tylko przy „Odśwież tło" (refresh=1).
        $refresh = $request->boolean('refresh');
        $png = $action->render($jobPosting, $format, $refresh);

        return response($png, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="oferta.png"',
        ]);
    }

    /**
     * AI (ChatGPT): generuje gotowy opis ogłoszenia na podstawie danych z formularza.
     */
    public function aiDescription(Request $request, GenerateOfferDescriptionAction $action): JsonResponse
    {
        $description = $action->execute($request->user()->tenant, $request->all());

        return response()->json(['description' => $description]);
    }
}
