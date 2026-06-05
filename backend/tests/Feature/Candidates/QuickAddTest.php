<?php

namespace Tests\Feature\Candidates;

use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Candidate;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuickAddTest extends TestCase
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

    public function test_quick_add_creates_candidate_with_minimal_fields(): void
    {
        $response = $this->postJson('/api/v1/candidates', [
            'phone' => '600 100 200',
            'first_name' => 'Jan',
            'license_categories' => ['C+E'],
        ]);

        $response->assertCreated()
            ->assertJsonPath('first_name', 'Jan')
            ->assertJsonPath('phone_normalized', '+48600100200')
            ->assertJsonPath('license_categories', ['C+E']);

        $this->assertDatabaseHas('candidates', [
            'phone_normalized' => '+48600100200',
            'tenant_id' => $this->tenant->id,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_quick_add_with_contact_and_next_contact_creates_followup_task(): void
    {
        $response = $this->postJson('/api/v1/candidates', [
            'phone' => '+48600100201',
            'first_name' => 'Anna',
            'contact' => [
                'channel' => 'phone',
                'outcome' => 'interested',
                'note' => 'Szuka tras międzynarodowych',
                'next_contact_at' => now()->addDay()->toIso8601String(),
            ],
        ]);

        $response->assertCreated();
        $candidateId = $response->json('id');

        // Powstał wpis kontaktu.
        $this->assertDatabaseHas('contact_logs', [
            'candidate_id' => $candidateId,
            'outcome' => 'interested',
        ]);

        // Powstało automatyczne zadanie follow-up powiązane z kontaktem.
        $task = Task::where('candidate_id', $candidateId)->first();
        $this->assertNotNull($task);
        $this->assertSame(TaskType::FollowUp, $task->type);
        $this->assertSame(TaskStatus::Open, $task->status);
        $this->assertSame($this->user->id, $task->assigned_to);
    }

    public function test_quick_add_without_next_contact_creates_no_task(): void
    {
        $response = $this->postJson('/api/v1/candidates', [
            'phone' => '+48600100202',
            'first_name' => 'Piotr',
            'contact' => [
                'channel' => 'phone',
                'outcome' => 'no_answer',
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_duplicate_phone_returns_existing_candidate(): void
    {
        Candidate::factory()->for($this->tenant)->create([
            'phone' => '+48600100203',
            'phone_normalized' => '+48600100203',
            'first_name' => 'Istniejący',
        ]);

        $response = $this->postJson('/api/v1/candidates', [
            'phone' => '0600 100 203',
            'first_name' => 'Nowy',
        ]);

        $response->assertStatus(409)
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('candidate.first_name', 'Istniejący');

        $this->assertDatabaseCount('candidates', 1);
    }

    public function test_lookup_finds_existing_candidate_by_phone(): void
    {
        Candidate::factory()->for($this->tenant)->create([
            'phone' => '+48600100204',
            'phone_normalized' => '+48600100204',
        ]);

        $this->getJson('/api/v1/candidates/lookup?phone='.urlencode('600-100-204'))
            ->assertOk()
            ->assertJsonPath('exists', true)
            ->assertJsonPath('normalized', '+48600100204');

        $this->getJson('/api/v1/candidates/lookup?phone=600999888')
            ->assertOk()
            ->assertJsonPath('exists', false);
    }

    public function test_quick_add_requires_phone_and_name(): void
    {
        $this->postJson('/api/v1/candidates', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['phone', 'first_name']);
    }
}
