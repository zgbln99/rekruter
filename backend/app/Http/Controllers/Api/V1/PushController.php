<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use App\Support\Push\WebPushService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Subskrypcje Web Push (VAPID) — rejestracja urządzeń do powiadomień push.
 */
class PushController extends Controller
{
    public function publicKey(WebPushService $push): JsonResponse
    {
        return response()->json([
            'enabled' => $push->configured(),
            'key' => config('webpush.vapid.public_key'),
        ]);
    }

    public function subscribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string'],
            'keys.p256dh' => ['required', 'string'],
            'keys.auth' => ['required', 'string'],
        ]);

        PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $request->user()->id,
                'public_key' => $data['keys']['p256dh'],
                'auth_token' => $data['keys']['auth'],
            ],
        );

        return response()->json(['ok' => true], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $endpoint = $request->string('endpoint')->toString();
        if ($endpoint !== '') {
            PushSubscription::where('endpoint', $endpoint)->delete();
        }

        return response()->json(['ok' => true]);
    }
}
