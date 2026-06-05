<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Logowanie — zwraca token Sanctum oraz dane użytkownika.
     *
     * Uwaga: lookup użytkownika odbywa się bez kontekstu tenanta
     * (TenantScope nie filtruje, gdy currentTenantId nie jest związany),
     * dzięki czemu możliwe jest zalogowanie przed ustaleniem tenanta.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email'))->first();

        if (! $user || ! Hash::check($request->string('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Nieprawidłowy e-mail lub hasło.'],
            ]);
        }

        $user->forceFill(['last_login_at' => now()])->save();

        $deviceName = $request->string('device_name')->toString() ?: 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    /**
     * Dane bieżącego użytkownika.
     */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    /**
     * Wylogowanie — usuwa bieżący token dostępu.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Wylogowano.']);
    }
}
