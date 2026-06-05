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
            'address' => $this->address,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'nationality' => $this->nationality,
            'availability_from' => $this->availability_from?->toDateString(),
            'experience_notes' => $this->experience_notes,
            'work_history' => $this->work_history ?? [],
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'license_categories' => $this->license_categories ?? [],
            'has_adr' => $this->has_adr,
            'adr_expiry' => $this->adr_expiry?->toDateString(),
            'has_code_95' => $this->has_code_95,
            'code_95_expiry' => $this->code_95_expiry?->toDateString(),
            'driver_card_expiry' => $this->driver_card_expiry?->toDateString(),
            'has_hds' => $this->has_hds,
            'exp_reefer' => $this->exp_reefer,
            'exp_tilt' => $this->exp_tilt,
            'exp_international' => $this->exp_international,
            'lang_de' => $this->lang_de,
            'lang_en' => $this->lang_en,
            'source' => $this->source,
            'profile_photo_id' => $this->profile_photo_id,
            'consent_rodo_at' => $this->consent_rodo_at?->toIso8601String(),
            'internal_notes' => $this->internal_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'contact_logs' => ContactLogResource::collection($this->whenLoaded('contactLogs')),
            'tasks' => TaskResource::collection($this->whenLoaded('tasks')),
            'applications' => ApplicationResource::collection($this->whenLoaded('applications')),
        ];
    }
}
