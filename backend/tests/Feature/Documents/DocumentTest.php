<?php

namespace Tests\Feature\Documents;

use App\Models\Candidate;
use App\Models\Document;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentTest extends TestCase
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
        $this->candidate = Candidate::factory()->for($this->tenant)->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_upload_document_to_private_storage(): void
    {
        $file = UploadedFile::fake()->create('prawo-jazdy.pdf', 200, 'application/pdf');

        $response = $this->postJson("/api/v1/candidates/{$this->candidate->id}/documents", [
            'type' => 'driving_license',
            'file' => $file,
        ]);

        $response->assertCreated()->assertJsonPath('type', 'driving_license');

        $path = $response->json('id');
        $document = $this->candidate->documents()->first();
        $this->assertNotNull($document);
        Storage::disk('s3')->assertExists($document->path);

        // Audit log: utworzenie dokumentu.
        $this->assertDatabaseHas('activities', [
            'subject_id' => $document->id,
            'event' => 'created',
        ]);
    }

    public function test_cropped_profile_photo_is_set_on_candidate(): void
    {
        $photo = UploadedFile::fake()->image('crop.jpg', 300, 300);

        $response = $this->postJson(
            "/api/v1/candidates/{$this->candidate->id}/profile-photo",
            ['photo' => $photo]
        );

        $response->assertCreated()
            ->assertJsonPath('is_profile_photo', true)
            ->assertJsonPath('type', 'photo');

        $this->candidate->refresh();
        $this->assertNotNull($this->candidate->profile_photo_id);
        $this->assertSame($response->json('id'), $this->candidate->profile_photo_id);
    }

    public function test_document_upload_validates_type(): void
    {
        $file = UploadedFile::fake()->create('x.pdf', 10, 'application/pdf');

        $this->postJson("/api/v1/candidates/{$this->candidate->id}/documents", [
            'type' => 'invalid_type',
            'file' => $file,
        ])->assertStatus(422)->assertJsonValidationErrors(['type']);
    }

    public function test_can_download_document(): void
    {
        Storage::disk('s3')->put('docs/test.pdf', 'PDF-CONTENT');

        $document = Document::factory()->for($this->tenant)->create([
            'candidate_id' => $this->candidate->id,
            'type' => 'cv',
            'disk' => 's3',
            'path' => 'docs/test.pdf',
            'original_name' => 'cv.pdf',
            'mime' => 'application/pdf',
            'size' => 11,
        ]);

        $this->get("/api/v1/candidates/{$this->candidate->id}/documents/{$document->id}/download")
            ->assertOk();

        $this->assertDatabaseHas('activities', [
            'subject_id' => $document->id,
            'event' => 'downloaded',
        ]);
    }
}
