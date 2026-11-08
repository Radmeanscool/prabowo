<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\ConflictDeclaration;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\ReviewScore;
use App\Services\Audit;
use App\Services\ScoreCalculator;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Reviewer hanya melihat proposal yang ditugaskan admin. Akses selalu dicek
 * dari tabel penugasan (bukan sekadar menyembunyikan tombol).
 */
class AssignmentController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status');
        $assignments = ReviewerAssignment::query()
            ->where('reviewer_id', $request->user()->id)
            ->where('status', '!=', 'removed')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with(['proposal.theme', 'proposal.challenge', 'phase', 'review'])
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('reviewer.assignments.index', [
            'assignments' => $assignments,
            'statuses' => collect(Options::assignmentStatuses())->except('removed')->all(),
            'status' => $status,
        ]);
    }

    public function reviews(Request $request): View
    {
        $status = $request->query('status') === 'submitted' ? 'submitted' : 'draft';
        $reviews = Review::query()
            ->where('reviewer_id', $request->user()->id)
            ->where('status', $status)
            ->whereHas('assignment', fn ($q) => $q->whereIn('status', ReviewerAssignment::ACCESS_STATUSES))
            ->with(['proposal.theme', 'phase', 'assignment'])
            ->latest('updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('reviewer.reviews', compact('reviews', 'status'));
    }

    public function show(Request $request, ReviewerAssignment $assignment): View
    {
        $this->authorizeAssignment($request, $assignment);

        $proposal = $assignment->proposal;
        $proposal->load(['theme', 'challenge.owner', 'team.members', 'researchHistories', 'files', 'answers.question']);
        $criteria = $assignment->phase->criteriaForTheme($proposal->theme_id);
        $review = $assignment->review()->with('scores')->first();

        return view('reviewer.assignments.show', [
            'assignment' => $assignment,
            'proposal' => $proposal,
            'criteria' => $criteria,
            'review' => $review,
            'scores' => $review?->scores->keyBy('review_criterion_id') ?? collect(),
            'locked' => $review?->isSubmitted() ?? false,
            'blind' => (bool) setting('blind_review', false),
            'recommendations' => Options::recommendations(),
        ]);
    }

    public function saveReview(Request $request, ReviewerAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);
        $proposal = $assignment->proposal;

        $review = $assignment->review;
        if ($review?->isSubmitted()) {
            return back()->with('error', 'Penilaian sudah dikirim final dan terkunci. Hubungi admin bila perlu dibuka kembali.');
        }

        $submit = $request->input('action') === 'submit';
        $criteria = $assignment->phase->criteriaForTheme($proposal->theme_id);

        if ($submit && $request->input('recommendation') === 'conflict_of_interest') {
            return $this->markConflict($request, $assignment, $request->input('overall_comment') ?: 'Dinyatakan melalui formulir penilaian.');
        }

        $rules = [
            'recommendation' => [$submit ? 'required' : 'nullable', Rule::in(array_keys(Options::recommendations()))],
            'overall_comment' => ['nullable', 'string', 'max:5000'],
            'private_notes' => ['nullable', 'string', 'max:5000'],
            'scores' => ['nullable', 'array'],
            'comments' => ['nullable', 'array'],
        ];
        $messages = [];
        foreach ($criteria as $criterion) {
            $rules['scores.'.$criterion->id] = [$submit ? 'required' : 'nullable', 'integer', 'between:1,'.$criterion->max_score];
            $rules['comments.'.$criterion->id] = [$submit && $criterion->requires_comment ? 'required' : 'nullable', 'string', 'max:3000'];
            $messages['scores.'.$criterion->id.'.required'] = 'Nilai untuk "'.$criterion->name.'" wajib diisi.';
            $messages['comments.'.$criterion->id.'.required'] = 'Komentar untuk "'.$criterion->name.'" wajib diisi.';
        }
        $data = $request->validate($rules, $messages);

        DB::transaction(function () use ($assignment, $proposal, $criteria, $data, $submit, $request, &$review) {
            $review = $assignment->review ?? new Review([
                'reviewer_assignment_id' => $assignment->id,
                'proposal_id' => $proposal->id,
                'reviewer_id' => $request->user()->id,
                'review_phase_id' => $assignment->review_phase_id,
            ]);

            $review->fill([
                'recommendation' => $data['recommendation'] ?? null,
                'overall_comment' => $data['overall_comment'] ?? null,
                'private_notes' => $data['private_notes'] ?? null,
            ]);
            $review->status = 'draft';
            $review->save();

            $scores = [];
            foreach ($criteria as $criterion) {
                $score = $data['scores'][$criterion->id] ?? null;
                $scores[$criterion->id] = $score !== null ? (int) $score : null;
                ReviewScore::query()->updateOrCreate(
                    ['review_id' => $review->id, 'review_criterion_id' => $criterion->id],
                    ['score' => $scores[$criterion->id], 'comment' => $data['comments'][$criterion->id] ?? null]
                );
            }

            // Skor tertimbang selalu dihitung di server, tidak pernah diterima dari klien.
            $review->weighted_score = ScoreCalculator::weighted($criteria, $scores);

            if ($submit) {
                $review->status = 'submitted';
                $review->submitted_at = now();
                $assignment->forceFill(['status' => 'submitted'])->save();
            } elseif ($assignment->status === 'assigned') {
                $assignment->forceFill(['status' => 'in_progress'])->save();
            }
            $review->save();

            if ($submit) {
                Audit::log('review.submitted', $review, [], [
                    'proposal_id' => $proposal->id,
                    'phase' => $assignment->phase?->code,
                    'weighted_score' => $review->weighted_score,
                    'recommendation' => $review->recommendation,
                    'scores' => $scores,
                ], 'Penilaian final dikirim');
            }
        });

        return redirect()->route('reviewer.assignments.show', $assignment)->with('success', $submit
            ? 'Penilaian final terkirim. Skor tertimbang: '.number_format((float) $review->weighted_score, 2, ',', '.').'.'
            : 'Draf penilaian tersimpan.');
    }

    public function declareConflict(Request $request, ReviewerAssignment $assignment): RedirectResponse
    {
        $this->authorizeAssignment($request, $assignment);
        $data = $request->validate(['reason' => ['required', 'string', 'max:2000']]);

        if ($assignment->review?->isSubmitted()) {
            return back()->with('error', 'Penilaian sudah dikirim. Hubungi admin untuk melaporkan konflik kepentingan.');
        }

        return $this->markConflict($request, $assignment, $data['reason']);
    }

    private function markConflict(Request $request, ReviewerAssignment $assignment, string $reason): RedirectResponse
    {
        DB::transaction(function () use ($request, $assignment, $reason) {
            $assignment->forceFill([
                'status' => 'conflict_declared',
                'conflict_reason' => $reason,
                'conflict_declared_at' => now(),
            ])->save();

            ConflictDeclaration::query()->create([
                'reviewer_id' => $request->user()->id,
                'proposal_id' => $assignment->proposal_id,
                'team_id' => $assignment->proposal?->team_id,
                'reason' => $reason,
            ]);

            Audit::log('assignment.conflict_declared', $assignment, ['status' => 'assigned'], ['status' => 'conflict_declared', 'reason' => $reason], 'Reviewer menyatakan konflik kepentingan');
        });

        return redirect()->route('reviewer.assignments.index')->with('success', 'Konflik kepentingan tercatat. Proposal ini tidak lagi dapat Anda akses; admin akan menugaskan reviewer lain.');
    }

    private function authorizeAssignment(Request $request, ReviewerAssignment $assignment): void
    {
        abort_unless(
            (int) $assignment->reviewer_id === (int) $request->user()->id && $assignment->grantsAccess(),
            403,
            'Proposal ini tidak ditugaskan kepada Anda.'
        );
    }
}
