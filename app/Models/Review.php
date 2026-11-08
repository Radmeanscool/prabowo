<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'reviewer_assignment_id', 'proposal_id', 'reviewer_id', 'review_phase_id', 'status', 'recommendation',
        'overall_comment', 'private_notes', 'weighted_score', 'submitted_at', 'reopened_at', 'reopened_by',
    ];

    protected function casts(): array
    {
        return [
            'weighted_score' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reopened_at' => 'datetime',
        ];
    }

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ReviewerAssignment::class, 'reviewer_assignment_id');
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

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function isSubmitted(): bool
    {
        return $this->status === 'submitted';
    }

    public function recommendationLabel(): string
    {
        return Options::label(Options::recommendations(), $this->recommendation);
    }
}
