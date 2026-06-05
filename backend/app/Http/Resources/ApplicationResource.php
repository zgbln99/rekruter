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
            'stage_id' => $this->stage_id,
            'position' => $this->position,
            'notes' => $this->notes,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
        ];
    }
}
