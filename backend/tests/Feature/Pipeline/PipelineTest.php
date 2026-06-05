<?php

namespace Tests\Feature\Pipeline;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($this->tenant)->create();
        Sanctum::actingAs($this->user);
    }

    private function jobPosting(): JobPosting
    {
        $company = Company::factory()->for($this->tenant)->create();

        return JobPosting::factory()->for($this->tenant)->for($company)->create();
    }

    public function test_can_create_company(): void
    {
        $this->postJson('/api/v1/companies', ['name' => 'LTS Logistik'])
            ->assertCreated()
            ->assertJsonPath('name', 'LTS Logistik');

        $this->assertDatabaseHas('companies', [
            'name' => 'LTS Logistik',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_can_create_job_posting_for_company(): void
    {
        $company = Company::factory()->for($this->tenant)->create();

        $this->postJson('/api/v1/job-postings', [
            'company_id' => $company->id,
            'title' => 'Kierowca C+E',
            'required_categories' => ['C+E'],
        ])->assertCreated()->assertJsonPath('title', 'Kierowca C+E');
    }

    public function test_can_add_candidate_to_pipeline_with_new_status(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        $this->postJson('/api/v1/applications', [
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
        ])->assertCreated()->assertJsonPath('status', ApplicationStatus::New->value);
    }

    public function test_candidate_cannot_be_added_twice_to_same_posting(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        $payload = ['candidate_id' => $candidate->id, 'job_posting_id' => $posting->id];
        $this->postJson('/api/v1/applications', $payload)->assertCreated();
        $this->postJson('/api/v1/applications', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['candidate_id']);
    }

    public function test_can_move_application_between_statuses(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        $application = Application::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->patchJson("/api/v1/applications/{$application->id}", [
            'status' => ApplicationStatus::Interested->value,
        ])->assertOk()->assertJsonPath('status', ApplicationStatus::Interested->value);

        $this->assertDatabaseHas('activities', [
            'subject_id' => $application->id,
            'event' => 'status_changed',
        ]);
    }

    public function test_pipeline_board_groups_applications_by_status(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();

        Application::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
            'status' => ApplicationStatus::New,
        ]);

        $this->getJson("/api/v1/job-offers/{$posting->id}/pipeline")
            ->assertOk()
            ->assertJsonPath('stages.0.applications.0.candidate_id', $candidate->id)
            ->assertJsonCount(count(ApplicationStatus::cases()), 'stages');
    }
}
