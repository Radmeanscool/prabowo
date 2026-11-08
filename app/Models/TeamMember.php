<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeamMember extends Model
{
    protected $fillable = [
        'team_id', 'user_id', 'name', 'email', 'phone', 'institution', 'discipline', 'role', 'status', 'joined_at', 'added_by',
    ];

    protected function casts(): array
    {
        return ['joined_at' => 'datetime'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function statusLabel(): string
    {
        return Options::label(Options::memberStatuses(), $this->status);
    }

    public function isLeader(): bool
    {
        return $this->role === 'leader';
    }
}
