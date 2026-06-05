<?php

namespace Database\Factories;

use App\Enums\DocumentType;
use App\Models\Candidate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'candidate_id' => Candidate::factory(),
            'type' => DocumentType::Cv,
            'disk' => 's3',
            'path' => 'tenants/x/candidates/y/documents/'.Str::uuid().'.pdf',
            'original_name' => 'cv.pdf',
            'mime' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
