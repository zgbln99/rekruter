<?php

namespace Tests\Feature\Notes;

use App\Models\Note;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NoteTest extends TestCase
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
        // Bezpośrednie tworzenie modeli w teście potrzebuje kontekstu tenanta.
        $this->app->instance('currentTenantId', $this->tenant->id);
    }

    public function test_can_create_and_list_notes(): void
    {
        $this->postJson('/api/v1/notes', ['title' => 'Ważne', 'body' => 'Treść', 'pinned' => true])
            ->assertCreated()
            ->assertJsonPath('title', 'Ważne')
            ->assertJsonPath('pinned', true);

        $this->getJson('/api/v1/notes')
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_pinned_notes_come_first(): void
    {
        Note::create(['user_id' => $this->user->id, 'title' => 'Zwykła', 'pinned' => false]);
        Note::create(['user_id' => $this->user->id, 'title' => 'Przypięta', 'pinned' => true]);

        $this->getJson('/api/v1/notes')
            ->assertOk()
            ->assertJsonPath('0.title', 'Przypięta');
    }

    public function test_can_update_and_delete_own_note(): void
    {
        $note = Note::create(['user_id' => $this->user->id, 'title' => 'A']);

        $this->patchJson("/api/v1/notes/{$note->id}", ['title' => 'B'])
            ->assertOk()->assertJsonPath('title', 'B');

        $this->deleteJson("/api/v1/notes/{$note->id}")->assertOk();
        $this->assertSoftDeleted('notes', ['id' => $note->id]);
    }

    public function test_cannot_access_other_users_note(): void
    {
        $other = User::factory()->for($this->tenant)->create();
        $note = Note::create(['user_id' => $other->id, 'title' => 'Cudza']);

        $this->getJson('/api/v1/notes')->assertJsonCount(0);
        $this->patchJson("/api/v1/notes/{$note->id}", ['title' => 'Hack'])->assertNotFound();
        $this->deleteJson("/api/v1/notes/{$note->id}")->assertNotFound();
    }
}
