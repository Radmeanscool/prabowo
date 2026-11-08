<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgendaItem extends Model
{
    protected $fillable = [
        'program_stage_id', 'title', 'session_type', 'date', 'date_label', 'start_time', 'end_time',
        'location', 'description', 'speakers', 'status', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_public' => 'boolean',
        ];
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(ProgramStage::class, 'program_stage_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw('case when date is null then 1 else 0 end')
            ->orderBy('date')->orderBy('start_time')->orderBy('sort_order')->orderBy('id');
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::agendaStatuses(), $this->status);
    }

    public function dateText(): string
    {
        if ($this->date) {
            return fmt_date($this->date);
        }

        return $this->date_label ?: 'Tanggal menyusul';
    }

    public function timeText(): ?string
    {
        if (! $this->start_time) {
            return null;
        }

        $start = substr((string) $this->start_time, 0, 5);
        $end = $this->end_time ? substr((string) $this->end_time, 0, 5) : null;

        return str_replace(':', '.', $start).($end ? '–'.str_replace(':', '.', $end) : ' – selesai').' WITA';
    }
}
