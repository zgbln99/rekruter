<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $email = 'ania@rekruter.local', string $password = 'tajne123'): User
    {
        $tenant = Tenant::factory()->create();

        return User::factory()->for($tenant)->create([
            'email' => $email,
            'password' => Hash::make($password),
            'role' => UserRole::Recruiter,
        ]);
    }

    public function test_user_can_login_and_receive_token(): void
    {
        $this->makeUser();

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'ania@rekruter.local',
            'password' => 'tajne123',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['id', 'name', 'email', 'role', 'role_label', 'tenant_id'],
            ]);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $this->makeUser();

        $this->postJson('/api/v1/auth/login', [
            'email' => 'ania@rekruter.local',
            'password' => 'zle-haslo',
        ])->assertStatus(422);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertStatus(401);
    }

    public function test_authenticated_user_can_fetch_profile(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('email', 'ania@rekruter.local')
            ->assertJsonPath('role', 'recruiter');
    }

    public function test_user_can_logout(): void
    {
        $user = $this->makeUser();
        $token = $user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/auth/logout')
            ->assertOk();
    }
}
