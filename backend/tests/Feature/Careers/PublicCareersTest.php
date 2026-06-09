<?php

namespace Tests\Feature\Careers;

use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCareersTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create(['settings' => ['agency_name' => 'Test Agencja', 'agency_phone' => '+48 600 100 200']]);
    }

    public function test_listing_shows_only_published_offers(): void
    {
        $published = JobPosting::factory()->for($this->tenant)->create([
            'title' => 'Kierowca C+E Niemcy', 'status' => 'open', 'is_public' => true,
        ]);
        $hidden = JobPosting::factory()->for($this->tenant)->create([
            'title' => 'Ukryta oferta', 'status' => 'open', 'is_public' => false,
        ]);

        $this->get('/kariera')
            ->assertOk()
            ->assertSee('Kierowca C+E Niemcy')
            ->assertDontSee('Ukryta oferta');
    }

    public function test_unpublished_offer_detail_returns_404(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'title' => 'Tajna', 'status' => 'open', 'is_public' => false,
        ]);

        $this->get($offer->publicPath())->assertNotFound();
    }

    public function test_published_offer_detail_renders_with_jsonld(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'title' => 'Kierowca C+E', 'status' => 'open', 'is_public' => true,
            'country' => 'Niemcy', 'public_description' => 'Fajna praca dla kierowcy.',
        ]);

        $this->get($offer->publicPath())
            ->assertOk()
            ->assertSee('Kierowca C+E')
            ->assertSee('application/ld+json', false)
            ->assertSee('JobPosting', false);
    }

    public function test_wrong_slug_redirects_to_canonical(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'title' => 'Kierowca C+E', 'status' => 'open', 'is_public' => true,
        ]);

        $this->get('/kariera/zly-slug/'.$offer->id)
            ->assertRedirect($offer->publicPath());
    }

    public function test_application_creates_candidate_and_attaches_to_offer(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'status' => 'open', 'is_public' => true,
        ]);

        $this->post(route('careers.apply', ['jobPosting' => $offer->id]), [
            'first_name' => 'Jan',
            'last_name' => 'Nowak',
            'phone' => '600 100 200',
            'categories' => ['C+E'],
            'message' => 'Mam 5 lat doświadczenia.',
            'consent' => '1',
        ])->assertRedirect();

        $candidate = Candidate::where('phone_normalized', '+48600100200')->first();
        $this->assertNotNull($candidate);
        $this->assertSame('Strona kariery', $candidate->source);
        $this->assertNotNull($candidate->consent_rodo_at);
        $this->assertDatabaseHas('applications', [
            'candidate_id' => $candidate->id,
            'job_posting_id' => $offer->id,
        ]);
    }

    public function test_application_requires_consent(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'status' => 'open', 'is_public' => true,
        ]);

        $this->post(route('careers.apply', ['jobPosting' => $offer->id]), [
            'first_name' => 'Jan',
            'last_name' => 'Nowak',
            'phone' => '600 100 200',
        ])->assertSessionHasErrors('consent');

        $this->assertDatabaseCount('candidates', 0);
    }

    public function test_honeypot_blocks_bot_without_creating_candidate(): void
    {
        $offer = JobPosting::factory()->for($this->tenant)->create([
            'status' => 'open', 'is_public' => true,
        ]);

        $this->post(route('careers.apply', ['jobPosting' => $offer->id]), [
            'first_name' => 'Bot',
            'last_name' => 'Spam',
            'phone' => '600 100 200',
            'consent' => '1',
            'company' => 'spam-corp',
        ])->assertRedirect();

        $this->assertDatabaseCount('candidates', 0);
    }
}
