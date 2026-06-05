<?php

namespace Tests\Feature\JobOffers;

use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class JobOfferTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    public function test_can_create_full_job_offer(): void
    {
        $company = Company::factory()->for($this->tenant)->create();

        $this->postJson('/api/v1/job-offers', [
            'company_id' => $company->id,
            'title' => 'Kierowca C+E chłodnia',
            'driver_type' => 'solo',
            'trailer_type' => 'chłodnia',
            'country' => 'Niemcy',
            'work_system' => '3/1',
            'salary_amount' => '2000-2300',
            'currency' => 'EUR',
            'required_categories' => ['C+E'],
            'requirements' => ['ce' => true, 'adr' => true, 'exp_reefer' => true],
            'call_script' => ['Czy ma Pan C+E?', 'Od kiedy może Pan wyjechać?'],
            'public_description' => 'Szukamy kierowcy C+E na chłodnię, trasy DE.',
            'recruiter_notes' => 'Klient nie chce kandydatów bez chłodni.',
        ])
            ->assertCreated()
            ->assertJsonPath('title', 'Kierowca C+E chłodnia')
            ->assertJsonPath('requirements.ce', true)
            ->assertJsonCount(2, 'call_script');
    }

    public function test_create_candidate_from_offer_assigns_to_offer(): void
    {
        $company = Company::factory()->for($this->tenant)->create();
        $offer = JobPosting::factory()->for($this->tenant)->for($company)->create();

        $response = $this->postJson("/api/v1/job-offers/{$offer->id}/create-candidate", [
            'first_name' => 'Marek',
            'last_name' => 'Kowalski',
            'phone' => '600 500 400',
        ]);

        $response->assertCreated()->assertJsonPath('first_name', 'Marek');
        $candidateId = $response->json('id');

        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidateId,
            'job_posting_id' => $offer->id,
            'status' => 'new',
        ]);

        // Timeline: przypisano do ogłoszenia.
        $this->assertDatabaseHas('activities', [
            'subject_id' => $candidateId,
            'event' => 'assigned_to_offer',
        ]);
    }

    public function test_create_candidate_from_offer_reuses_existing_by_phone(): void
    {
        $company = Company::factory()->for($this->tenant)->create();
        $offer = JobPosting::factory()->for($this->tenant)->for($company)->create();

        $first = $this->postJson("/api/v1/job-offers/{$offer->id}/create-candidate", [
            'first_name' => 'Jan', 'phone' => '+48600500401',
        ])->assertCreated()->json('id');

        // Ten sam numer → istniejący kandydat (200), brak duplikatu.
        $this->postJson("/api/v1/job-offers/{$offer->id}/create-candidate", [
            'first_name' => 'Jan2', 'phone' => '600 500 401',
        ])->assertOk()->assertJsonPath('duplicate', true)->assertJsonPath('id', $first);

        $this->assertDatabaseCount('candidates', 1);
    }
}
