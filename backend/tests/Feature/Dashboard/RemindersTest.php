<?php

namespace Tests\Feature\Dashboard;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\PlacementInstallment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['settings' => ['placement_fee' => 1000]]);
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    private function placementToday(): array
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $company = Company::factory()->for($this->tenant)->create();
        $offer = JobPosting::factory()->for($this->tenant)->for($company)->create();

        return $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => now()->setTime(9, 0)->format('Y-m-d H:i'),
        ])->json();
    }

    public function test_arrivals_today_appear_in_reminders(): void
    {
        $this->placementToday();

        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(1, 'reminders.arrivals_today');
    }

    public function test_installments_due_soon_visible_only_to_admin(): void
    {
        $this->placementToday();
        // Ustaw termin raty na jutro.
        $inst = PlacementInstallment::firstOrFail();
        $inst->update(['due_date' => now()->addDay()->toDateString()]);

        // Rekruter nie widzi rat.
        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(0, 'reminders.installments_due');

        // Administrator widzi.
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());
        $this->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonCount(1, 'reminders.installments_due');
    }
}
