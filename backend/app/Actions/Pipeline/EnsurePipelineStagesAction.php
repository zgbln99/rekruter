<?php

namespace App\Actions\Pipeline;

use App\Models\PipelineStage;
use App\Models\Tenant;

/**
 * Zakłada domyślne etapy pipeline dla organizacji, jeśli jeszcze nie istnieją.
 * Wywoływane przy seedowaniu i przy tworzeniu nowego tenanta.
 */
class EnsurePipelineStagesAction
{
    public function execute(Tenant $tenant): void
    {
        $exists = PipelineStage::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->exists();

        if ($exists) {
            return;
        }

        foreach (PipelineStage::defaults() as $i => $stage) {
            PipelineStage::withoutGlobalScopes()->create([
                'tenant_id' => $tenant->id,
                'name' => $stage['name'],
                'color' => $stage['color'],
                'is_terminal' => $stage['is_terminal'],
                'position' => $i,
            ]);
        }
    }
}
