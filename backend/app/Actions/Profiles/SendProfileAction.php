<?php

namespace App\Actions\Profiles;

use App\Enums\ProfileSendStatus;
use App\Jobs\SendProfileEmailJob;
use App\Models\Candidate;
use App\Models\ProfileSend;
use App\Models\User;

/**
 * Inicjuje wysyłkę profilu kandydata do klienta: tworzy rekord ProfileSend
 * (status queued) i zleca wysyłkę w kolejce.
 */
class SendProfileAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Candidate $candidate, array $data, User $user): ProfileSend
    {
        $send = ProfileSend::create([
            'candidate_id' => $candidate->id,
            'company_id' => $data['company_id'] ?? null,
            'job_posting_id' => $data['job_posting_id'] ?? null,
            'recipient_email' => $data['recipient_email'],
            'status' => ProfileSendStatus::Queued,
            'sent_by' => $user->id,
        ]);

        SendProfileEmailJob::dispatch($send);

        return $send;
    }
}
