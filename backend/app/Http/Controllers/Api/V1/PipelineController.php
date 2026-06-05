<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Http\Resources\JobPostingResource;
use App\Models\JobPosting;
use App\Models\PipelineStage;
use Illuminate\Http\JsonResponse;

class PipelineController extends Controller
{
    /**
     * Tablica kanban dla ogłoszenia: etapy + przypisani kandydaci.
     */
    public function board(JobPosting $jobPosting): JsonResponse
    {
        $stages = PipelineStage::orderBy('position')->get();

        $applications = $jobPosting->applications()
            ->with('candidate')
            ->orderBy('position')
            ->get()
            ->groupBy('stage_id');

        $columns = $stages->map(fn (PipelineStage $stage) => [
            'id' => $stage->id,
            'name' => $stage->name,
            'color' => $stage->color,
            'is_terminal' => $stage->is_terminal,
            'applications' => ApplicationResource::collection(
                $applications->get($stage->id, collect())
            ),
        ]);

        return response()->json([
            'job_posting' => new JobPostingResource($jobPosting->load('company')),
            'stages' => $columns,
        ]);
    }
}
