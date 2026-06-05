<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CandidateEditPayloadTest extends TestCase
{
    use RefreshDatabase;

    public function test_full_edit_payload_like_frontend_succeeds(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());
        $candidate = Candidate::factory()->for($tenant)->create();

        // Payload identyczny jak z formularza edycji (część pól pustych).
        $payload = [
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'phone' => '+48600100200',
            'email' => '',
            'city' => 'Wrocław',
            'country' => '',
            'address' => 'ul. Testowa 1',
            'date_of_birth' => '',
            'nationality' => '',
            'availability_from' => '',
            'source' => '',
            'experience_notes' => '',
            'internal_notes' => '',
            'has_adr' => true,
            'has_code_95' => false,
            'has_hds' => false,
            'exp_reefer' => false,
            'exp_tilt' => false,
            'exp_international' => false,
            'lang_de' => false,
            'lang_en' => false,
            'license_categories' => ['C+E'],
            'work_history' => [
                ['employer' => 'Trans', 'position' => 'Kierowca', 'period' => '2020-2022'],
            ],
        ];

        $response = $this->patchJson("/api/v1/candidates/{$candidate->id}", $payload);
        $response->assertOk();
    }
}
