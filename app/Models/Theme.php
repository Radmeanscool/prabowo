<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    public const FOOD_SECURITY = 'food_security';
    public const HEALTH = 'health';
    public const HYBRID = 'hybrid';

    protected $fillable = ['code', 'name', 'name_en', 'description', 'color', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    /** Tantangan yang boleh dipilih oleh proposal bertema ini. */
    public function eligibleChallenges(): BelongsToMany
    {
        return $this->belongsToMany(Challenge::class, 'challenge_theme');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ThemeQuestion::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /** Kelas CSS lencana tema (theme-food, theme-health, theme-hybrid). */
    public function cssClass(): string
    {
        return match ($this->code) {
            self::FOOD_SECURITY => 'theme-food',
            self::HEALTH => 'theme-health',
            self::HYBRID => 'theme-hybrid',
            default => 'theme-other',
        };
    }

    public function icon(): string
    {
        return match ($this->code) {
            self::FOOD_SECURITY => 'leaf',
            self::HEALTH => 'heart-pulse',
            self::HYBRID => 'hybrid',
            default => 'layers',
        };
    }
}
