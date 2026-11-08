<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChallengeOwner extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'short_name', 'type', 'theme_id', 'description', 'initial_focus', 'website',
        'contact_name', 'contact_position', 'contact_email', 'contact_phone',
        'participation_status', 'is_public', 'internal_notes', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['is_public' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function challenges(): HasMany
    {
        return $this->hasMany(Challenge::class);
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true)->orderBy('sort_order')->orderBy('name');
    }

    public function typeLabel(): string
    {
        return Options::label(Options::challengeOwnerTypes(), $this->type);
    }

    public function displayName(): string
    {
        return $this->short_name ?: $this->name;
    }

    public function initials(): string
    {
        $words = preg_split('/\s+/', trim($this->displayName())) ?: [];
        $letters = collect($words)->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode('');

        return $letters ?: 'M';
    }
}
