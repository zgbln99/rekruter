<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Application
 */
class ApplicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'job_posting_id' => $this->job_posting_id,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'position' => $this->position,
            'notes' => $this->notes,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
        ];
    }
}
