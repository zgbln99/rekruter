<?php

namespace App\Console\Commands;

use App\Models\Placement;
use App\Models\PlacementInstallment;
use App\Models\PushSubscription;
use App\Models\Task;
use App\Models\User;
use App\Support\Push\WebPushService;
use App\Support\Tenancy\TenantScope;
use Illuminate\Console\Command;

/**
 * Wysyła powiadomienia push z przypomnieniami do użytkowników z subskrypcją:
 * zaległe zadania, dzisiejsze zadania/przyjazdy, raty do wystawienia (admin).
 *
 * Pod crona, np. codziennie o 7:00:
 *   php artisan rekruter:send-reminders
 */
class SendReminderPushCommand extends Command
{
    protected $signature = 'rekruter:send-reminders';

    protected $description = 'Wysyła powiadomienia push z przypomnieniami na dziś';

    public function handle(WebPushService $push): int
    {
        if (! $push->configured()) {
            $this->warn('Brak kluczy VAPID — push pominięty.');

            return self::SUCCESS;
        }

        $userIds = PushSubscription::withoutGlobalScopes()->distinct()->pluck('user_id');
        $sent = 0;

        foreach ($userIds as $uid) {
            $user = User::withoutGlobalScopes()->find($uid);
            if (! $user) {
                continue;
            }

            $parts = [];

            $overdue = Task::withoutGlobalScope(TenantScope::class)
                ->where('assigned_to', $user->id)->where('status', 'open')
                ->whereNotNull('due_at')->where('due_at', '<', now()->startOfDay())->count();
            if ($overdue) {
                $parts[] = $overdue.' '.$this->plural($overdue, 'zaległe zadanie', 'zaległe zadania', 'zaległych zadań');
            }

            $todayTasks = Task::withoutGlobalScope(TenantScope::class)
                ->where('assigned_to', $user->id)->where('status', 'open')
                ->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count();
            if ($todayTasks) {
                $parts[] = $todayTasks.' '.$this->plural($todayTasks, 'zadanie na dziś', 'zadania na dziś', 'zadań na dziś');
            }

            $arrivals = Placement::withoutGlobalScope(TenantScope::class)
                ->where('tenant_id', $user->tenant_id)
                ->where('arrival_status', 'pending')
                ->whereBetween('arrival_at', [now()->startOfDay(), now()->endOfDay()])->count();
            if ($arrivals) {
                $parts[] = $arrivals.' '.$this->plural($arrivals, 'przyjazd dziś', 'przyjazdy dziś', 'przyjazdów dziś');
            }

            if ($user->isAdmin()) {
                $inst = PlacementInstallment::withoutGlobalScope(TenantScope::class)
                    ->where('tenant_id', $user->tenant_id)
                    ->where('status', 'pending')
                    ->whereBetween('due_date', [now()->startOfDay()->toDateString(), now()->addDays(2)->toDateString()])->count();
                if ($inst) {
                    $parts[] = $inst.' '.$this->plural($inst, 'rata do wystawienia', 'raty do wystawienia', 'rat do wystawienia');
                }
            }

            if (empty($parts)) {
                continue;
            }

            $push->sendToUser($user, 'Przypomnienia na dziś', implode(' · ', $parts), '/');
            $sent++;
        }

        $this->info("Wysłano powiadomienia do {$sent} użytkowników.");

        return self::SUCCESS;
    }

    /** Polska odmiana: 1 / 2–4 / 5+. */
    private function plural(int $n, string $one, string $few, string $many): string
    {
        if ($n === 1) {
            return $one;
        }
        $mod10 = $n % 10;
        $mod100 = $n % 100;
        if ($mod10 >= 2 && $mod10 <= 4 && ($mod100 < 12 || $mod100 > 14)) {
            return $few;
        }

        return $many;
    }
}
