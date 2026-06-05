<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'candidate_id' => $this->candidate_id,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'original_name' => $this->original_name,
            'mime' => $this->mime,
            'size' => $this->size,
            'is_profile_photo' => $this->is_profile_photo,
            'created_at' => $this->created_at?->toIso8601String(),
            // Pobieranie wyłącznie przez uwierzytelniony endpoint (RODO).
            'download_url' => route('candidates.documents.download', [
                'candidate' => $this->candidate_id,
                'document' => $this->id,
            ]),
        ];
    }
}
