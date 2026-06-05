<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ProfileSend
 */
class ProfileSendResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'company_id' => $this->company_id,
            'job_posting_id' => $this->job_posting_id,
            'recipient_email' => $this->recipient_email,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'decision' => $this->decision->value,
            'decision_label' => $this->decision->label(),
            'decision_at' => $this->decision_at?->toIso8601String(),
            'sent_at' => $this->sent_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
