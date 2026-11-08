<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\User;
use App\Notifications\ProposalStatusChanged;
use App\Notifications\ProposalSubmitted;
use App\Notifications\ReviewerAssigned;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Notifikasi surel opsional (aktif bila pengaturan email_notifications_enabled = true).
 * Dikirim sinkron sehingga tidak memerlukan queue worker di shared hosting.
 * Kegagalan pengiriman dicatat ke log dan tidak menggagalkan alur kerja.
 */
class Notifier
{
    public static function enabled(): bool
    {
        return (bool) setting('email_notifications_enabled', false);
    }

    public static function proposalSubmitted(Proposal $proposal): void
    {
        self::send(self::teamUsers($proposal), new ProposalSubmitted($proposal));
    }

    public static function statusChanged(Proposal $proposal, ProposalStatus $status): void
    {
        $notify = [
            ProposalStatus::RevisionRequested, ProposalStatus::Shortlisted, ProposalStatus::Accepted,
            ProposalStatus::Approved, ProposalStatus::Rejected, ProposalStatus::SelectedPrototype,
        ];
        if (in_array($status, $notify, true)) {
            self::send(self::teamUsers($proposal), new ProposalStatusChanged($proposal, $status));
        }
    }

    public static function reviewerAssigned(ReviewerAssignment $assignment): void
    {
        if ($assignment->reviewer) {
            self::send(collect([$assignment->reviewer]), new ReviewerAssigned($assignment));
        }
    }

    /** @return Collection<int, User> */
    protected static function teamUsers(Proposal $proposal): Collection
    {
        $team = $proposal->team;
        if (! $team) {
            return collect();
        }

        return $team->members()->where('status', 'active')->whereNotNull('user_id')->with('user')->get()
            ->pluck('user')->filter()->unique('id')->values();
    }

    protected static function send(Collection $users, object $notification): void
    {
        if (! self::enabled() || $users->isEmpty()) {
            return;
        }

        try {
            Notification::send($users, $notification);
        } catch (Throwable $e) {
            Log::warning('Gagal mengirim notifikasi DEEPNEST: '.$e->getMessage());
        }
    }
}
