<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use SoftDeletes;

    protected $table = 'people';

    protected $fillable = [
        'user_id', 'name', 'slug', 'type', 'category', 'affiliation', 'position', 'expertise', 'bio', 'theme_id', 'is_public', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->orderBy('sort_order')->orderBy('name');
    }

    public function typeLabel(): string
    {
        return Options::label(Options::personTypes(), $this->type);
    }

    public function categoryLabel(): string
    {
        return Options::label(Options::personCategories(), $this->category, '');
    }

    public function initials(): string
    {
        $clean = preg_replace('/\b(Prof|Dr|Ir|drg|dr|Hj|H)\.?\s*/i', '', $this->name) ?? $this->name;
        $words = preg_split('/\s+/', trim(explode(',', $clean)[0])) ?: [];

        return collect($words)->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('') ?: 'D';
    }
}
