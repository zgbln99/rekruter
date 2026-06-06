<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ArrivalStatus;
use App\Enums\InstallmentStatus;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Placement;
use App\Models\PlacementInstallment;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Powiadomienia „na żywo" dla zalogowanego użytkownika: zaległe zadania,
 * dzisiejsze przyjazdy do weryfikacji, terminy rozliczeń (admin) oraz
 * wygasające uprawnienia kierowców.
 */
class NotificationController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $items = [];

        // Zaległe zadania (przypisane do mnie) — tylko z istniejącym kandydatem.
        $tasks = Task::query()
            ->with('candidate')
            ->where('assigned_to', $user->id)
            ->where('status', TaskStatus::Open->value)
            ->whereNotNull('due_at')
            ->where('due_at', '<', now()->startOfDay())
            ->where(fn ($q) => $q->whereNull('candidate_id')->orWhereHas('candidate'))
            ->orderBy('due_at')
            ->limit(20)
            ->get();
        foreach ($tasks as $t) {
            $items[] = [
                'id' => 'task:'.$t->id,
                'type' => 'task',
                'title' => $t->title,
                'subtitle' => 'Zaległe zadanie'.($t->candidate ? ' · '.$t->candidate->fullName() : ''),
                'to' => $t->candidate_id ? '/candidates/'.$t->candidate_id : '/',
                'when' => $t->due_at?->toIso8601String(),
                'color' => '#ef4444',
            ];
        }

        // Dzisiejsze przyjazdy do weryfikacji.
        $arrivals = Placement::query()
            ->with('candidate')
            ->whereHas('candidate')
            ->where('arrival_status', ArrivalStatus::Pending->value)
            ->whereBetween('arrival_at', [now()->startOfDay(), now()->endOfDay()])
            ->get();
        foreach ($arrivals as $p) {
            $items[] = [
                'id' => 'arrival:'.$p->id,
                'type' => 'arrival',
                'title' => $p->candidate?->fullName() ?? 'Kierowca',
                'subtitle' => 'Dziś przyjazd — zweryfikuj, czy dotarł',
                'to' => $p->candidate_id ? '/candidates/'.$p->candidate_id : '/calendar',
                'when' => $p->arrival_at?->toIso8601String(),
                'color' => '#f59e0b',
            ];
        }

        // Raty z terminem ≤ 2 dni (tylko administrator).
        if ($user->isAdmin()) {
            $installments = PlacementInstallment::query()
                ->with('placement.candidate')
                ->whereHas('placement.candidate')
                ->where('status', InstallmentStatus::Pending->value)
                ->whereBetween('due_date', [now()->startOfDay()->toDateString(), now()->addDays(2)->toDateString()])
                ->get();
            foreach ($installments as $i) {
                $items[] = [
                    'id' => 'installment:'.$i->id,
                    'type' => 'installment',
                    'title' => 'Rozliczenie '.$i->sequence.'/2',
                    'subtitle' => ($i->placement?->candidate?->fullName() ?? 'kierowca').' — wystaw fakturę',
                    'to' => '/calendar',
                    'when' => $i->due_date?->toIso8601String(),
                    'color' => '#6366f1',
                ];
            }
        }

        // Wygasające uprawnienia kierowców (≤ 30 dni).
        $items = array_merge($items, $this->expiringQualifications());

        // Sortuj po dacie rosnąco (najpilniejsze na górze).
        usort($items, fn ($a, $b) => strcmp($a['when'] ?? '', $b['when'] ?? ''));

        return response()->json([
            'count' => count($items),
            'items' => $items,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function expiringQualifications(): array
    {
        $fields = [
            'code_95_expiry' => 'Kod 95',
            'adr_expiry' => 'ADR',
            'driver_card_expiry' => 'Karta kierowcy',
        ];

        $from = now()->startOfDay();
        $to = now()->addDays(30)->endOfDay();

        $items = [];
        foreach ($fields as $field => $label) {
            $candidates = Candidate::query()
                ->whereNotNull($field)
                ->whereBetween($field, [$from, $to])
                ->limit(30)
                ->get(['id', 'first_name', 'last_name', $field]);

            foreach ($candidates as $c) {
                $items[] = [
                    'id' => 'expiry:'.$c->id.':'.$field,
                    'type' => 'expiry',
                    'title' => $c->fullName(),
                    'subtitle' => $label.' wygasa '.$c->{$field}->format('d.m.Y'),
                    'to' => '/candidates/'.$c->id,
                    'when' => $c->{$field}->toIso8601String(),
                    'color' => '#dc2626',
                ];
            }
        }

        return $items;
    }
}
