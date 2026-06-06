<?php

namespace Tests\Feature\Push;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_runs_without_vapid(): void
    {
        config(['webpush.vapid.public_key' => null, 'webpush.vapid.private_key' => null]);

        $this->artisan('rekruter:send-reminders')->assertSuccessful();
    }
}
