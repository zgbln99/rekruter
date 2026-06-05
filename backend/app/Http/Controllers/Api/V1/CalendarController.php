<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Placement;
use App\Models\PlacementInstallment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Wbudowany kalendarz: przyjazdy kierowców (weryfikacja „Dotarł / Nie dotarł")
 * oraz terminy rozliczeń (raty/faktury) — te ostatnie widzi tylko administrator.
 */
class CalendarController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $from = $request->date('from') ?? Carbon::now()->startOfMonth();
        $to = $request->date('to') ?? Carbon::now()->endOfMonth();
        $from = Carbon::parse($from)->startOfDay();
        $to = Carbon::parse($to)->endOfDay();

        $events = [];

        // Przyjazdy (dla wszystkich).
        $placements = Placement::query()
            ->with(['candidate', 'jobPosting.company'])
            ->whereBetween('arrival_at', [$from, $to])
            ->get();

        foreach ($placements as $p) {
            $events[] = [
                'type' => 'arrival',
                'date' => $p->arrival_at->toDateString(),
                'datetime' => $p->arrival_at->toIso8601String(),
                'time' => $p->arrival_at->format('H:i'),
                'title' => $p->candidate?->fullName() ?? 'Kierowca',
                'subtitle' => trim(($p->jobPosting?->title ?? '').' · '.($p->jobPosting?->company?->name ?? '')),
                'status' => $p->arrival_status->value,
                'status_label' => $p->arrival_status->label(),
                'color' => $p->arrival_status->color(),
                'placement_id' => $p->id,
                'candidate_id' => $p->candidate_id,
            ];
        }

        // Terminy rozliczeń (tylko administrator).
        if ($request->user()->isAdmin()) {
            $installments = PlacementInstallment::query()
                ->with(['placement.candidate', 'placement.jobPosting.company'])
                ->whereBetween('due_date', [$from->toDateString(), $to->toDateString()])
                ->get();

            foreach ($installments as $i) {
                $p = $i->placement;
                $amount = $i->amount !== null ? rtrim(rtrim(number_format((float) $i->amount, 2, ',', ' '), '0'), ',') : null;
                $events[] = [
                    'type' => 'installment',
                    'date' => $i->due_date->toDateString(),
                    'datetime' => $i->due_date->toIso8601String(),
                    'time' => null,
                    'title' => 'Rozliczenie '.$i->sequence.'/2 — '.($p?->candidate?->fullName() ?? 'kierowca'),
                    'subtitle' => $amount
                        ? ($amount.' '.($p?->currency ?? 'EUR').' · '.($p?->jobPosting?->company?->name ?? ''))
                        : ($p?->jobPosting?->company?->name ?? ''),
                    'status' => $i->status->value,
                    'status_label' => $i->status->label(),
                    'color' => $i->status->color(),
                    'placement_id' => $i->placement_id,
                    'installment_id' => $i->id,
                    'sequence' => $i->sequence,
                    'amount' => $i->amount,
                    'currency' => $p?->currency,
                    'candidate_id' => $p?->candidate_id,
                ];
            }
        }

        // Sortuj wg daty/godziny.
        usort($events, fn ($a, $b) => strcmp($a['datetime'], $b['datetime']));

        return response()->json($events);
    }
}
