<?php

namespace App\Models;

use App\Support\Tenancy\BelongsToTenant;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    use BelongsToTenant, HasFactory, HasUuids;

    protected $fillable = [
        'tenant_id',
        'name',
        'color',
        'position',
        'is_terminal',
    ];

    protected function casts(): array
    {
        return [
            'is_terminal' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'stage_id');
    }

    /**
     * Domyślne etapy pipeline tworzone dla nowej organizacji.
     *
     * @return array<int, array{name: string, color: string, is_terminal: bool}>
     */
    public static function defaults(): array
    {
        return [
            ['name' => 'Nowy', 'color' => '#64748b', 'is_terminal' => false],
            ['name' => 'Kontakt', 'color' => '#0ea5e9', 'is_terminal' => false],
            ['name' => 'Weryfikacja dokumentów', 'color' => '#f59e0b', 'is_terminal' => false],
            ['name' => 'Wysłany do klienta', 'color' => '#8b5cf6', 'is_terminal' => false],
            ['name' => 'Zatrudniony', 'color' => '#10b981', 'is_terminal' => true],
            ['name' => 'Odrzucony', 'color' => '#ef4444', 'is_terminal' => true],
        ];
    }
}
