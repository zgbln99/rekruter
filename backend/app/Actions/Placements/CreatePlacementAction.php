<?php

namespace App\Actions\Placements;

use App\Models\Candidate;
use App\Models\Placement;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tworzy skierowanie kierowcy do pracy wraz z harmonogramem rozliczeń.
 *
 * Reguła rat (decyzja biznesowa): płatność dzielona na 2 raty —
 *  • rata 1: termin = przyjazd + 14 dni, kwota = total/2,
 *  • rata 2: termin = przyjazd + 28 dni, kwota = total − rata1 (wyrównanie groszy).
 */
class CreatePlacementAction
{
    /**
     * @param  array{job_posting_id:string, arrival_at:string, total_amount?:float|string|null, currency?:string|null, notes?:string|null}  $data
     */
    public function execute(Candidate $candidate, array $data, User $recruiter): Placement
    {
        return DB::transaction(function () use ($candidate, $data, $recruiter) {
            $jobPosting = \App\Models\JobPosting::query()->findOrFail($data['job_posting_id']);
            $tenant = $recruiter->tenant;

            $arrival = Carbon::parse($data['arrival_at']);

            // Kwota rozliczenia jest ustalona z góry w ustawieniach agencji
            // (dane finansowe — rekruterka jej nie podaje). Admin może ją nadpisać.
            $override = $data['total_amount'] ?? null;
            $total = ($override !== '' && $override !== null && $recruiter->isAdmin())
                ? round((float) $override, 2)
                : $tenant?->placementFee();

            $placement = Placement::create([
                'candidate_id' => $candidate->id,
                'job_posting_id' => $jobPosting->id,
                'company_id' => $jobPosting->company_id,
                'created_by' => $recruiter->id,
                'arrival_at' => $arrival,
                'arrival_status' => 'pending',
                'total_amount' => $total,
                'currency' => $data['currency'] ?? $tenant?->placementCurrency() ?? 'EUR',
                'notes' => $data['notes'] ?? null,
            ]);

            // Harmonogram dwóch rat.
            $first = $total !== null ? round($total / 2, 2) : null;
            $second = $total !== null ? round($total - $first, 2) : null;

            $placement->installments()->createMany([
                [
                    'sequence' => 1,
                    'due_date' => $arrival->copy()->addDays(14)->toDateString(),
                    'amount' => $first,
                    'status' => 'pending',
                ],
                [
                    'sequence' => 2,
                    'due_date' => $arrival->copy()->addDays(28)->toDateString(),
                    'amount' => $second,
                    'status' => 'pending',
                ],
            ]);

            return $placement->load(['installments', 'jobPosting.company', 'candidate']);
        });
    }
}
