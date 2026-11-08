<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramStage extends Model
{
    protected $fillable = [
        'code', 'phase_number', 'phase_name', 'name', 'name_id', 'month_label', 'start_month', 'end_month',
        'starts_on', 'ends_on', 'description', 'activities', 'output', 'status', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'starts_on' => 'date',
            'ends_on' => 'date',
        ];
    }

    public function agendaItems(): HasMany
    {
        return $this->hasMany(AgendaItem::class);
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->orderBy('sort_order')->orderBy('id');
    }

    public function activityList(): array
    {
        return collect(preg_split('/\R/', (string) $this->activities) ?: [])
            ->map(fn ($line) => trim(ltrim(trim($line), '-•*')))
            ->filter()
            ->values()
            ->all();
    }

    public function statusLabel(): string
    {
        return Options::label(Options::stageStatuses(), $this->status);
    }

    public function phaseTone(): string
    {
        return match ((int) $this->phase_number) {
            1 => 'phase-1',
            2 => 'phase-2',
            3 => 'phase-3',
            4 => 'phase-4',
            default => 'phase-0',
        };
    }
}
