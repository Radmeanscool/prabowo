<?php

namespace App\Policies;

use App\Models\ProposalFile;
use App\Models\User;

/**
 * Akses berkas privat:
 * - Peserta: hanya berkas tim sendiri (kecuali telaah internal admin).
 * - Reviewer: hanya berkas proposal yang ditugaskan (kecuali telaah internal admin).
 * - Admin: semua berkas.
 * Pengunjung publik tidak pernah melewati policy ini; PDF publik punya rute sendiri.
 */
class ProposalFilePolicy
{
    public function view(User $user, ProposalFile $file): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $proposal = $file->proposal;
        if (! $proposal || $file->is_internal) {
            return false;
        }

        if ($user->isParticipant()) {
            return $proposal->team?->hasActiveMember($user) ?? false;
        }

        if ($user->isReviewer()) {
            return ProposalPolicy::reviewerHasAccess($user, $proposal);
        }

        return false;
    }

    public function delete(User $user, ProposalFile $file): bool
    {
        $proposal = $file->proposal;

        return $user->isParticipant()
            && ! $file->is_internal
            && $proposal !== null
            && $proposal->isEditableByParticipant()
            && ($proposal->team?->hasActiveMember($user) ?? false);
    }
}
