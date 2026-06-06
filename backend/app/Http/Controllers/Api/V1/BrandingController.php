<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Branding aplikacji: logo, ikona, favicon — wgrywane w panelu przez administratora.
 * Pliki serwowane publicznie (bez auth), bo favicon/logo ładuje przeglądarka.
 * Aplikacja jednoagencyjna → branding bierzemy z pierwszego tenanta.
 */
class BrandingController extends Controller
{
    private const TYPES = ['logo', 'icon', 'favicon'];

    /** Upload jednego lub kilku elementów brandingu (admin). */
    public function upload(Request $request): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);

        $request->validate([
            'logo' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'icon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:png,jpg,jpeg,webp,svg,ico', 'max:2048'],
        ]);

        $tenant = $request->user()->tenant;
        $branding = $tenant->branding();
        $disk = Storage::disk(config('rekruter.documents_disk'));

        foreach (self::TYPES as $type) {
            if (! $request->hasFile($type)) {
                continue;
            }
            $file = $request->file($type);
            $ext = strtolower($file->getClientOriginalExtension() ?: 'png');
            $path = 'branding/'.$tenant->id.'/'.$type.'.'.$ext;
            $disk->put($path, file_get_contents($file->getRealPath()));
            $branding[$type] = ['path' => $path, 'mime' => $file->getMimeType() ?: 'image/png'];
        }

        $branding['v'] = now()->timestamp;
        $settings = $tenant->settings ?? [];
        $settings['branding'] = $branding;
        $tenant->settings = $settings;
        $tenant->save();

        return response()->json($this->payload($tenant->refresh()));
    }

    /** Usuń element brandingu (admin). */
    public function destroy(Request $request, string $type): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403);
        abort_unless(in_array($type, self::TYPES, true), 404);

        $tenant = $request->user()->tenant;
        $branding = $tenant->branding();

        if (isset($branding[$type]['path'])) {
            Storage::disk(config('rekruter.documents_disk'))->delete($branding[$type]['path']);
            unset($branding[$type]);
            $branding['v'] = now()->timestamp;
            $settings = $tenant->settings ?? [];
            $settings['branding'] = $branding;
            $tenant->settings = $settings;
            $tenant->save();
        }

        return response()->json($this->payload($tenant->refresh()));
    }

    /** Publiczne serwowanie pliku (bez auth) — favicon/logo ładuje przeglądarka. */
    public function show(string $type): Response
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $tenant = Tenant::query()->withoutGlobalScopes()->first();
        $entry = $tenant?->branding()[$type] ?? null;
        abort_if(! $entry || empty($entry['path']), 404);

        $disk = Storage::disk(config('rekruter.documents_disk'));
        abort_unless($disk->exists($entry['path']), 404);

        return response($disk->get($entry['path']), 200, [
            'Content-Type' => $entry['mime'] ?? 'image/png',
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /**
     * Info o brandingu do ustawień (co jest ustawione + wersja do cache-bustingu).
     *
     * @return array<string, mixed>
     */
    public static function payload(Tenant $tenant): array
    {
        $b = $tenant->branding();

        return [
            'logo' => ! empty($b['logo']['path']),
            'icon' => ! empty($b['icon']['path']),
            'favicon' => ! empty($b['favicon']['path']),
            'v' => $b['v'] ?? 0,
        ];
    }
}
