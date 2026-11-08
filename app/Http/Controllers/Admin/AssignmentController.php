<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Models\Theme;
use App\Models\User;
use App\Services\Audit;
use App\Services\ConflictChecker;
use App\Services\Notifier;
use App\Services\ProposalWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin menghubungkan proposal dengan reviewer: tugaskan, tugaskan massal, cabut,
 * ganti, dan pantau beban kerja. Pemeriksaan konflik kepentingan berjalan di setiap penugasan.
 */
class AssignmentController extends Controller
{
    private const ASSIGNABLE = ['submitted', 'under_review', 'shortlisted', 'accepted', 'bootcamp', 'mentoring', 'prototype_development', 'demo_day', 'selected_prototype', 'approved'];

    public function index(Request $request): View
    {
        $phases = ReviewPhase::query()->ordered()->get();
        $phase = $phases->firstWhere('id', (int) $request->query('phase_id')) ?? $phases->firstWhere('is_active', true) ?? $phases->first();

        $proposals = Proposal::query()
            ->whereIn('status', self::ASSIGNABLE)
            ->when($request->query('theme_id'), fn ($q, $v) => $q->where('theme_id', $v))
            ->when($request->query('state') === 'unassigned', fn ($q) => $q->whereDoesntHave('assignments', fn ($a) => $a->where('review_phase_id', $phase?->id)->whereIn('status', ReviewerAssignment::ACCESS_STATUSES)))
            ->when($request->query('state') === 'conflict', fn ($q) => $q->whereHas('assignments', fn ($a) => $a->where('review_phase_id', $phase?->id)->where('status', 'conflict_declared')))
            ->with(['theme', 'team', 'challenge', 'assignments' => fn ($q) => $q->where('review_phase_id', $phase?->id)->where('status', '!=', 'removed')->with(['reviewer', 'review'])])
            ->latest('submitted_at')
            ->paginate(30)->withQueryString();

        $reviewers = User::query()->where('role', UserRole::Reviewer->value)->where('is_active', true)
            ->withCount([
                'reviewerAssignments as open_count' => fn ($q) => $q->open(),
                'reviewerAssignments as done_count' => fn ($q) => $q->where('status', 'submitted'),
                'reviewerAssignments as phase_count' => fn ($q) => $q->where('review_phase_id', $phase?->id)->whereIn('status', ReviewerAssignment::ACCESS_STATUSES),
            ])
            ->with('person')
            ->orderBy('name')->get();

        return view('admin.assignments.index', [
            'phases' => $phases,
            'phase' => $phase,
            'proposals' => $proposals,
            'reviewers' => $reviewers,
            'themes' => Theme::query()->active()->pluck('name', 'id')->all(),
            'selfSelection' => (bool) setting('reviewer_self_selection_enabled', false),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'proposal_id' => ['required', Rule::exists('proposals', 'id')],
            'reviewer_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::Reviewer->value)],
            'review_phase_id' => ['required', Rule::exists('review_phases', 'id')],
            'due_at' => ['nullable', 'date'],
            'confirm_warnings' => ['nullable', 'boolean'],
            'override_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $result = $this->assign(
            Proposal::query()->findOrFail($data['proposal_id']),
            User::query()->findOrFail($data['reviewer_id']),
            ReviewPhase::query()->findOrFail($data['review_phase_id']),
            $data['due_at'] ?? null,
            $request->boolean('confirm_warnings'),
            $data['override_note'] ?? null,
            $request->user(),
        );

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function bulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'proposal_ids' => ['required', 'array', 'min:1'],
            'proposal_ids.*' => [Rule::exists('proposals', 'id')],
            'reviewer_ids' => ['required', 'array', 'min:1'],
            'reviewer_ids.*' => [Rule::exists('users', 'id')->where('role', UserRole::Reviewer->value)],
            'review_phase_id' => ['required', Rule::exists('review_phases', 'id')],
            'due_at' => ['nullable', 'date'],
            'confirm_warnings' => ['nullable', 'boolean'],
        ], [
            'proposal_ids.required' => 'Centang minimal satu proposal.',
            'reviewer_ids.required' => 'Pilih minimal satu reviewer.',
        ]);

        $phase = ReviewPhase::query()->findOrFail($data['review_phase_id']);
        $reviewers = User::query()->whereIn('id', $data['reviewer_ids'])->get();
        $created = 0;
        $skipped = [];

        foreach (Proposal::query()->whereIn('id', $data['proposal_ids'])->get() as $proposal) {
            foreach ($reviewers as $reviewer) {
                $result = $this->assign($proposal, $reviewer, $phase, $data['due_at'] ?? null, $request->boolean('confirm_warnings'), 'Penugasan massal', $request->user());
                if ($result['ok']) {
                    $created++;
                } else {
                    $skipped[] = $proposal->product_name.' × '.$reviewer->name.': '.$result['message'];
                }
            }
        }

        $message = $created.' penugasan dibuat.';
        if ($skipped) {
            return back()->with('success', $message)->with('warning', count($skipped).' dilewati — '.implode(' | ', array_slice($skipped, 0, 6)).(count($skipped) > 6 ? ' …' : ''));
        }

        return back()->with('success', $message);
    }

    public function remove(Request $request, ReviewerAssignment $assignment): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']], ['reason.required' => 'Isi alasan pencabutan penugasan.']);

        $old = $assignment->status;
        $assignment->forceFill([
            'status' => 'removed',
            'removed_at' => now(),
            'removed_by' => $request->user()->id,
            'removal_reason' => $data['reason'],
        ])->save();

        Audit::log('assignment.removed', $assignment, ['status' => $old], ['status' => 'removed', 'reason' => $data['reason']], 'Penugasan reviewer dicabut');

        return back()->with('success', 'Penugasan dicabut. Riwayat dan penilaian (bila ada) tetap tersimpan.');
    }

    public function replace(Request $request, ReviewerAssignment $assignment): RedirectResponse
    {
        $data = $request->validate([
            'reviewer_id' => ['required', Rule::exists('users', 'id')->where('role', UserRole::Reviewer->value), Rule::notIn([$assignment->reviewer_id])],
            'confirm_warnings' => ['nullable', 'boolean'],
        ]);

        $result = $this->assign(
            $assignment->proposal,
            User::query()->findOrFail($data['reviewer_id']),
            $assignment->phase,
            $assignment->due_at?->toDateTimeString(),
            $request->boolean('confirm_warnings'),
            'Pengganti reviewer #'.$assignment->reviewer_id,
            $request->user(),
        );

        if (! $result['ok']) {
            return back()->with('error', $result['message']);
        }

        if ($assignment->status !== 'removed') {
            $old = $assignment->status;
            $assignment->forceFill(['status' => 'removed', 'removed_at' => now(), 'removed_by' => $request->user()->id, 'removal_reason' => 'Diganti reviewer lain'])->save();
            Audit::log('assignment.replaced', $assignment, ['status' => $old, 'reviewer_id' => $assignment->reviewer_id], ['status' => 'removed', 'replacement_reviewer_id' => (int) $data['reviewer_id']]);
        }

        return back()->with('success', 'Reviewer diganti. '.$result['message']);
    }

    /**
     * @return array{ok: bool, message: string}
     */
    private function assign(Proposal $proposal, User $reviewer, ReviewPhase $phase, ?string $dueAt, bool $confirmWarnings, ?string $note, User $admin): array
    {
        if (! in_array($proposal->status->value, self::ASSIGNABLE, true)) {
            return ['ok' => false, 'message' => 'Proposal berstatus '.$proposal->status->label().' tidak dapat ditugaskan.'];
        }

        $exists = ReviewerAssignment::query()
            ->where('proposal_id', $proposal->id)->where('reviewer_id', $reviewer->id)->where('review_phase_id', $phase->id)
            ->whereIn('status', ReviewerAssignment::ACCESS_STATUSES)->exists();
        if ($exists) {
            return ['ok' => false, 'message' => 'Reviewer sudah ditugaskan pada fase ini.'];
        }

        $check = ConflictChecker::check($proposal, $reviewer);
        if ($check['blocks']) {
            return ['ok' => false, 'message' => 'Konflik kepentingan: '.implode(' ', $check['blocks'])];
        }
        if ($check['warnings'] && ! $confirmWarnings) {
            return ['ok' => false, 'message' => 'Perlu konfirmasi: '.implode(' ', $check['warnings']).' Centang "Saya sudah meninjau peringatan" untuk melanjutkan.'];
        }

        $assignment = ReviewerAssignment::query()->create([
            'proposal_id' => $proposal->id,
            'reviewer_id' => $reviewer->id,
            'review_phase_id' => $phase->id,
            'assigned_by' => $admin->id,
            'status' => 'assigned',
            'due_at' => $dueAt ? Carbon::parse($dueAt) : $phase->ends_at,
            'override_note' => $check['warnings'] ? trim(($note ? $note.' · ' : '').'Peringatan ditinjau admin: '.implode(' ', $check['warnings'])) : $note,
        ]);

        if ($proposal->status === ProposalStatus::Submitted) {
            $proposal->forceFill(['status' => ProposalStatus::UnderReview, 'review_phase_id' => $phase->id])->save();
            (new ProposalWorkflow)->recordHistory($proposal, ProposalStatus::Submitted, ProposalStatus::UnderReview, $admin, 'Reviewer ditugaskan.');
        } elseif (! $proposal->review_phase_id) {
            $proposal->forceFill(['review_phase_id' => $phase->id])->save();
        }

        Audit::log('assignment.created', $assignment, [], [
            'proposal_id' => $proposal->id,
            'reviewer_id' => $reviewer->id,
            'phase' => $phase->code,
            'warnings' => $check['warnings'],
        ], 'Reviewer ditugaskan');

        Notifier::reviewerAssigned($assignment->load(['reviewer', 'phase']));

        return ['ok' => true, 'message' => $reviewer->name.' ditugaskan menilai "'.$proposal->product_name.'".'];
    }
}
