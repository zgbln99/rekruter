<?php

namespace App\Actions\Candidates;

use App\Actions\Pipeline\AddCandidateToPipelineAction;
use App\Models\Candidate;
use App\Models\JobPosting;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Szybkie utworzenie kandydata z poziomu ogłoszenia (krok 1: imię, nazwisko,
 * telefon) i automatyczne przypisanie do ogłoszenia oraz jego firmy.
 *
 * Jeśli kandydat z danym numerem już istnieje — używa istniejącego i (o ile to
 * możliwe) dopisuje go do ogłoszenia. Cel: < 60 s podczas rozmowy.
 */
class CreateCandidateFromOfferAction
{
    public function __construct(
        private readonly CreateCandidateAction $createCandidate,
        private readonly AddCandidateToPipelineAction $addToPipeline,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     * @return array{candidate: Candidate, duplicate: bool}
     */
    public function execute(JobPosting $offer, array $data, User $user): array
    {
        return DB::transaction(function () use ($offer, $data, $user) {
            $normalized = PhoneNumber::normalize($data['phone']);
            $existing = Candidate::where('phone_normalized', $normalized)->first();

            $duplicate = $existing !== null;
            $candidate = $existing ?? $this->createCandidate->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'] ?? null,
                'phone' => $data['phone'],
                'source' => $data['source'] ?? 'phone',
            ], $user);

            // Przypisz do ogłoszenia (ignoruj, jeśli już przypisany).
            try {
                $this->addToPipeline->execute([
                    'candidate_id' => $candidate->id,
                    'job_posting_id' => $offer->id,
                ]);
                $candidate->logActivity('assigned_to_offer', [
                    'job_posting_id' => $offer->id,
                    'title' => $offer->title,
                ]);
            } catch (ValidationException $e) {
                // Kandydat już jest w tym ogłoszeniu — to nie błąd w tym flow.
            }

            return ['candidate' => $candidate->refresh(), 'duplicate' => $duplicate];
        });
    }
}
