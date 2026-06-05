<?php

namespace Tests\Feature\Matching;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    private function offer(array $requirements): JobPosting
    {
        $company = Company::factory()->for($this->tenant)->create();

        return JobPosting::factory()->for($this->tenant)->for($company)->create([
            'requirements' => $requirements,
        ]);
    }

    public function test_full_match(): void
    {
        $offer = $this->offer(['ce' => true, 'adr' => true]);
        $candidate = Candidate::factory()->for($this->tenant)->create([
            'license_categories' => ['C+E'],
            'has_adr' => true,
        ]);

        $this->getJson("/api/v1/candidates/{$candidate->id}/match/{$offer->id}")
            ->assertOk()
            ->assertJsonPath('result', 'match')
            ->assertJsonPath('missing', []);
    }

    public function test_partial_match_lists_missing(): void
    {
        $offer = $this->offer(['ce' => true, 'adr' => true, 'exp_reefer' => true]);
        $candidate = Candidate::factory()->for($this->tenant)->create([
            'license_categories' => ['C+E'],
            'has_adr' => false,
            'exp_reefer' => false,
        ]);

        $this->getJson("/api/v1/candidates/{$candidate->id}/match/{$offer->id}")
            ->assertOk()
            ->assertJsonPath('result', 'partial')
            ->assertJsonFragment(['brak ADR'])
            ->assertJsonFragment(['brak doświadczenia na chłodni']);
    }

    public function test_no_match(): void
    {
        $offer = $this->offer(['ce' => true, 'adr' => true]);
        $candidate = Candidate::factory()->for($this->tenant)->create([
            'license_categories' => [],
            'has_adr' => false,
        ]);

        $this->getJson("/api/v1/candidates/{$candidate->id}/match/{$offer->id}")
            ->assertOk()
            ->assertJsonPath('result', 'no_match');
    }
}
