<?php

namespace App\Actions\Contacts;

use App\Enums\ContactChannel;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Candidate;
use App\Models\ContactLog;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Zapisuje kontakt z kandydatem. Jeżeli podano termin kolejnego kontaktu
 * (next_contact_at), automatycznie tworzy zadanie follow-up i wiąże je
 * z wpisem kontaktu (DESIGN.md, reguła 5.3).
 */
class LogContactAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Candidate $candidate, array $data, User $user): ContactLog
    {
        return DB::transaction(function () use ($candidate, $data, $user) {
            $contactedAt = isset($data['contacted_at'])
                ? Carbon::parse($data['contacted_at'])
                : now();

            $nextContactAt = ! empty($data['next_contact_at'])
                ? Carbon::parse($data['next_contact_at'])
                : null;

            $contact = new ContactLog([
                'candidate_id' => $candidate->id,
                'user_id' => $user->id,
                'channel' => $data['channel'],
                'outcome' => $data['outcome'],
                'note' => $data['note'] ?? null,
                'contacted_at' => $contactedAt,
                'next_contact_at' => $nextContactAt,
            ]);
            $contact->save();

            if ($nextContactAt !== null) {
                $channelLabel = ContactChannel::tryFrom($data['channel'])?->label() ?? 'Kontakt';

                $task = new Task([
                    'candidate_id' => $candidate->id,
                    'assigned_to' => $user->id,
                    'created_by' => $user->id,
                    'type' => TaskType::FollowUp,
                    'status' => TaskStatus::Open,
                    'title' => 'Kontakt: '.$candidate->fullName(),
                    'description' => $channelLabel.' — '.($data['note'] ?? ''),
                    'due_at' => $nextContactAt,
                ]);
                $task->save();

                $contact->task_id = $task->id;
                $contact->save();
            }

            return $contact;
        });
    }
}
