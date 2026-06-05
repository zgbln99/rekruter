<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => TaskType::FollowUp,
            'status' => TaskStatus::Open,
            'title' => 'Kontakt: '.fake()->name(),
            'description' => fake()->sentence(),
            'due_at' => now()->addHours(fake()->numberBetween(1, 48)),
        ];
    }
}
