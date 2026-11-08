<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\ProgramStage;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Models\Team;
use App\Models\Theme;
use App\Models\User;
use App\Support\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $statusCounts = Proposal::query()->select('status', DB::raw('count(*) as total'))->groupBy('status')->pluck('total', 'status');
        $submittedStatuses = collect(ProposalStatus::cases())->reject(fn ($s) => $s === ProposalStatus::Draft)->map->value;

        $themes = Theme::query()->active()->withCount([
            'proposals as submitted_count' => fn ($q) => $q->whereIn('status', $submittedStatuses),
            'proposals as draft_count' => fn ($q) => $q->where('status', ProposalStatus::Draft->value),
        ])->get();

        $byOwner = DB::table('proposals')
            ->join('challenges', 'challenges.id', '=', 'proposals.challenge_id')
            ->join('challenge_owners', 'challenge_owners.id', '=', 'challenges.challenge_owner_id')
            ->whereNull('proposals.deleted_at')
            ->where('proposals.status', '!=', ProposalStatus::Draft->value)
            ->groupBy('challenge_owners.id', 'challenge_owners.name')
            ->select('challenge_owners.name', DB::raw('count(*) as total'))
            ->orderByDesc('total')
            ->limit(8)
            ->get();

        $workload = User::query()->where('role', UserRole::Reviewer->value)->where('is_active', true)
            ->withCount([
                'reviewerAssignments as open_count' => fn ($q) => $q->open(),
                'reviewerAssignments as done_count' => fn ($q) => $q->where('status', 'submitted'),
                'reviewerAssignments as conflict_count' => fn ($q) => $q->where('status', 'conflict_declared'),
            ])->orderByDesc('open_count')->limit(10)->get();

        $averages = ReviewPhase::query()->ordered()->get()->map(fn ($phase) => [
            'phase' => $phase,
            'avg' => Review::query()->where('review_phase_id', $phase->id)->where('status', 'submitted')->avg('weighted_score'),
            'count' => Review::query()->where('review_phase_id', $phase->id)->where('status', 'submitted')->count(),
        ]);

        $stages = ProgramStage::query()->ordered()->withCount('proposals')->get();

        $submitted = Proposal::query()->whereIn('status', $submittedStatuses)->with('files')->get(['id', 'status']);
        $missingDocs = $submitted->filter(fn ($p) => ! $p->files->contains('type', 'proposal_pdf') || ! $p->files->contains('type', 'cover_image'))->count();

        return view('admin.dashboard', [
            'metrics' => [
                'users' => User::query()->where('role', UserRole::Participant->value)->count(),
                'teams' => Team::query()->count(),
                'draft' => (int) ($statusCounts['draft'] ?? 0),
                'submitted' => $submitted->count(),
                'under_review' => (int) ($statusCounts['under_review'] ?? 0),
                'approved' => Proposal::query()->whereNotNull('approved_at')->whereNotIn('status', ['rejected', 'archived'])->count(),
                'public' => Proposal::query()->publicProducts()->count(),
                'unassigned' => Proposal::query()->whereIn('status', ['submitted', 'under_review'])->whereDoesntHave('activeAssignments')->count(),
                'conflicts' => ReviewerAssignment::query()->where('status', 'conflict_declared')->count(),
                'missing_docs' => $missingDocs,
            ],
            'statusCounts' => $statusCounts,
            'themes' => $themes,
            'byOwner' => $byOwner,
            'workload' => $workload,
            'averages' => $averages,
            'stages' => $stages,
            'openCall' => Settings::openCallState(),
            'recent' => Proposal::query()->whereNotNull('submitted_at')->with(['theme', 'team'])->latest('submitted_at')->limit(6)->get(),
        ]);
    }
}
