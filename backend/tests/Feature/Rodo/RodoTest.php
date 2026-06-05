<?php

namespace Tests\Feature\Rodo;

use App\Models\Candidate;
use App\Models\Company;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RodoTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->tenant = Tenant::factory()->create();
    }

    private function actingAsRecruiter(): User
    {
        $user = User::factory()->for($this->tenant)->create();
        Sanctum::actingAs($user);

        return $user;
    }

    private function actingAsAdmin(): User
    {
        $user = User::factory()->for($this->tenant)->admin()->create();
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_export_returns_full_candidate_data(): void
    {
        $this->actingAsRecruiter();
        $candidate = Candidate::factory()->for($this->tenant)->create(['first_name' => 'Eksport']);

        $this->getJson("/api/v1/candidates/{$candidate->id}/export")
            ->assertOk()
            ->assertJsonPath('candidate.first_name', 'Eksport')
            ->assertJsonStructure(['exported_at', 'candidate', 'contact_logs', 'documents', 'activities']);
    }

    public function test_consent_can_be_granted_and_revoked(): void
    {
        $this->actingAsRecruiter();
        $candidate = Candidate::factory()->for($this->tenant)->create(['consent_rodo_at' => null]);

        $this->patchJson("/api/v1/candidates/{$candidate->id}/consent", ['granted' => true])
            ->assertOk();
        $this->assertNotNull($candidate->fresh()->consent_rodo_at);

        $this->patchJson("/api/v1/candidates/{$candidate->id}/consent", ['granted' => false])
            ->assertOk();
        $this->assertNull($candidate->fresh()->consent_rodo_at);
    }

    public function test_recruiter_cannot_forget_candidate(): void
    {
        $this->actingAsRecruiter();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        $this->deleteJson("/api/v1/candidates/{$candidate->id}/forget")
            ->assertForbidden();

        $this->assertDatabaseHas('candidates', ['id' => $candidate->id]);
    }

    public function test_admin_can_forget_candidate_and_purge_files(): void
    {
        $admin = $this->actingAsAdmin();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        Storage::disk('s3')->put('docs/forget.pdf', 'X');
        Document::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'path' => 'docs/forget.pdf',
        ]);

        $this->deleteJson("/api/v1/candidates/{$candidate->id}/forget")
            ->assertOk();

        $this->assertDatabaseMissing('candidates', ['id' => $candidate->id]);
        Storage::disk('s3')->assertMissing('docs/forget.pdf');

        // Ślad rozliczalności bez danych osobowych.
        $this->assertDatabaseHas('activities', [
            'subject_id' => $candidate->id,
            'event' => 'candidate_forgotten',
        ]);
    }

    public function test_recruiter_cannot_delete_company(): void
    {
        $this->actingAsRecruiter();
        $company = Company::factory()->for($this->tenant)->create();

        $this->deleteJson("/api/v1/companies/{$company->id}")->assertForbidden();
    }

    public function test_admin_can_delete_company(): void
    {
        $this->actingAsAdmin();
        $company = Company::factory()->for($this->tenant)->create();

        $this->deleteJson("/api/v1/companies/{$company->id}")->assertOk();
    }

    public function test_api_responses_carry_security_headers(): void
    {
        $this->actingAsRecruiter();

        $this->getJson('/api/v1/auth/me')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY');
    }
}
