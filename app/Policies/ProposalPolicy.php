<?php

namespace App\Policies;

use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\User;

/**
 * Otorisasi proposal. Reviewer hanya dapat melihat proposal yang ditugaskan admin.
 */
class ProposalPolicy
{
    public function view(User $user, Proposal $proposal): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($user->isParticipant()) {
            return $proposal->team?->hasActiveMember($user) ?? false;
        }

        if ($user->isReviewer()) {
            return self::reviewerHasAccess($user, $proposal);
        }

        return false;
    }

    public function update(User $user, Proposal $proposal): bool
    {
        return $user->isParticipant()
            && $proposal->isEditableByParticipant()
            && ($proposal->team?->hasActiveMember($user) ?? false);
    }

    /** Hanya ketua tim yang mengirim proposal (Kirim Proposal / Ajukan Revisi). */
    public function submit(User $user, Proposal $proposal): bool
    {
        return $user->isParticipant()
            && $proposal->status->isSubmittableByParticipant()
            && ($proposal->team?->isLeader($user) ?? false)
            && ($proposal->team?->hasActiveMember($user) ?? false);
    }

    public function review(User $user, Proposal $proposal): bool
    {
        return $user->isReviewer() && self::reviewerHasAccess($user, $proposal);
    }

    public function manage(User $user, Proposal $proposal): bool
    {
        return $user->isAdmin();
    }

    public static function reviewerHasAccess(User $user, Proposal $proposal): bool
    {
        return ReviewerAssignment::query()
            ->where('proposal_id', $proposal->id)
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ReviewerAssignment::ACCESS_STATUSES)
            ->exists();
    }
}
