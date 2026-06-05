<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Profiles\GenerateProfilePdfAction;
use App\Actions\Profiles\SendProfileAction;
use App\Enums\CompanyDecision;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profiles\SendProfileRequest;
use App\Http\Requests\Profiles\SetCompanyDecisionRequest;
use App\Http\Resources\ProfileSendResource;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\ProfileSend;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ProfileController extends Controller
{
    /**
     * Generuje i zwraca profil PDF (podgląd / pobranie).
     */
    public function pdf(Candidate $candidate, GenerateProfilePdfAction $action): Response
    {
        $pdf = $action->render($candidate);

        $name = 'profil-'.str()->slug($candidate->fullName()).'.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$name.'"',
        ]);
    }

    /**
     * Generuje PDF i zapisuje go na dysku dokumentów (zwraca ścieżkę).
     */
    public function generatePdf(Candidate $candidate, GenerateProfilePdfAction $action): JsonResponse
    {
        $path = $action->execute($candidate);

        return response()->json(['pdf_path' => $path]);
    }

    /**
     * Zleca wysyłkę profilu do klienta (kolejka).
     */
    public function send(
        SendProfileRequest $request,
        Candidate $candidate,
        SendProfileAction $action
    ): JsonResponse {
        $send = $action->execute($candidate, $request->validated(), $request->user());

        return (new ProfileSendResource($send))->response()->setStatusCode(202);
    }

    /**
     * Ustawia decyzję firmy po wysłaniu profilu i synchronizuje status aplikacji.
     */
    public function decision(SetCompanyDecisionRequest $request, ProfileSend $profileSend): ProfileSendResource
    {
        $decision = CompanyDecision::from($request->string('decision')->toString());

        $profileSend->forceFill([
            'decision' => $decision,
            'decision_at' => now(),
        ])->save();

        $profileSend->logActivity('decision', ['decision' => $decision->value]);

        // Synchronizacja statusu kandydata w ogłoszeniu.
        if (($appStatus = $decision->toApplicationStatus()) && $profileSend->job_posting_id) {
            Application::where('candidate_id', $profileSend->candidate_id)
                ->where('job_posting_id', $profileSend->job_posting_id)
                ->update(['status' => $appStatus]);
        }

        return new ProfileSendResource($profileSend->refresh());
    }
}
