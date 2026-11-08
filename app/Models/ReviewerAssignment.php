<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ReviewerAssignment extends Model
{
    /** Status yang memberi akses reviewer ke proposal. */
    public const ACCESS_STATUSES = ['assigned', 'in_progress', 'submitted'];

    protected $fillable = [
        'proposal_id', 'reviewer_id', 'review_phase_id', 'assigned_by', 'status', 'due_at',
        'conflict_reason', 'conflict_declared_at', 'override_note', 'removed_at', 'removed_by', 'removal_reason',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'conflict_declared_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ReviewPhase::class, 'review_phase_id');
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', self::ACCESS_STATUSES);
    }

    /** Penugasan yang masih dihitung sebagai beban kerja (belum selesai). */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', ['assigned', 'in_progress']);
    }

    public function grantsAccess(): bool
    {
        return in_array($this->status, self::ACCESS_STATUSES, true);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::assignmentStatuses(), $this->status);
    }
}
