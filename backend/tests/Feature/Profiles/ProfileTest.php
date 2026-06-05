<?php

namespace Tests\Feature\Profiles;

use App\Enums\ProfileSendStatus;
use App\Mail\ProfileMail;
use App\Models\Candidate;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    private Candidate $candidate;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('s3');
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        $this->candidate = Candidate::factory()->for($this->tenant)->create([
            'first_name' => 'Jan',
            'last_name' => 'Kowalski',
            'license_categories' => ['C+E'],
            'has_adr' => true,
        ]);
        Sanctum::actingAs($this->user);
    }

    public function test_pdf_endpoint_returns_pdf(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake', 200),
        ]);

        $this->get("/api/v1/candidates/{$this->candidate->id}/profile-pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_send_profile_queues_and_sends_email(): void
    {
        Mail::fake();
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake', 200),
        ]);

        $response = $this->postJson(
            "/api/v1/candidates/{$this->candidate->id}/profile-send",
            ['recipient_email' => 'klient@firma.pl']
        );

        $response->assertStatus(202);

        // Kolejka sync w testach → job wykonany inline.
        Mail::assertSent(ProfileMail::class);

        $this->assertDatabaseHas('profile_sends', [
            'candidate_id' => $this->candidate->id,
            'recipient_email' => 'klient@firma.pl',
            'status' => ProfileSendStatus::Sent->value,
        ]);
    }

    public function test_send_profile_requires_valid_email(): void
    {
        $this->postJson(
            "/api/v1/candidates/{$this->candidate->id}/profile-send",
            ['recipient_email' => 'nieprawidlowy']
        )->assertStatus(422)->assertJsonValidationErrors(['recipient_email']);
    }

    public function test_generate_pdf_stores_file_on_documents_disk(): void
    {
        Http::fake([
            '*/forms/chromium/convert/html' => Http::response('%PDF-1.4 fake', 200),
        ]);

        $response = $this->postJson("/api/v1/candidates/{$this->candidate->id}/generate-pdf")
            ->assertOk();

        $path = $response->json('pdf_path');
        $this->assertNotNull($path);
        \Illuminate\Support\Facades\Storage::disk(config('rekruter.documents_disk'))->assertExists($path);
    }

    public function test_company_decision_updates_application_status(): void
    {
        $company = \App\Models\Company::factory()->for($this->tenant)->create();
        $offer = \App\Models\JobPosting::factory()->for($this->tenant)->for($company)->create();
        $application = \App\Models\Application::factory()->for($this->tenant)->create([
            'candidate_id' => $this->candidate->id,
            'job_posting_id' => $offer->id,
            'status' => \App\Enums\ApplicationStatus::SentToCompany,
        ]);
        $send = \App\Models\ProfileSend::factory()->for($this->tenant)->create([
            'candidate_id' => $this->candidate->id,
            'job_posting_id' => $offer->id,
            'company_id' => $company->id,
            'recipient_email' => 'klient@firma.pl',
        ]);

        $this->patchJson("/api/v1/profile-sends/{$send->id}/decision", ['decision' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('decision', 'accepted');

        $this->assertSame(
            \App\Enums\ApplicationStatus::AcceptedByCompany,
            $application->fresh()->status
        );
    }
}
