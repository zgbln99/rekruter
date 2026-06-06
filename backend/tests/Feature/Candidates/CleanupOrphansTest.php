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
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CleanupOrphansTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_removes_orphans_of_deleted_candidates(): void
    {
        $tenant = Tenant::factory()->create(['settings' => ['placement_fee' => 1000]]);
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $candidate = Candidate::factory()->for($tenant)->create();
        $company = Company::factory()->for($tenant)->create();
        $offer = JobPosting::factory()->for($tenant)->for($company)->create();

        $this->postJson('/api/v1/tasks', ['candidate_id' => $candidate->id, 'title' => 'Oddzwonić'])->assertCreated();
        $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => now()->addDays(2)->format('Y-m-d H:i'),
        ])->assertCreated();

        // Symulacja starego usunięcia (soft-delete bez sprzątania powiązań).
        DB::table('candidates')->where('id', $candidate->id)->update(['deleted_at' => now()]);

        $this->assertGreaterThan(0, Task::withoutGlobalScopes()->count());
        $this->assertSame(2, PlacementInstallment::withoutGlobalScopes()->count());

        $this->artisan('rekruter:cleanup-orphans --force')->assertSuccessful();

        $this->assertSame(0, Task::withoutGlobalScopes()->count());
        $this->assertSame(0, PlacementInstallment::withoutGlobalScopes()->count());
    }
}
