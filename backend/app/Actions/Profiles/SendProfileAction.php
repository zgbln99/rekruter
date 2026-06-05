<?php

namespace App\Actions\Profiles;

use App\Enums\ApplicationStatus;
use App\Enums\CompanyDecision;
use App\Enums\ProfileSendStatus;
use App\Jobs\SendProfileEmailJob;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\ProfileSend;
use App\Models\User;

/**
 * Inicjuje wysyłkę profilu kandydata do klienta: tworzy rekord ProfileSend
 * (status queued), uzupełnia ogłoszenie/firmę i zleca wysyłkę w kolejce.
 */
class SendProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Candidate $candidate, array $data, User $user): ProfileSend
    {
        $jobPostingId = $data['job_offer_id'] ?? $data['job_posting_id'] ?? null;
        $companyId = $data['company_id'] ?? null;

        // Z ogłoszenia ustal firmę docelową i zaktualizuj status aplikacji.
        if ($jobPostingId !== null) {
            $offer = JobPosting::find($jobPostingId);
            $companyId ??= $offer?->company_id;

            Application::where('candidate_id', $candidate->id)
                ->where('job_posting_id', $jobPostingId)
                ->update(['status' => ApplicationStatus::SentToCompany]);
        }

        $send = ProfileSend::create([
            'candidate_id' => $candidate->id,
            'company_id' => $companyId,
            'job_posting_id' => $jobPostingId,
            'recipient_email' => $data['recipient_email'],
            'status' => ProfileSendStatus::Queued,
            'decision' => CompanyDecision::Pending,
            'sent_by' => $user->id,
        ]);

        SendProfileEmailJob::dispatch($send);

        return $send;
    }
}
