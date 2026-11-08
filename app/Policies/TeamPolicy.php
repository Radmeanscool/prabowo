<?php

namespace App\Policies;

use App\Models\Team;
use App\Models\User;

class TeamPolicy
{
    public function view(User $user, Team $team): bool
    {
        return $user->isAdmin() || $team->hasActiveMember($user);
    }

    public function update(User $user, Team $team): bool
    {
        return $user->isAdmin() || ($team->isLeader($user) && $team->hasActiveMember($user));
    }

    public function manageMembers(User $user, Team $team): bool
    {
        return $this->update($user, $team);
    }
}
