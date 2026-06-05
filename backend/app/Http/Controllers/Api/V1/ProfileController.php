<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Profiles\GenerateProfilePdfAction;
use App\Actions\Profiles\SendProfileAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Profiles\SendProfileRequest;
use App\Http\Resources\ProfileSendResource;
use App\Models\Candidate;
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
}
