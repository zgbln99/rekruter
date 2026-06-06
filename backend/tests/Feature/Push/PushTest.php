<?php

namespace Tests\Feature\Push;

use App\Models\PushSubscription;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PushTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $tenant = Tenant::factory()->create();
        $this->user = User::factory()->for($tenant)->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_subscribe_and_unsubscribe(): void
    {
        $payload = [
            'endpoint' => 'https://push.example.com/abc123',
            'keys' => ['p256dh' => 'pkey', 'auth' => 'akey'],
        ];

        $this->postJson('/api/v1/push/subscribe', $payload)->assertCreated();

        $this->assertDatabaseHas('push_subscriptions', [
            'endpoint' => 'https://push.example.com/abc123',
            'user_id' => $this->user->id,
        ]);

        // Ponowna subskrypcja tego samego endpointu nie tworzy duplikatu.
        $this->postJson('/api/v1/push/subscribe', $payload)->assertCreated();
        $this->assertSame(1, PushSubscription::count());

        $this->deleteJson('/api/v1/push/unsubscribe', ['endpoint' => $payload['endpoint']])->assertOk();
        $this->assertSame(0, PushSubscription::count());
    }

    public function test_public_key_reports_disabled_without_config(): void
    {
        $this->getJson('/api/v1/push/public-key')
            ->assertOk()
            ->assertJsonPath('enabled', false);
    }
}
