<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobPostings\StoreJobPostingRequest;
use App\Http\Requests\JobPostings\UpdateJobPostingRequest;
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
}
