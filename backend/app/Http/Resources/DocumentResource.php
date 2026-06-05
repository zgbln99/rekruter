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
            // Aliasy nazewnictwa zgodne z DESIGN.md 19.7.
            'storage_disk' => $this->disk,
            'storage_path' => $this->path,
            'original_filename' => $this->original_name,
            'mime_type' => $this->mime,
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at?->toIso8601String(),
            // Pobieranie wyłącznie przez uwierzytelniony endpoint (RODO).
            'download_url' => route('candidates.documents.download', [
                'candidate' => $this->candidate_id,
                'document' => $this->id,
            ]),
        ];
    }
}
