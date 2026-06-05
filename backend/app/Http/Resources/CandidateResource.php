<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Candidate
 */
class CandidateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->fullName(),
            'phone' => $this->phone,
            'phone_normalized' => $this->phone_normalized,
            'email' => $this->email,
            'city' => $this->city,
            'country' => $this->country,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'license_categories' => $this->license_categories ?? [],
            'has_adr' => $this->has_adr,
            'adr_expiry' => $this->adr_expiry?->toDateString(),
            'has_code_95' => $this->has_code_95,
            'code_95_expiry' => $this->code_95_expiry?->toDateString(),
            'driver_card_expiry' => $this->driver_card_expiry?->toDateString(),
            'source' => $this->source,
            'consent_rodo_at' => $this->consent_rodo_at?->toIso8601String(),
            'internal_notes' => $this->internal_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'contact_logs' => ContactLogResource::collection($this->whenLoaded('contactLogs')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
        ];
    }
}
