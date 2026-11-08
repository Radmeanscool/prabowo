<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewPhase extends Model
{
    protected $fillable = ['code', 'name', 'description', 'is_active', 'starts_at', 'ends_at', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(ReviewCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Kriteria aktif yang berlaku untuk tema tertentu (umum + khusus tema). */
    public function criteriaForTheme(?int $themeId)
    {
        return $this->criteria()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('theme_id')->when($themeId, fn ($qq) => $qq->orWhere('theme_id', $themeId)))
            ->get();
    }

    public function isPastDeadline(): bool
    {
        return $this->ends_at !== null && now()->greaterThan($this->ends_at);
    }
}
