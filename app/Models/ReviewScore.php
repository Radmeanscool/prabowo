<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewScore extends Model
{
    protected $fillable = ['review_id', 'review_criterion_id', 'score', 'comment'];

    protected function casts(): array
    {
        return ['score' => 'integer'];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(ReviewCriterion::class, 'review_criterion_id');
    }
}
