<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\PipelineStage;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Application>
 */
class ApplicationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'candidate_id' => Candidate::factory(),
            'job_posting_id' => JobPosting::factory(),
            'stage_id' => PipelineStage::factory(),
            'position' => 1,
        ];
    }
}
