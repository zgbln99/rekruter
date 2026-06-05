<?php

namespace App\Actions\Candidates;

use App\Actions\Contacts\LogContactAction;
use App\Enums\CandidateStatus;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Szybkie dodanie kandydata (Quick-Add) — rdzeń KPI < 60s.
 *
 * Tworzy kandydata z minimalnego zestawu pól i — jeśli przekazano blok
 * `contact` — od razu zapisuje pierwszy kontakt (z ewentualnym auto-taskiem).
 */
class CreateCandidateAction
{
    public function __construct(
        private readonly LogContactAction $logContact,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $user): Candidate
    {
        return DB::transaction(function () use ($data, $user) {
            $candidate = new Candidate([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? null,
                'status' => $data['status'] ?? CandidateStatus::New,
                'license_categories' => $data['license_categories'] ?? [],
                'has_adr' => $data['has_adr'] ?? false,
                'has_code_95' => $data['has_code_95'] ?? false,
                'source' => $data['source'] ?? 'phone',
                'internal_notes' => $data['internal_notes'] ?? null,
                'created_by' => $user->id,
            ]);
            $candidate->save();

            if (! empty($data['contact'])) {
                $this->logContact->execute($candidate, $data['contact'], $user);
            }

            return $candidate->refresh();
        });
    }
}
