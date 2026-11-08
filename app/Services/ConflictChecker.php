<?php

namespace App\Services;

use App\Models\ConflictDeclaration;
use App\Models\MentorAssignment;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Pemeriksaan konflik kepentingan sebelum admin menugaskan reviewer.
 * "blocks" mencegah penugasan; "warnings" boleh dilanjutkan dengan catatan admin.
 */
class ConflictChecker
{
    /**
     * @return array{blocks: array<int, string>, warnings: array<int, string>}
     */
    public static function check(Proposal $proposal, User $reviewer): array
    {
        $blocks = [];
        $warnings = [];
        $team = $proposal->team;

        if (! $reviewer->isReviewer() || ! $reviewer->is_active) {
            $blocks[] = 'Akun ini bukan reviewer aktif.';
        }

        $declared = ConflictDeclaration::query()
            ->where('reviewer_id', $reviewer->id)
            ->where(fn ($q) => $q->where('proposal_id', $proposal->id)->orWhere('team_id', $proposal->team_id))
            ->exists();
        if ($declared) {
            $blocks[] = 'Reviewer telah menyatakan konflik kepentingan terhadap tim/proposal ini.';
        }

        $declaredOnAssignment = ReviewerAssignment::query()
            ->where('reviewer_id', $reviewer->id)
            ->where('proposal_id', $proposal->id)
            ->where('status', 'conflict_declared')
            ->exists();
        if ($declaredOnAssignment && ! $declared) {
            $blocks[] = 'Reviewer pernah menolak penugasan proposal ini karena konflik kepentingan.';
        }

        if ($team) {
            $isMember = $team->members()
                ->where(fn ($q) => $q->where('user_id', $reviewer->id)->orWhere('email', $reviewer->email))
                ->exists();
            if ($isMember || (int) $team->leader_id === (int) $reviewer->id) {
                $blocks[] = 'Reviewer tercatat sebagai anggota tim pengusul.';
            }

            $person = $reviewer->person;
            if ($person && MentorAssignment::query()->where('person_id', $person->id)->where('team_id', $team->id)->exists()) {
                $blocks[] = 'Reviewer adalah mentor tim ini. Juri tidak boleh menilai tim yang dibimbingnya.';
            }

            $institutions = collect([$team->institution])
                ->merge($team->members()->pluck('institution'))
                ->filter()
                ->map(fn ($name) => self::normalize($name))
                ->unique();

            $declaredInstitutions = ConflictDeclaration::query()
                ->where('reviewer_id', $reviewer->id)
                ->whereNotNull('institution')
                ->pluck('institution')
                ->map(fn ($name) => self::normalize($name));

            if ($declaredInstitutions->intersect($institutions)->isNotEmpty()) {
                $blocks[] = 'Reviewer menyatakan konflik kepentingan dengan institusi anggota tim.';
            }

            if ($reviewer->institution && $institutions->contains(self::normalize($reviewer->institution))) {
                $warnings[] = 'Reviewer berasal dari institusi yang sama dengan tim ('.$reviewer->institution.').';
            }
        }

        return ['blocks' => array_values(array_unique($blocks)), 'warnings' => $warnings];
    }

    private static function normalize(string $value): string
    {
        return Str::of($value)->lower()->squish()->toString();
    }
}
