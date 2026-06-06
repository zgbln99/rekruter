<?php

namespace Tests\Feature\Tasks;

use App\Models\Candidate;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TaskTest extends TestCase
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

    public function test_today_view_returns_my_open_tasks_due_today(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();

        // Zadanie na dziś — powinno się pojawić.
        Task::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'assigned_to' => $this->user->id,
            'due_at' => now()->setTime(10, 0),
        ]);

        // Zadanie w przyszłości — nie powinno się pojawić w „today".
        Task::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'assigned_to' => $this->user->id,
            'due_at' => now()->addDays(5),
        ]);

        // Zadanie innego użytkownika — nie moje.
        $other = User::factory()->for($this->tenant)->create();
        Task::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'assigned_to' => $other->id,
            'due_at' => now()->setTime(9, 0),
        ]);

        $this->getJson('/api/v1/tasks?filter=today')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_marking_task_done_sets_completed_at(): void
    {
        $task = Task::factory()->for($this->tenant)->create([
            'assigned_to' => $this->user->id,
        ]);

        $this->patchJson('/api/v1/tasks/'.$task->id, ['status' => 'done'])
            ->assertOk()
            ->assertJsonPath('status', 'done');

        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_task_can_be_rescheduled(): void
    {
        $task = Task::factory()->for($this->tenant)->create([
            'assigned_to' => $this->user->id,
        ]);

        $newDue = now()->addDays(2)->startOfHour();

        $this->patchJson('/api/v1/tasks/'.$task->id, [
            'due_at' => $newDue->toIso8601String(),
        ])->assertOk();

        $this->assertEquals(
            $newDue->toIso8601String(),
            $task->fresh()->due_at->toIso8601String()
        );
    }

    public function test_can_create_task_for_candidate(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $due = now()->addDay()->startOfHour();

        $this->postJson('/api/v1/tasks', [
            'candidate_id' => $candidate->id,
            'title' => 'Oddzwonić w sprawie ADR',
            'due_at' => $due->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('title', 'Oddzwonić w sprawie ADR')
            ->assertJsonPath('candidate_id', $candidate->id)
            ->assertJsonPath('status', 'open');

        $this->assertDatabaseHas('tasks', [
            'candidate_id' => $candidate->id,
            'assigned_to' => $this->user->id,
            'created_by' => $this->user->id,
            'title' => 'Oddzwonić w sprawie ADR',
        ]);
    }
}
