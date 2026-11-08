<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * `role` dan `is_active` sengaja TIDAK mass-assignable agar tidak bisa
     * dinaikkan lewat formulir pendaftaran/profil (role escalation).
     */
    protected $fillable = [
        'name', 'email', 'password', 'phone', 'institution', 'city', 'province',
        'discipline', 'participant_type', 'expertise', 'consented_at',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $attributes = [
        'role' => 'participant',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'consented_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'role' => UserRole::class,
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isReviewer(): bool
    {
        return $this->role === UserRole::Reviewer;
    }

    public function isParticipant(): bool
    {
        return $this->role === UserRole::Participant;
    }

    public function teamMemberships(): HasMany
    {
        return $this->hasMany(TeamMember::class);
    }

    public function ledTeams(): HasMany
    {
        return $this->hasMany(Team::class, 'leader_id');
    }

    /** Tim aktif peserta (default: satu peserta satu tim). */
    public function activeTeam(): ?Team
    {
        $membership = $this->teamMemberships()
            ->where('status', 'active')
            ->whereHas('team')
            ->with('team')
            ->oldest('id')
            ->first();

        return $membership?->team;
    }

    public function pendingMembership(): ?TeamMember
    {
        return $this->teamMemberships()->where('status', 'pending')->whereHas('team')->with('team')->first();
    }

    public function reviewerAssignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class, 'reviewer_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewer_id');
    }

    public function conflictDeclarations(): HasMany
    {
        return $this->hasMany(ConflictDeclaration::class, 'reviewer_id');
    }

    public function person(): HasOne
    {
        return $this->hasOne(Person::class);
    }

    public function dashboardRoute(): string
    {
        return $this->role->dashboardRoute();
    }
}
