<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\PlacementInstallment;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DeleteCandidateTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['settings' => ['placement_fee' => 1000]]);
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    public function test_deleting_candidate_removes_related_records(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $company = Company::factory()->for($this->tenant)->create();
        $offer = JobPosting::factory()->for($this->tenant)->for($company)->create();

        // Zadanie + skierowanie (z ratami) powiązane z kandydatem.
        $this->postJson('/api/v1/tasks', [
            'candidate_id' => $candidate->id,
            'title' => 'Oddzwonić',
        ])->assertCreated();

        $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => now()->addDays(2)->format('Y-m-d H:i'),
        ])->assertCreated();

        $this->assertSame(1, Task::where('candidate_id', $candidate->id)->count());
        $this->assertSame(2, PlacementInstallment::count());

        // Usuń kandydata.
        $this->deleteJson("/api/v1/candidates/{$candidate->id}")->assertOk();

        // Powiązania zniknęły, kandydat soft-deleted.
        $this->assertSame(0, Task::where('candidate_id', $candidate->id)->count());
        $this->assertSame(0, PlacementInstallment::count());
        $this->assertSame(0, $candidate->placements()->count());
        $this->assertNull(Candidate::find($candidate->id));
        $this->assertNotNull(Candidate::withTrashed()->find($candidate->id));
    }
}
