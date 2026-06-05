<?php

namespace Database\Factories;

use App\Enums\JobPostingStatus;
use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\JobPosting>
 */
class JobPostingFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => Company::factory(),
            'title' => 'Kierowca '.fake()->randomElement(['C+E', 'międzynarodowy', 'krajowy']),
            'description' => fake()->paragraph(),
            'required_categories' => ['C+E'],
            'location' => fake()->city(),
            'status' => JobPostingStatus::Open,
        ];
    }
}
