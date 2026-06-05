<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CandidatePersonalTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_update_personal_details_and_work_history(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());
        $candidate = Candidate::factory()->for($tenant)->create();

        $this->patchJson("/api/v1/candidates/{$candidate->id}", [
            'address' => 'ul. Kierowców 1, Wrocław',
            'date_of_birth' => '1985-04-12',
            'nationality' => 'PL',
            'work_history' => [
                ['employer' => 'Trans-Pol', 'position' => 'Kierowca C+E', 'period' => '2018–2022'],
                ['employer' => 'EuroLogistik', 'position' => 'Kierowca chłodnia', 'period' => '2022–2024'],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('address', 'ul. Kierowców 1, Wrocław')
            ->assertJsonPath('date_of_birth', '1985-04-12')
            ->assertJsonPath('work_history.0.employer', 'Trans-Pol');

        $this->assertCount(2, $candidate->fresh()->work_history);
    }
}
