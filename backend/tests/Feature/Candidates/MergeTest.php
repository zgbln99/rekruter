<?php

namespace Tests\Feature\Candidates;

use App\Models\Candidate;
use App\Models\ContactLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MergeTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
        Sanctum::actingAs(User::factory()->for($this->tenant)->create());
    }

    public function test_merge_moves_relations_fills_fields_and_soft_deletes_source(): void
    {
        $target = Candidate::factory()->for($this->tenant)->create(['email' => null, 'city' => null]);
        $source = Candidate::factory()->for($this->tenant)->create(['email' => 'jan@x.pl', 'city' => 'Kraków']);

        $log = ContactLog::factory()->for($this->tenant)->for($source)->create();

        $this->postJson("/api/v1/candidates/{$target->id}/merge", ['source_id' => $source->id])
            ->assertOk()
            ->assertJsonPath('id', $target->id);

        // Pola uzupełnione z duplikatu.
        $target->refresh();
        $this->assertSame('jan@x.pl', $target->email);
        $this->assertSame('Kraków', $target->city);

        // Kontakt przeniesiony na docelowego.
        $this->assertSame($target->id, $log->fresh()->candidate_id);

        // Źródło usunięte (soft delete).
        $this->assertNull(Candidate::find($source->id));
        $this->assertNotNull(Candidate::withTrashed()->find($source->id));
    }

    public function test_cannot_merge_candidate_into_itself(): void
    {
        $c = Candidate::factory()->for($this->tenant)->create();

        $this->postJson("/api/v1/candidates/{$c->id}/merge", ['source_id' => $c->id])
            ->assertStatus(422);
    }
}
