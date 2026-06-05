<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Rodo\ExportCandidateAction;
use App\Actions\Rodo\ForgetCandidateAction;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RodoController extends Controller
{
    /**
     * Eksport danych osobowych kandydata (RODO art. 15).
     */
    public function export(Candidate $candidate, ExportCandidateAction $action): JsonResponse
    {
        $candidate->logActivity('exported');

        return response()->json($action->execute($candidate));
    }

    /**
     * Aktualizacja zgody RODO (udzielenie / wycofanie).
     */
    public function consent(Request $request, Candidate $candidate): JsonResponse
    {
        $granted = $request->boolean('granted');

        $candidate->consent_rodo_at = $granted ? now() : null;
        $candidate->save();

        return response()->json([
            'consent_rodo_at' => $candidate->consent_rodo_at?->toIso8601String(),
        ]);
    }

    /**
     * Trwałe usunięcie kandydata i danych (RODO art. 17) — tylko administrator.
     */
    public function forget(
        Request $request,
        Candidate $candidate,
        ForgetCandidateAction $action
    ): JsonResponse {
        $this->authorize('forget', $candidate);

        $action->execute($candidate, $request->user()->id);

        return response()->json(['message' => 'Dane kandydata zostały trwale usunięte.']);
    }
}
