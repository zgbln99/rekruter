<?php

namespace Tests\Feature\Search;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    public function test_search_finds_candidate_by_name_and_phone(): void
    {
        Candidate::factory()->for($this->tenant)->create([
            'first_name' => 'Jan', 'last_name' => 'Kowalski', 'phone' => '+48 600 100 200',
        ]);

        $this->getJson('/api/v1/search?q=Kowalski')
            ->assertOk()
            ->assertJsonPath('candidates.0.full_name', 'Jan Kowalski');

        $this->getJson('/api/v1/search?q=600100')
            ->assertOk()
            ->assertJsonCount(1, 'candidates');
    }

    public function test_search_finds_offer_by_title(): void
    {
        $company = Company::factory()->for($this->tenant)->create();
        JobPosting::factory()->for($this->tenant)->for($company)->create(['title' => 'Kierowca C+E chłodnia']);

        $this->getJson('/api/v1/search?q=chłodnia')
            ->assertOk()
            ->assertJsonPath('offers.0.title', 'Kierowca C+E chłodnia');
    }

    public function test_short_query_returns_empty(): void
    {
        $this->getJson('/api/v1/search?q=a')
            ->assertOk()
            ->assertJsonCount(0, 'candidates')
            ->assertJsonCount(0, 'offers');
    }
}
