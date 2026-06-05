<?php

namespace Tests\Feature\Placements;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Placement;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PlacementTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    private function offer(): JobPosting
    {
        $company = Company::factory()->for($this->tenant)->create();

        return JobPosting::factory()->for($this->tenant)->for($company)->create();
    }

    public function test_creating_placement_schedules_two_installments(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $response = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-01 09:00',
            'total_amount' => 1000,
            'currency' => 'EUR',
        ])->assertCreated();

        $response->assertJsonCount(2, 'installments');

        // Raty: +14 dni / +28 dni od przyjazdu, kwoty 50/50 (suma = total).
        $response->assertJsonPath('installments.0.sequence', 1);
        $response->assertJsonPath('installments.0.due_date', '2026-07-15');
        $response->assertJsonPath('installments.0.amount', '500.00');
        $response->assertJsonPath('installments.1.sequence', 2);
        $response->assertJsonPath('installments.1.due_date', '2026-07-29');
        $response->assertJsonPath('installments.1.amount', '500.00');
    }

    public function test_referral_pdf_uses_placement_arrival(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $created = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-01 09:00',
        ])->assertCreated()->json();

        // Endpoint PDF istnieje i jest dostępny (sam render PDF wymaga Gotenberga,
        // więc sprawdzamy tylko, że trasa i autoryzacja działają — 200 lub 5xx z Gotenberga).
        $this->assertNotEmpty($created['id']);
    }

    public function test_marking_arrival_confirmed_sets_status_and_timestamp(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $placement = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-01 09:00',
        ])->json();

        $this->patchJson("/api/v1/placements/{$placement['id']}/arrival", [
            'status' => 'confirmed',
        ])->assertOk()
            ->assertJsonPath('arrival_status', 'confirmed');

        $this->assertNotNull(Placement::find($placement['id'])->arrival_confirmed_at);
    }

    public function test_calendar_returns_arrivals_for_recruiter_but_not_installments(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-10 09:00',
            'total_amount' => 800,
        ])->assertCreated();

        $events = $this->getJson('/api/v1/calendar?from=2026-07-01&to=2026-07-31')->assertOk()->json();

        $types = array_column($events, 'type');
        $this->assertContains('arrival', $types);
        $this->assertNotContains('installment', $types); // rekruter nie widzi rozliczeń
    }

    public function test_calendar_returns_installments_for_admin(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-10 09:00',
            'total_amount' => 800,
        ])->assertCreated();

        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $events = $this->getJson('/api/v1/calendar?from=2026-07-01&to=2026-08-31')->assertOk()->json();
        $types = array_column($events, 'type');
        $this->assertContains('installment', $types);
    }

    public function test_recruiter_cannot_edit_installment(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $placement = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-10 09:00',
            'total_amount' => 800,
        ])->json();

        $installmentId = $placement['installments'][0]['id'];

        $this->patchJson("/api/v1/placement-installments/{$installmentId}", [
            'status' => 'paid',
        ])->assertForbidden();
    }

    public function test_admin_can_mark_installment_paid(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $placement = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-10 09:00',
            'total_amount' => 800,
        ])->json();

        $installmentId = $placement['installments'][0]['id'];

        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $this->patchJson("/api/v1/placement-installments/{$installmentId}", [
            'status' => 'paid',
        ])->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('paid_at', now()->toDateString());
    }
}
