<?php

namespace App\Actions\Profiles;

use App\Models\Candidate;
use App\Models\JobPosting;
use App\Support\Pdf\GotenbergClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

/**
 * Generuje profesjonalny profil kandydata w PDF (Gotenberg) i zapisuje go
 * na dysku dokumentów (MEGA S3 w prod).
 *
 * PDF NIE zawiera notatek wewnętrznych ani danych nadmiarowych
 * (minimalizacja danych — DESIGN.md sekcje 7.3, 12 i 19.13).
 */
class GenerateProfilePdfAction
{
    /**
     * Renderuje PDF i zwraca jego zawartość binarną (podgląd / pobranie).
     * Opcjonalnie w kontekście konkretnego ogłoszenia (firma docelowa, oferta).
     */
    public function render(Candidate $candidate, ?JobPosting $offer = null): string
    {
        $offer ??= $this->resolveOffer($candidate);

        $html = View::make('pdf.profile', [
            'candidate' => $candidate,
            'offer' => $offer,
            'company' => $offer?->company,
            'photoDataUri' => $this->photoDataUri($candidate),
            'agencyName' => config('app.name'),
            'generatedAt' => now()->format('d.m.Y'),
        ])->render();

        return GotenbergClient::make()->htmlToPdf($html);
    }

    /**
     * Renderuje PDF i zapisuje go na dysku dokumentów, zwracając ścieżkę.
     */
    public function execute(Candidate $candidate, ?JobPosting $offer = null): string
    {
        $pdf = $this->render($candidate, $offer);

        $path = sprintf(
            '%s/profil-pdf/%s.pdf',
            $candidate->storageFolder(),
            now()->format('Ymd-His')
        );

        Storage::disk(config('rekruter.documents_disk'))->put($path, $pdf, ['visibility' => 'private']);

        $candidate->logActivity('pdf_generated');

        return $path;
    }

    /**
     * Najświeższe przypisane ogłoszenie kandydata (firma docelowa w PDF).
     */
    private function resolveOffer(Candidate $candidate): ?JobPosting
    {
        $application = $candidate->applications()->latest()->first();

        return $application
            ? JobPosting::with('company')->find($application->job_posting_id)
            : null;
    }

    /**
     * Wczytuje zdjęcie profilowe i zwraca jako data-URI (do osadzenia w HTML).
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
