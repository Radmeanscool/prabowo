<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Team extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'institution', 'city', 'province', 'profile', 'contact_email', 'contact_phone',
    ];

    public function leader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    public function members(): HasMany
    {
        return $this->hasMany(TeamMember::class)->orderByRaw("case when role = 'leader' then 0 else 1 end")->orderBy('id');
    }

    public function activeMembers(): HasMany
    {
        return $this->members()->where('status', 'active');
    }

    public function proposals(): HasMany
    {
        return $this->hasMany(Proposal::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class);
    }

    public function isLeader(?User $user): bool
    {
        return $user !== null && (int) $this->leader_id === (int) $user->id;
    }

    public function hasActiveMember(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->members()->where('user_id', $user->id)->where('status', 'active')->exists();
    }

    /** Jumlah anggota yang menempati kuota (aktif + menunggu persetujuan). */
    public function seatCount(): int
    {
        return $this->members()->whereIn('status', ['active', 'pending'])->count();
    }

    public static function generateJoinCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
            $code = strtr($code, ['0' => 'X', 'O' => 'Y', '1' => 'Z', 'I' => 'W', 'L' => 'V']);
        } while (self::withTrashed()->where('join_code', $code)->exists());

        return $code;
    }

    public static function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'tim';
        $slug = $base;
        $i = 2;
        while (self::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
