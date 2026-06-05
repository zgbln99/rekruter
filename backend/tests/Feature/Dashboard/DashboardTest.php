<?php

namespace Tests\Feature\Dashboard;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_returns_metrics(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        Candidate::factory()->count(3)->for($tenant)->create();
        $company = Company::factory()->for($tenant)->create();
        JobPosting::factory()->for($tenant)->for($company)->create(['status' => 'open']);

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('candidates.total', 3)
            ->assertJsonPath('offers.active', 1)
            ->assertJsonPath('companies', 1)
            ->assertJsonStructure([
                'candidates' => ['total', 'new_this_week', 'by_status'],
                'offers' => ['total', 'active'],
                'tasks' => ['today', 'overdue'],
                'profiles' => ['sent_total', 'pending_decisions'],
                'pipeline',
                'recent_activity',
            ]);
    }
}
