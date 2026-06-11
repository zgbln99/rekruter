<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExportCsvTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    public function test_export_returns_csv_with_filtered_candidates(): void
    {
        Candidate::factory()->for($this->tenant)->create([
            'first_name' => 'Marek', 'last_name' => 'Kowalski', 'status' => 'active',
        ]);
        Candidate::factory()->for($this->tenant)->create([
            'first_name' => 'Jan', 'last_name' => 'Nowak', 'status' => 'new',
        ]);

        $response = $this->get('/api/v1/candidates/export-csv?status=active');

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Marek', $csv);
        $this->assertStringNotContainsString('Nowak', $csv);
    }
}
