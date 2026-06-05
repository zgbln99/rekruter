<?php

namespace Tests\Feature\Pipeline;

use App\Actions\Pipeline\EnsurePipelineStagesAction;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\PipelineStage;
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
        app(EnsurePipelineStagesAction::class)->execute($this->tenant);
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

    public function test_default_pipeline_stages_are_seeded(): void
    {
        $this->getJson('/api/v1/pipeline-stages')
            ->assertOk()
            ->assertJsonCount(6);
    }

    public function test_can_add_candidate_to_pipeline_in_first_stage(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $firstStage = PipelineStage::orderBy('position')->first();

        $response = $this->postJson('/api/v1/applications', [
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
        ]);

        $response->assertCreated()->assertJsonPath('stage_id', $firstStage->id);
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

    public function test_can_move_application_between_stages(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $stages = PipelineStage::orderBy('position')->get();

        $application = Application::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
            'stage_id' => $stages->first()->id,
        ]);

        $target = $stages->get(1);

        $this->patchJson("/api/v1/applications/{$application->id}", [
            'stage_id' => $target->id,
        ])->assertOk()->assertJsonPath('stage_id', $target->id);

        // Przeniesienie zostało zapisane w audit logu.
        $this->assertDatabaseHas('activities', [
            'subject_id' => $application->id,
            'event' => 'moved',
        ]);
    }

    public function test_pipeline_board_groups_applications_by_stage(): void
    {
        $posting = $this->jobPosting();
        $candidate = Candidate::factory()->for($this->tenant)->create();
        $firstStage = PipelineStage::orderBy('position')->first();

        Application::factory()->for($this->tenant)->create([
            'candidate_id' => $candidate->id,
            'job_posting_id' => $posting->id,
            'stage_id' => $firstStage->id,
        ]);

        $this->getJson("/api/v1/job-postings/{$posting->id}/pipeline")
            ->assertOk()
            ->assertJsonPath('stages.0.applications.0.candidate_id', $candidate->id)
            ->assertJsonCount(6, 'stages');
    }
}
