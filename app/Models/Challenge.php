<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Challenge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'theme_id', 'challenge_owner_id', 'title', 'slug', 'summary', 'challenge_statement',
        'problem_statement', 'affected_users', 'current_baseline', 'desired_outcome',
        'minimum_functions', 'success_indicators', 'constraints', 'available_data', 'data_public',
        'pilot_location', 'pilot_location_public', 'contact_info', 'contact_public',
        'internal_notes', 'status', 'published_at', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'data_public' => 'boolean',
            'pilot_location_public' => 'boolean',
            'contact_public' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(ChallengeOwner::class, 'challenge_owner_id');
    }

    /** Tema proposal yang boleh memilih tantangan ini. */
    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class, 'challenge_theme');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    /** Nama pemilik tantangan hanya bila mitra tersebut sudah ditandai publik. */
    public function publicOwner(): ?ChallengeOwner
    {
        return $this->owner && $this->owner->is_public ? $this->owner : null;
    }
}
