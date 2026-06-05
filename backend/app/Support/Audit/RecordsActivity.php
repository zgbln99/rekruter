<?php

namespace App\Support\Audit;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait audytujący — zapisuje wpis w `activities` przy created/updated/deleted.
 *
 * Wymóg RODO + biznesowy „kto co zrobił" (DESIGN.md sekcja 12).
 * Kontekst użytkownika/IP pobierany jest z bieżącego żądania, jeśli istnieje.
 */
trait RecordsActivity
{
    public static function bootRecordsActivity(): void
    {
        static::created(fn (Model $m) => self::recordActivity($m, 'created'));
        static::updated(fn (Model $m) => self::recordActivity($m, 'updated'));
        static::deleted(fn (Model $m) => self::recordActivity($m, 'deleted'));
    }

    /**
     * Ręczne logowanie zdarzeń niestandardowych (np. sent, viewed, downloaded).
     */
    public function logActivity(string $event, ?array $changes = null): void
    {
        self::recordActivity($this, $event, $changes);
    }

    protected static function recordActivity(Model $model, string $event, ?array $changes = null): void
    {
        if ($changes === null && $event === 'updated') {
            $dirty = $model->getChanges();
            unset($dirty['updated_at']);
            if ($dirty === []) {
                return; // brak istotnych zmian
            }
            $changes = [
                'attributes' => $dirty,
                'old' => array_intersect_key($model->getOriginal(), $dirty),
            ];
        }

        $tenantId = $model->getAttribute('tenant_id')
            ?? (app()->bound('currentTenantId') ? app('currentTenantId') : null);

        $request = function_exists('request') ? request() : null;

        Activity::create([
            'tenant_id' => $tenantId,
            'user_id' => optional($request?->user())->id ?? auth()->id(),
            'subject_type' => $model->getMorphClass(),
            'subject_id' => $model->getKey(),
            'event' => $event,
            'changes' => $changes,
            'ip' => $request?->ip(),
            'user_agent' => $request ? substr((string) $request->userAgent(), 0, 255) : null,
        ]);
    }
}
