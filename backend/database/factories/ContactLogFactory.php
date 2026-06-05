<?php

namespace Database\Factories;

use App\Enums\ContactChannel;
use App\Enums\ContactOutcome;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ContactLog>
 */
class ContactLogFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'channel' => ContactChannel::Phone,
            'outcome' => ContactOutcome::Interested,
            'note' => fake()->sentence(),
            'contacted_at' => now(),
        ];
    }
}
