<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'category', 'audience', 'is_published', 'is_pinned', 'published_at'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_pinned' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderByDesc('is_pinned')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function scopeForPublic(Builder $query): Builder
    {
        return $query->published()->where('audience', 'public');
    }

    /** Pengumuman yang relevan untuk pengguna yang login sesuai perannya. */
    public function scopeForUser(Builder $query, User $user): Builder
    {
        $audiences = ['public', 'all_users'];
        if ($user->isParticipant()) {
            $audiences[] = 'participants';
        }
        if ($user->isReviewer()) {
            $audiences[] = 'reviewers';
        }
        if ($user->isAdmin()) {
            $audiences = array_keys(Options::audiences());
        }

        return $query->published()->whereIn('audience', $audiences);
    }

    public function categoryLabel(): string
    {
        return Options::label(Options::announcementCategories(), $this->category);
    }
}
