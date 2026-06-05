<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\JobPosting
 */
class JobPostingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'title' => $this->title,
            'driver_type' => $this->driver_type,
            'trailer_type' => $this->trailer_type,
            'vehicle_type' => $this->vehicle_type,
            'cargo' => $this->cargo,
            'routes_info' => $this->routes_info,
            'accommodation' => $this->accommodation,
            'onsite_contact' => $this->onsite_contact,
            'arrival_info' => $this->arrival_info,
            'country' => $this->country,
            'region_base' => $this->region_base,
            'work_system' => $this->work_system,
            'salary_amount' => $this->salary_amount,
            'currency' => $this->currency,
            'start_date' => $this->start_date?->toDateString(),
            'required_language' => $this->required_language,
            'required_experience' => $this->required_experience,
            'description' => $this->description,
            'public_description' => $this->public_description,
            'recruiter_notes' => $this->recruiter_notes,   // wewnętrzne — tylko UI rekruterki
            'call_script' => $this->call_script ?? [],
            'required_categories' => $this->required_categories ?? [],
            'requirements' => $this->requirements ?? (object) [],
            'location' => $this->location,
            'salary_range' => $this->salary_range,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'applications_count' => $this->whenCounted('applications'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
