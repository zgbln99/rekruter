<?php

namespace Tests\Feature\Notifications;

use App\Models\Candidate;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        Sanctum::actingAs($this->user);
    }

    public function test_returns_overdue_tasks_and_expiring_qualifications(): void
    {
        Task::factory()->for($this->tenant)->create([
            'assigned_to' => $this->user->id,
            'status' => 'open',
            'title' => 'Zaległe',
            'due_at' => now()->subDays(2),
        ]);

        Candidate::factory()->for($this->tenant)->create([
            'code_95_expiry' => now()->addDays(10)->toDateString(),
        ]);

        $res = $this->getJson('/api/v1/notifications')->assertOk();

        $types = array_column($res->json('items'), 'type');
        $this->assertContains('task', $types);
        $this->assertContains('expiry', $types);
        $this->assertGreaterThanOrEqual(2, $res->json('count'));
    }

    public function test_no_notifications_when_nothing_pending(): void
    {
        $this->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonPath('count', 0);
    }
}
