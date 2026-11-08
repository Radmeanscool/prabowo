<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ThemeQuestion extends Model
{
    protected $fillable = ['theme_id', 'question', 'help_text', 'is_required', 'word_limit', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /** Pertanyaan aktif untuk sebuah tema (termasuk pertanyaan umum tanpa tema). */
    public function scopeForTheme(Builder $query, ?int $themeId): Builder
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('theme_id')->when($themeId, fn ($qq) => $qq->orWhere('theme_id', $themeId)))
            ->orderBy('sort_order')->orderBy('id');
    }
}
