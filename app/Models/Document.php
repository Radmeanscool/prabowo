<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    protected $fillable = ['title', 'slug', 'description', 'category', 'external_url', 'visibility', 'is_published', 'published_at', 'sort_order'];

    protected $hidden = ['file_path'];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'size' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order')->orderByDesc('published_at');
    }

    /** Visibilitas yang boleh dilihat oleh pengguna (null = pengunjung publik). */
    public static function visibilitiesFor(?User $user): array
    {
        if ($user === null) {
            return ['public'];
        }
        if ($user->isAdmin()) {
            return array_keys(Options::documentVisibilities());
        }
        if ($user->isReviewer()) {
            return ['public', 'reviewers'];
        }

        return ['public', 'participants'];
    }

    public function isVisibleTo(?User $user): bool
    {
        return ($this->is_published || ($user?->isAdmin() ?? false))
            && in_array($this->visibility, self::visibilitiesFor($user), true);
    }

    public function hasFile(): bool
    {
        return ! empty($this->file_path);
    }

    public function categoryLabel(): string
    {
        return Options::label(Options::documentCategories(), $this->category);
    }

    public function visibilityLabel(): string
    {
        return Options::label(Options::documentVisibilities(), $this->visibility);
    }
}
