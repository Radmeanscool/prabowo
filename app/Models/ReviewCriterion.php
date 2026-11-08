<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCriterion extends Model
{
    protected $table = 'review_criteria';

    protected $fillable = [
        'review_phase_id', 'theme_id', 'name', 'description', 'max_score', 'weight', 'requires_comment', 'sort_order', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'max_score' => 'integer',
            'weight' => 'decimal:2',
            'requires_comment' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function phase(): BelongsTo
    {
        return $this->belongsTo(ReviewPhase::class, 'review_phase_id');
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(ReviewScore::class);
    }

    public function hasScores(): bool
    {
        return $this->scores()->whereNotNull('score')->exists();
    }
}
