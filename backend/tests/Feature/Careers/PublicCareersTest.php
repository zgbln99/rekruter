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

    public function test_privacy_page_renders(): void
    {
        $this->get('/polityka-prywatnosci')
            ->assertOk()
            ->assertSee('Polityka prywatności')
            ->assertSee('Administrator danych');
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

    public function test_callback_creates_lead_candidate_with_follow_up_task(): void
    {
        $this->post(route('careers.callback'), [
            'name' => 'Marek',
            'phone' => '600 700 800',
            'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseHas('candidates', [
            'first_name' => 'Marek',
            'source' => 'Strona kariery - prośba o kontakt',
        ]);

        $candidate = Candidate::query()->first();
        $this->assertDatabaseHas('tasks', [
            'candidate_id' => $candidate->id,
            'status' => 'open',
            'title' => 'Oddzwonić - prośba ze strony kariery',
        ]);
    }

    public function test_callback_reuses_existing_candidate_by_phone_but_adds_task(): void
    {
        $existing = Candidate::factory()->for($this->tenant)->create([
            'phone' => '+48 600 700 800',
            'phone_normalized' => \App\Support\PhoneNumber::normalize('+48 600 700 800'),
        ]);

        $this->post(route('careers.callback'), [
            'phone' => '600 700 800',
            'consent' => '1',
        ])->assertRedirect();

        $this->assertDatabaseCount('candidates', 1);
        $this->assertDatabaseHas('tasks', ['candidate_id' => $existing->id, 'status' => 'open']);
    }

    public function test_callback_honeypot_creates_nothing(): void
    {
        $this->post(route('careers.callback'), [
            'phone' => '600 700 800',
            'consent' => '1',
            'company' => 'spam-corp',
        ])->assertRedirect();

        $this->assertDatabaseCount('candidates', 0);
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_callback_requires_consent_and_phone(): void
    {
        $this->post(route('careers.callback'), ['name' => 'Jan'])
            ->assertSessionHasErrors(['phone', 'consent']);

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
