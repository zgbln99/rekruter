<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Placement
 */
class PlacementResource extends JsonResource
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
            'company_id' => $this->company_id,
            'arrival_at' => optional($this->arrival_at)->toIso8601String(),
            'arrival_status' => $this->arrival_status->value,
            'arrival_status_label' => $this->arrival_status->label(),
            'arrival_status_color' => $this->arrival_status->color(),
            'arrival_confirmed_at' => optional($this->arrival_confirmed_at)->toIso8601String(),
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'notes' => $this->notes,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'installments' => PlacementInstallmentResource::collection($this->whenLoaded('installments')),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}
