<?php

namespace Tests\Feature\Branding;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_uploads_logo_and_it_is_served_publicly(): void
    {
        Storage::fake(config('rekruter.documents_disk'));
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->admin()->create());

        $this->postJson('/api/v1/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png', 200, 80),
        ])->assertOk()->assertJsonPath('logo', true);

        // Publiczny endpoint (bez auth) zwraca plik.
        $this->get('/api/v1/branding/logo')
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_recruiter_cannot_upload_branding(): void
    {
        $tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($tenant)->create());

        $this->postJson('/api/v1/settings/branding', [
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertForbidden();
    }

    public function test_missing_branding_returns_404(): void
    {
        Tenant::factory()->create();
        $this->get('/api/v1/branding/favicon')->assertNotFound();
    }

    public function test_dynamic_manifest_is_public_and_has_icons(): void
    {
        Tenant::factory()->create(['settings' => ['agency_name' => 'LTS Logistik']]);

        $this->get('/api/v1/manifest.webmanifest')
            ->assertOk()
            ->assertJsonPath('name', 'LTS Logistik')
            ->assertJsonStructure(['name', 'short_name', 'icons']);
    }
}
