<?php

namespace App\Actions\Documents;

use App\Models\Candidate;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Zapisuje dokument kandydata w prywatnym S3 i tworzy rekord Document.
 * Dokumenty nigdy nie są publiczne (RODO — DESIGN.md sekcja 12).
 */
class StoreDocumentAction
{
    public function execute(Candidate $candidate, UploadedFile $file, string $type, User $user): Document
    {
        $disk = config('rekruter.documents_disk');

        // Czytelna ścieżka: kandydaci/{nazwisko-imie-id}/{typ}/{data}-{rand}.{ext}
        $path = sprintf(
            '%s/%s/%s-%s.%s',
            $candidate->storageFolder(),
            $type,
            now()->format('Ymd-His'),
            Str::lower(Str::random(4)),
            $file->getClientOriginalExtension() ?: 'bin'
        );

        Storage::disk($disk)->putFileAs(
            dirname($path),
            $file,
            basename($path),
            ['visibility' => 'private']
        );

        return Document::create([
            'candidate_id' => $candidate->id,
            'type' => $type,
            'disk' => $disk,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);
    }
}
