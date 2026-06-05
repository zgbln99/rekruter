<?php

namespace Tests\Feature\Placements;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Placement;
use App\Models\PlacementInstallment;
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
        // Stała kwota rozliczenia ustalona z góry w ustawieniach agencji.
        $this->tenant = Tenant::factory()->create([
            'settings' => ['placement_fee' => 1000, 'placement_currency' => 'EUR'],
        ]);
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    private function offer(): JobPosting
    {
        $company = Company::factory()->for($this->tenant)->create();

        return JobPosting::factory()->for($this->tenant)->for($company)->create();
    }

    private function createPlacement(Candidate $candidate, JobPosting $offer, string $arrival = '2026-07-01 09:00'): array
    {
        return $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => $arrival,
        ])->json();
    }

    public function test_creating_placement_uses_fixed_fee_and_schedules_two_installments(): void
    {
        // Tworzymy jako administrator, aby zobaczyć dane finansowe w odpowiedzi.
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $response = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-01 09:00',
        ])->assertCreated();

        // Kwota wzięta ze stałej stawki (1000), nie z formularza.
        $response->assertJsonPath('total_amount', '1000.00');
        $response->assertJsonCount(2, 'installments');

        // Raty: +14 dni / +28 dni od przyjazdu, kwoty 50/50 (suma = total).
        $response->assertJsonPath('installments.0.sequence', 1);
        $response->assertJsonPath('installments.0.due_date', '2026-07-15');
        $response->assertJsonPath('installments.0.amount', '500.00');
        $response->assertJsonPath('installments.1.sequence', 2);
        $response->assertJsonPath('installments.1.due_date', '2026-07-29');
        $response->assertJsonPath('installments.1.amount', '500.00');
    }

    public function test_recruiter_does_not_see_amounts_or_installments(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();

        $response = $this->postJson("/api/v1/candidates/{$candidate->id}/placements", [
            'job_posting_id' => $offer->id,
            'arrival_at' => '2026-07-01 09:00',
        ])->assertCreated();

        // Rekruter: kwoty ukryte, lista rat pusta.
        $response->assertJsonPath('total_amount', null);
        $response->assertJsonCount(0, 'installments');

        // Ale w bazie raty istnieją (powstały automatycznie).
        $this->assertSame(2, PlacementInstallment::count());
    }

    public function test_marking_arrival_confirmed_sets_status_and_timestamp(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();
        $placement = $this->createPlacement($candidate, $offer);

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
        $this->createPlacement($candidate, $offer, '2026-07-10 09:00');

        $events = $this->getJson('/api/v1/calendar?from=2026-07-01&to=2026-07-31')->assertOk()->json();

        $types = array_column($events, 'type');
        $this->assertContains('arrival', $types);
        $this->assertNotContains('installment', $types); // rekruter nie widzi rozliczeń
    }

    public function test_calendar_returns_installments_for_admin(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();
        $this->createPlacement($candidate, $offer, '2026-07-10 09:00');

        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $events = $this->getJson('/api/v1/calendar?from=2026-07-01&to=2026-08-31')->assertOk()->json();
        $types = array_column($events, 'type');
        $this->assertContains('installment', $types);
    }

    public function test_recruiter_cannot_edit_installment(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();
        $this->createPlacement($candidate, $offer);

        $installment = PlacementInstallment::firstOrFail();

        $this->patchJson("/api/v1/placement-installments/{$installment->id}", [
            'status' => 'paid',
        ])->assertForbidden();
    }

    public function test_admin_can_mark_installment_paid(): void
    {
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $offer = $this->offer();
        $this->createPlacement($candidate, $offer);

        $installment = PlacementInstallment::firstOrFail();

        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $this->patchJson("/api/v1/placement-installments/{$installment->id}", [
            'status' => 'paid',
        ])->assertOk()
            ->assertJsonPath('status', 'paid')
            ->assertJsonPath('paid_at', now()->toDateString());
    }

    public function test_settings_hide_placement_fee_from_recruiter(): void
    {
        // Rekruter nie widzi stałej kwoty w ustawieniach.
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonMissingPath('placement_fee');

        // Administrator widzi.
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());
        $this->getJson('/api/v1/settings')
            ->assertOk()
            ->assertJsonPath('placement_fee', 1000);
    }
}
