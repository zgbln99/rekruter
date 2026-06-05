<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Company
 */
class CompanyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'website' => $this->website,
            'nip' => $this->nip,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'contact_person' => $this->contact_person,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'notes' => $this->notes,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'job_postings_count' => $this->whenCounted('jobPostings'),
            'job_postings' => JobPostingResource::collection($this->whenLoaded('jobPostings')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
