<?php

namespace App\Actions\Dashboard;

use App\Enums\ApplicationStatus;
use App\Enums\CandidateStatus;
use App\Enums\ProfileSendStatus;
use App\Models\Activity;
use App\Models\Application;
use App\Models\Candidate;
use App\Models\Company;
use App\Models\JobPosting;
use App\Models\ProfileSend;
use App\Models\Task;
use App\Models\User;

/**
 * Zbiera metryki na pulpit (w obrębie tenanta).
 */
class DashboardStatsAction
{
    /**
     * @return array<string, mixed>
     */
    public function execute(User $user): array
    {
        $startOfWeek = now()->startOfWeek();

        return [
            'candidates' => [
                'total' => Candidate::count(),
                'new_this_week' => Candidate::where('created_at', '>=', $startOfWeek)->count(),
                'by_status' => $this->candidatesByStatus(),
            ],
            'offers' => [
                'total' => JobPosting::count(),
                'active' => JobPosting::where('status', 'open')->count(),
            ],
            'companies' => Company::count(),
            'tasks' => [
                'today' => Task::where('assigned_to', $user->id)
                    ->where('status', 'open')
                    ->where(fn ($q) => $q->whereNull('due_at')->orWhere('due_at', '<=', now()->endOfDay()))
                    ->count(),
                'overdue' => Task::where('assigned_to', $user->id)
                    ->where('status', 'open')
                    ->whereNotNull('due_at')
                    ->where('due_at', '<', now()->startOfDay())
                    ->count(),
            ],
            'profiles' => [
                'sent_total' => ProfileSend::where('status', ProfileSendStatus::Sent->value)->count(),
                'sent_this_week' => ProfileSend::where('status', ProfileSendStatus::Sent->value)
                    ->where('sent_at', '>=', $startOfWeek)->count(),
                'pending_decisions' => ProfileSend::where('status', ProfileSendStatus::Sent->value)
                    ->where('decision', 'pending')->count(),
            ],
            'pipeline' => $this->applicationsByStatus(),
            'reminders' => $this->reminders($user),
            'recent_activity' => $this->recentActivity(),
        ];
    }

    /**
     * Przypomnienia: przyjazdy do zweryfikowania dziś oraz raty z terminem
     * w ciągu 2 dni (raty widzi tylko administrator — dane finansowe).
     *
     * @return array<string, mixed>
     */
    private function reminders(User $user): array
    {
        $arrivals = \App\Models\Placement::query()
            ->with(['candidate', 'jobPosting'])
            ->where('arrival_status', \App\Enums\ArrivalStatus::Pending->value)
            ->whereBetween('arrival_at', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('arrival_at')
            ->get()
            ->map(fn (\App\Models\Placement $p) => [
                'placement_id' => $p->id,
                'candidate_id' => $p->candidate_id,
                'candidate_name' => $p->candidate?->fullName() ?? 'Kierowca',
                'time' => $p->arrival_at?->format('H:i'),
                'offer_title' => $p->jobPosting?->title,
            ])
            ->all();

        $installments = [];
        if ($user->isAdmin()) {
            $installments = \App\Models\PlacementInstallment::query()
                ->with(['placement.candidate'])
                ->where('status', \App\Enums\InstallmentStatus::Pending->value)
                ->whereBetween('due_date', [now()->startOfDay()->toDateString(), now()->addDays(2)->toDateString()])
                ->orderBy('due_date')
                ->get()
                ->map(fn (\App\Models\PlacementInstallment $i) => [
                    'installment_id' => $i->id,
                    'placement_id' => $i->placement_id,
                    'candidate_name' => $i->placement?->candidate?->fullName() ?? 'kierowca',
                    'sequence' => $i->sequence,
                    'due_date' => $i->due_date?->toDateString(),
                    'amount' => $i->amount,
                    'currency' => $i->placement?->currency,
                ])
                ->all();
        }

        return [
            'arrivals_today' => $arrivals,
            'installments_due' => $installments,
        ];
    }

    /**
     * @return array<int, array{value: string, label: string, count: int}>
     */
    private function candidatesByStatus(): array
    {
        $counts = Candidate::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return collect(CandidateStatus::cases())
            ->map(fn (CandidateStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'count' => (int) ($counts[$s->value] ?? 0),
            ])
            ->filter(fn ($r) => $r['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{value: string, label: string, color: string, count: int}>
     */
    private function applicationsByStatus(): array
    {
        $counts = Application::query()
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return collect(ApplicationStatus::cases())
            ->map(fn (ApplicationStatus $s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
                'count' => (int) ($counts[$s->value] ?? 0),
            ])
            ->all();
    }

    /**
     * @return array<int, array{label: string, at: ?string, by: ?string, candidate_id: ?string}>
     */
    private function recentActivity(): array
    {
        return Activity::query()
            ->with('user')
            ->whereIn('event', ['created', 'assigned_to_offer', 'sent', 'status_changed', 'photo_set', 'pdf_generated'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (Activity $a) => [
                'label' => $this->activityLabel($a),
                'subject' => class_basename($a->subject_type),
                'candidate_id' => class_basename($a->subject_type) === 'Candidate' ? $a->subject_id : null,
                'at' => $a->created_at?->toIso8601String(),
                'by' => $a->user?->name,
            ])
            ->all();
    }

    private function activityLabel(Activity $a): string
    {
        $subject = class_basename($a->subject_type);

        return match ("{$subject}.{$a->event}") {
            'Candidate.created' => 'Dodano kandydata',
            'Candidate.assigned_to_offer' => 'Przypisano kandydata do ogłoszenia',
            'Candidate.photo_set' => 'Ustawiono zdjęcie kandydata',
            'Candidate.pdf_generated' => 'Wygenerowano PDF kandydata',
            'Application.status_changed' => 'Zmiana statusu w pipeline',
            'ProfileSend.sent' => 'Wysłano profil do firmy',
            'JobPosting.created' => 'Dodano ogłoszenie',
            'Company.created' => 'Dodano firmę',
            default => $a->event,
        };
    }
}
