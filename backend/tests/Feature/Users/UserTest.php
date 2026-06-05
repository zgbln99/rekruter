<?php

namespace Tests\Feature\Users;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tenant = Tenant::factory()->create();
    }

    public function test_admin_can_create_user(): void
    {
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());

        $this->postJson('/api/v1/users', [
            'name' => 'Ania Rekruter',
            'email' => 'ania@rekruter.local',
            'password' => 'tajne123',
            'role' => 'recruiter',
        ])->assertCreated()->assertJsonPath('role', 'recruiter');

        $this->assertDatabaseHas('users', [
            'email' => 'ania@rekruter.local',
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_recruiter_cannot_access_users(): void
    {
        Sanctum::actingAs(User::factory()->for($this->tenant)->create()); // recruiter

        $this->getJson('/api/v1/users')->assertForbidden();
        $this->postJson('/api/v1/users', [
            'name' => 'X', 'email' => 'x@y.pl', 'password' => 'tajne123', 'role' => 'recruiter',
        ])->assertForbidden();
    }

    public function test_admin_can_update_user_role_and_password(): void
    {
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());
        $target = User::factory()->for($this->tenant)->create();

        $this->patchJson("/api/v1/users/{$target->id}", [
            'role' => 'admin',
            'password' => 'noweHaslo1',
        ])->assertOk()->assertJsonPath('role', 'admin');
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->for($this->tenant)->admin()->create();
        Sanctum::actingAs($admin);

        $this->deleteJson("/api/v1/users/{$admin->id}")->assertForbidden();
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());
        $target = User::factory()->for($this->tenant)->create();

        $this->deleteJson("/api/v1/users/{$target->id}")->assertOk();
    }

    public function test_email_must_be_unique_within_tenant(): void
    {
        Sanctum::actingAs(User::factory()->for($this->tenant)->admin()->create());
        User::factory()->for($this->tenant)->create(['email' => 'zajety@rekruter.local']);

        $this->postJson('/api/v1/users', [
            'name' => 'X', 'email' => 'zajety@rekruter.local', 'password' => 'tajne123', 'role' => 'recruiter',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }
}
