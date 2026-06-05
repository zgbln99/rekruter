<?php

namespace App\Actions\Documents;

use App\Enums\DocumentType;
use App\Models\Candidate;
use App\Models\Document;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Zapisuje wycięte (CropperJS) zdjęcie jako zdjęcie profilowe kandydata.
 * Tworzy osobny Document typu `photo` i ustawia candidate.profile_photo_id
 * (DESIGN.md reguła 5.4).
 */
class SaveProfilePhotoAction
{
    public function execute(Candidate $candidate, UploadedFile $image, User $user): Document
    {
        return DB::transaction(function () use ($candidate, $image, $user) {
            $path = sprintf(
                'tenants/%s/candidates/%s/photo/%s.%s',
                $candidate->tenant_id,
                $candidate->id,
                Str::uuid(),
                $image->getClientOriginalExtension() ?: 'jpg'
            );

            Storage::disk('s3')->putFileAs(
                dirname($path),
                $image,
                basename($path),
                ['visibility' => 'private']
            );

            $document = Document::create([
                'candidate_id' => $candidate->id,
                'type' => DocumentType::Photo,
                'disk' => 's3',
                'path' => $path,
                'original_name' => $image->getClientOriginalName() ?: 'profile.jpg',
                'mime' => $image->getMimeType() ?: 'image/jpeg',
                'size' => $image->getSize(),
                'is_profile_photo' => true,
                'uploaded_by' => $user->id,
            ]);

            $candidate->profile_photo_id = $document->id;
            $candidate->save();

            return $document;
        });
    }
}
