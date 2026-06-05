<?php

namespace App\Actions\Rodo;

use App\Models\Activity;
use App\Models\Candidate;
use App\Models\ProfileSend;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Trwałe usunięcie kandydata i wszystkich powiązanych danych osobowych
 * (RODO — prawo do bycia zapomnianym, art. 17).
 *
 * - usuwa pliki z S3 (dokumenty + wygenerowane profile PDF),
 * - kasuje twardo rekordy powiązane (FK ON DELETE CASCADE),
 * - usuwa wpisy audit logu zawierające dane osobowe,
 * - pozostawia beznazwowy ślad rozliczalności (id + kto + kiedy).
 */
class ForgetCandidateAction
{
    public function execute(Candidate $candidate, ?string $byUserId = null): void
    {
        DB::transaction(function () use ($candidate, $byUserId) {
            // 1. Pliki w S3: dokumenty kandydata.
            foreach ($candidate->documents()->withTrashed()->get() as $document) {
                Storage::disk($document->disk)->delete($document->path);
            }

            // 2. Pliki w S3: wygenerowane profile PDF.
            $pdfPaths = ProfileSend::where('candidate_id', $candidate->id)
                ->whereNotNull('pdf_path')
                ->pluck('pdf_path');
            foreach ($pdfPaths as $path) {
                Storage::disk('s3')->delete($path);
            }

            // 3. Audit log z danymi osobowymi tego kandydata.
            Activity::where('subject_type', $candidate->getMorphClass())
                ->where('subject_id', $candidate->id)
                ->delete();

            $tenantId = $candidate->tenant_id;
            $candidateId = $candidate->id;

            // 4. Twarde usunięcie kandydata (kaskada usuwa dzieci na poziomie FK).
            $candidate->forceDelete();

            // 5. Beznazwowy ślad rozliczalności (bez danych osobowych).
            Activity::create([
                'tenant_id' => $tenantId,
                'user_id' => $byUserId,
                'subject_type' => 'rodo',
                'subject_id' => $candidateId,
                'event' => 'candidate_forgotten',
                'changes' => null,
            ]);
        });
    }
}
