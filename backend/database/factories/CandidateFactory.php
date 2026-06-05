<?php

namespace Database\Factories;

use App\Enums\CandidateStatus;
use App\Models\Tenant;
use App\Support\PhoneNumber;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Candidate>
 */
class CandidateFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $phone = '+486'.fake()->numerify('########');

        return [
            'tenant_id' => Tenant::factory(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => $phone,
            'phone_normalized' => PhoneNumber::normalize($phone),
            'email' => fake()->optional()->safeEmail(),
            'city' => fake()->city(),
            'country' => 'PL',
            'status' => CandidateStatus::New,
            'license_categories' => ['C+E'],
            'has_adr' => fake()->boolean(30),
            'has_code_95' => fake()->boolean(60),
            'source' => 'phone',
        ];
    }
}
