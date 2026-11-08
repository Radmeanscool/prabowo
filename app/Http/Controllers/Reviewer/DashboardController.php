<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\ReviewerAssignment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $base = ReviewerAssignment::query()->where('reviewer_id', $user->id);

        return view('reviewer.dashboard', [
            'user' => $user,
            'counts' => [
                'open' => (clone $base)->open()->count(),
                'draft' => (clone $base)->where('status', 'in_progress')->count(),
                'submitted' => (clone $base)->where('status', 'submitted')->count(),
                'conflict' => (clone $base)->where('status', 'conflict_declared')->count(),
            ],
            'upcoming' => (clone $base)->open()->with(['proposal.theme', 'phase'])
                ->orderByRaw('case when due_at is null then 1 else 0 end')->orderBy('due_at')->limit(6)->get(),
            'announcements' => Announcement::query()->forUser($user)->limit(4)->get(),
        ]);
    }
}
