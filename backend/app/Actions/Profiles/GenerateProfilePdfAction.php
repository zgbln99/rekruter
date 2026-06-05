<?php

namespace App\Actions\Profiles;

use App\Models\Candidate;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Generuje profesjonalny profil kandydata w PDF (Gotenberg) i zapisuje go w S3.
 *
 * PDF NIE zawiera notatek wewnętrznych ani danych nadmiarowych
 * (minimalizacja danych — DESIGN.md sekcje 7.3 i 12).
 */
class GenerateProfilePdfAction
{
    /**
     * Renderuje PDF i zwraca jego zawartość binarną (podgląd / pobranie).
     */
    public function render(Candidate $candidate): string
    {
        $html = View::make('pdf.profile', [
            'candidate' => $candidate,
            'photoDataUri' => $this->photoDataUri($candidate),
            'agencyName' => config('app.name'),
            'generatedAt' => now()->format('d.m.Y'),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }

    /**
     * Renderuje PDF i zapisuje go w prywatnym S3, zwracając ścieżkę (wysyłka).
     */
    public function execute(Candidate $candidate): string
    {
        $pdf = $this->render($candidate);

        $path = sprintf(
            'tenants/%s/candidates/%s/profiles/%s.pdf',
            $candidate->tenant_id,
            $candidate->id,
            Str::uuid()
        );

        Storage::disk('s3')->put($path, $pdf, ['visibility' => 'private']);

        return $path;
    }

    /**
     * Wczytuje zdjęcie profilowe z S3 i zwraca jako data-URI (do osadzenia w HTML).
     */
    private function photoDataUri(Candidate $candidate): ?string
    {
        $photo = $candidate->profilePhoto;
        if ($photo === null) {
            return null;
        }

        $disk = Storage::disk($photo->disk);
        if (! $disk->exists($photo->path)) {
            return null;
        }

        $mime = $photo->mime ?: 'image/jpeg';

        return 'data:'.$mime.';base64,'.base64_encode($disk->get($photo->path));
    }
}
