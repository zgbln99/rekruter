<?php

namespace Tests\Feature\Activities;

use App\Models\Candidate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityTest extends TestCase
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

    public function test_creating_candidate_records_activity(): void
    {
        $this->postJson('/api/v1/candidates', [
            'phone' => '+48600700800',
            'first_name' => 'Test',
        ])->assertCreated();

        $candidate = Candidate::first();

        $this->assertDatabaseHas('activities', [
            'subject_id' => $candidate->id,
            'event' => 'created',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_candidate_activity_endpoint_lists_events(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $candidate->update(['city' => 'Wrocław']);

        $this->getJson("/api/v1/candidates/{$candidate->id}/activities")
            ->assertOk()
            ->assertJsonFragment(['event' => 'updated']);
    }
}
