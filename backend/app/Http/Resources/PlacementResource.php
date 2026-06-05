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
        // Dane finansowe (kwota, raty/rozliczenia) widzi wyłącznie administrator.
        $isAdmin = (bool) $request->user()?->isAdmin();

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
            'notes' => $this->notes,
            'candidate' => new CandidateResource($this->whenLoaded('candidate')),
            'job_posting' => new JobPostingResource($this->whenLoaded('jobPosting')),
            'created_at' => optional($this->created_at)->toIso8601String(),
            // Tylko administrator:
            'total_amount' => $isAdmin ? $this->total_amount : null,
            'currency' => $this->currency,
            'installments' => $isAdmin
                ? PlacementInstallmentResource::collection($this->whenLoaded('installments'))
                : [],
        ];
    }
}
