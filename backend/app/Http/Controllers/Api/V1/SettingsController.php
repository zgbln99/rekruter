<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    /**
     * Ustawienia organizacji (nazwa agencji itp.). Odczyt dla zalogowanych,
     * zapis tylko dla administratora.
     */
    public function show(Request $request): JsonResponse
    {
        $tenant = $request->user()->tenant;

        return response()->json($this->payload($tenant));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tenant = $request->user()->tenant;
        $settings = $tenant->settings ?? [];

        foreach (['agency_name', 'agency_phone', 'agency_email', 'agency_website', 'openai_model'] as $key) {
            $settings[$key] = $request->input($key);
        }

        // Klucz API zapisujemy tylko gdy podano nowy (puste pole = bez zmian).
        if ($request->filled('openai_api_key')) {
            $settings['openai_api_key'] = $request->string('openai_api_key')->toString();
        }

        $tenant->settings = $settings;
        $tenant->save();

        return response()->json($this->payload($tenant->refresh()));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Tenant $tenant): array
    {
        $s = $tenant->settings ?? [];

        return [
            'agency_name' => $tenant->agencyName(),
            'agency_phone' => $s['agency_phone'] ?? null,
            'agency_email' => $s['agency_email'] ?? null,
            'agency_website' => $s['agency_website'] ?? null,
            'openai_model' => $tenant->openaiModel(),
            // Klucza nie zwracamy — tylko informację, czy jest ustawiony.
            'openai_configured' => ! empty($s['openai_api_key']),
        ];
    }
}
