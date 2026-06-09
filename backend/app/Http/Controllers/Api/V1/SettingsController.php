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

        return response()->json($this->payload($tenant, $request->user()->isAdmin()));
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tenant = $request->user()->tenant;
        $settings = $tenant->settings ?? [];

        foreach (['agency_name', 'agency_phone', 'agency_email', 'agency_website', 'openai_model', 'placement_currency', 'careers_hero_image'] as $key) {
            $settings[$key] = $request->input($key);
        }

        // Stała kwota rozliczenia (pusto = brak / nie ustawiono).
        $fee = $request->input('placement_fee');
        $settings['placement_fee'] = ($fee === null || $fee === '') ? null : (float) $fee;

        // Szablony wiadomości (tylko z niepustą treścią).
        if ($request->has('message_templates')) {
            $settings['message_templates'] = collect($request->input('message_templates', []))
                ->filter(fn ($t) => ! empty($t['body']))
                ->map(fn ($t) => ['name' => $t['name'] ?? '', 'body' => $t['body']])
                ->values()
                ->all();
        }

        // Edytowalne teksty strony kariery (tylko znane klucze).
        if ($request->has('careers_texts')) {
            $allowed = array_keys(config('rekruter.careers_texts', []));
            $settings['careers_texts'] = collect($request->input('careers_texts', []))
                ->only($allowed)
                ->map(fn ($v) => is_string($v) ? trim($v) : '')
                ->all();
        }

        // Klucz API zapisujemy tylko gdy podano nowy (puste pole = bez zmian).
        if ($request->filled('openai_api_key')) {
            $settings['openai_api_key'] = $request->string('openai_api_key')->toString();
        }

        // Klucz Unsplash (Access Key) — tak samo, tylko gdy podano nowy.
        if ($request->filled('unsplash_key')) {
            $settings['unsplash_key'] = trim($request->string('unsplash_key')->toString());
        }

        $tenant->settings = $settings;
        $tenant->save();

        return response()->json($this->payload($tenant->refresh(), $request->user()->isAdmin()));
    }

    /** Losuje zdjęcie hero strony kariery (europejska ciężarówka z Unsplash). */
    public function randomHero(Request $request, \App\Actions\Offers\FetchTruckPhotoAction $action): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $tenant = $request->user()->tenant;

        $url = $action($tenant);
        abort_if($url === '', 422, 'Nie udało się pobrać zdjęcia.');

        $settings = $tenant->settings ?? [];
        $settings['careers_hero_image'] = $url;
        $tenant->settings = $settings;
        $tenant->save();

        return response()->json($this->payload($tenant->refresh(), true));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Tenant $tenant, bool $isAdmin = false): array
    {
        $s = $tenant->settings ?? [];

        $data = [
            'agency_name' => $tenant->agencyName(),
            'agency_phone' => $s['agency_phone'] ?? null,
            'agency_email' => $s['agency_email'] ?? null,
            'agency_website' => $s['agency_website'] ?? null,
            'careers_hero_image' => $s['careers_hero_image'] ?? null,
            'careers_hero_effective' => $tenant->careersHeroImage(),
            'careers_texts' => collect(config('rekruter.careers_texts', []))
                ->map(fn ($f, $k) => ['label' => $f['label'], 'type' => $f['type'], 'value' => $tenant->careersText($k)])
                ->all(),
            'openai_model' => $tenant->openaiModel(),
            // Klucza nie zwracamy — tylko informację, czy jest ustawiony.
            'openai_configured' => ! empty($s['openai_api_key']),
            // Unsplash — czy klucz jest dostępny (z ustawień albo env/configu).
            'unsplash_configured' => ! empty($tenant->unsplashKey()),
            // Szablony wiadomości (dla wszystkich — używa ich rekruterka).
            'message_templates' => $tenant->messageTemplates(),
            // Branding (co jest ustawione + wersja do cache-bustingu).
            'branding' => \App\Http\Controllers\Api\V1\BrandingController::payload($tenant),
        ];

        // Dane finansowe (stała kwota rozliczenia) — tylko dla administratora.
        if ($isAdmin) {
            $data['placement_fee'] = $tenant->placementFee();
            $data['placement_currency'] = $tenant->placementCurrency();
        }

        return $data;
    }
}
