<?php

namespace App\Http\Controllers\Reviewer;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Services\Audit;
use App\Services\ConflictChecker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Pemilihan proposal oleh reviewer sendiri. NONAKTIF secara default;
 * hanya tersedia bila panitia mengaktifkan pengaturan reviewer_self_selection_enabled.
 */
class SelfSelectionController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(setting('reviewer_self_selection_enabled', false), 404);
        $phase = $this->phase();

        $proposals = Proposal::query()
            ->whereIn('status', [ProposalStatus::Submitted->value, ProposalStatus::UnderReview->value])
            ->whereDoesntHave('assignments', fn ($q) => $q->where('reviewer_id', $request->user()->id))
            ->with(['theme', 'challenge'])
            ->latest('submitted_at')
            ->paginate(20);

        return view('reviewer.self-select', compact('proposals', 'phase'));
    }

    public function store(Request $request, Proposal $proposal): RedirectResponse
    {
        abort_unless(setting('reviewer_self_selection_enabled', false), 404);
        abort_unless(in_array($proposal->status, [ProposalStatus::Submitted, ProposalStatus::UnderReview], true), 403);

        $phase = $this->phase();
        abort_unless($phase, 404);

        $check = ConflictChecker::check($proposal, $request->user());
        if ($check['blocks']) {
            return back()->with('error', implode(' ', $check['blocks']));
        }

        $assignment = ReviewerAssignment::query()->firstOrCreate(
            ['proposal_id' => $proposal->id, 'reviewer_id' => $request->user()->id, 'review_phase_id' => $phase->id],
            ['assigned_by' => $request->user()->id, 'status' => 'assigned']
        );

        if ($proposal->status === ProposalStatus::Submitted) {
            $proposal->forceFill(['status' => ProposalStatus::UnderReview, 'review_phase_id' => $phase->id])->save();
        }

        Audit::log('assignment.self_selected', $assignment, [], ['proposal_id' => $proposal->id, 'warnings' => $check['warnings']]);

        return redirect()->route('reviewer.assignments.show', $assignment)->with('success', 'Proposal ditambahkan ke daftar penilaian Anda.');
    }

    private function phase(): ?ReviewPhase
    {
        return ReviewPhase::query()->where('is_active', true)->ordered()->first();
    }
}
