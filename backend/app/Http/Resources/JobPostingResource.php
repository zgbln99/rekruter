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
            'contract_type' => $this->contract_type,
            'points_per_day' => $this->points_per_day,
            'loading_info' => $this->loading_info,
            'daily_km' => $this->daily_km,
            'pdf_url' => $this->pdf_url,
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
            'faq' => $this->faq ?? [],
            'required_categories' => $this->required_categories ?? [],
            'requirements' => $this->requirements ?? (object) [],
            'location' => $this->location,
            'salary_range' => $this->salary_range,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_public' => (bool) $this->is_public,
            'is_featured' => (bool) $this->is_featured,
            'public_url' => $this->isPublished() ? $this->publicUrl() : null,
            'cover_image_url' => $this->cover_image_url,
            'applications_count' => $this->whenCounted('applications'),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
