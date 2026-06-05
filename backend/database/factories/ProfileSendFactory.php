<?php

namespace Database\Factories;

use App\Enums\CompanyDecision;
use App\Enums\ProfileSendStatus;
use App\Models\Candidate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProfileSend>
 */
class ProfileSendFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'candidate_id' => Candidate::factory(),
            'recipient_email' => fake()->companyEmail(),
            'status' => ProfileSendStatus::Sent,
            'decision' => CompanyDecision::Pending,
            'sent_at' => now(),
        ];
    }
}
