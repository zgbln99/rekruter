<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use Illuminate\Http\JsonResponse;

class PipelineController extends Controller
{
    /**
     * Tablica kanban dla ogłoszenia: kolumny = statusy kandydata w ogłoszeniu.
     */
    public function board(JobPosting $jobPosting): JsonResponse
    {
        $applications = $jobPosting->applications()
            ->with('candidate')
            ->orderBy('position')
            ->get()
            ->groupBy(fn ($app) => $app->status->value);

        $columns = collect(ApplicationStatus::cases())->map(fn (ApplicationStatus $status) => [
            'id' => $status->value,
            'name' => $status->label(),
            'color' => $status->color(),
            'applications' => ApplicationResource::collection(
                $applications->get($status->value, collect())
            ),
        ]);

        return response()->json([
            'job_posting' => new JobPostingResource($jobPosting->load('company')),
            'stages' => $columns,
        ]);
    }
}
