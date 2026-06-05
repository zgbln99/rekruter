<?php

namespace App\Jobs;

use App\Actions\Profiles\GenerateProfilePdfAction;
use App\Enums\ProfileSendStatus;
use App\Mail\ProfileMail;
use App\Models\ProfileSend;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Generuje PDF (jeśli brak), wysyła profil do klienta i aktualizuje status.
 * Ciężka operacja I/O — wykonywana w kolejce (DESIGN.md ADR-4).
 */
class SendProfileEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public ProfileSend $profileSend) {}

    public function handle(GenerateProfilePdfAction $generatePdf): void
    {
        $send = $this->profileSend;
        $candidate = $send->candidate;

        try {
            if (empty($send->pdf_path)) {
                $send->pdf_path = $generatePdf->execute($candidate);
                $send->save();
            }

            $pdf = Storage::disk('s3')->get($send->pdf_path);

            Mail::to($send->recipient_email)->send(new ProfileMail($candidate, $pdf));

            $send->forceFill([
                'status' => ProfileSendStatus::Sent,
                'sent_at' => now(),
                'error' => null,
            ])->save();

            $send->logActivity('sent', ['recipient' => $send->recipient_email]);
        } catch (Throwable $e) {
            $send->forceFill([
                'status' => ProfileSendStatus::Failed,
                'error' => $e->getMessage(),
            ])->save();

            throw $e;
        }
    }
}
