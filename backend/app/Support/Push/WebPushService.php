<?php

namespace App\Support\Push;

use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Wysyłka powiadomień Web Push (VAPID). Gdy klucze nie są skonfigurowane,
 * metody są no-op — push jest opcjonalny.
 */
class WebPushService
{
    public function configured(): bool
    {
        return filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'));
    }

    public function sendToUser(User $user, string $title, string $body, string $url = '/'): void
    {
        if (! $this->configured()) {
            return;
        }

        $subs = PushSubscription::withoutGlobalScopes()->where('user_id', $user->id)->get();
        if ($subs->isEmpty()) {
            return;
        }

        try {
            $webPush = new WebPush(['VAPID' => [
                'subject' => config('webpush.vapid.subject'),
                'publicKey' => config('webpush.vapid.public_key'),
                'privateKey' => config('webpush.vapid.private_key'),
            ]]);

            $payload = json_encode(['title' => $title, 'body' => $body, 'url' => $url]);

            foreach ($subs as $s) {
                $webPush->queueNotification(
                    Subscription::create([
                        'endpoint' => $s->endpoint,
                        'keys' => ['p256dh' => $s->public_key, 'auth' => $s->auth_token],
                    ]),
                    $payload
                );
            }

            foreach ($webPush->flush() as $report) {
                if (! $report->isSuccess()) {
                    $code = $report->getResponse()?->getStatusCode();
                    if (in_array($code, [404, 410], true)) {
                        PushSubscription::withoutGlobalScopes()->where('endpoint', $report->getEndpoint())->delete();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('Web Push: '.$e->getMessage());
        }
    }
}
