<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Services\ProposalChecklist;
use App\Support\Settings;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $team = $user->activeTeam();
        $team?->load(['members', 'leader']);
        $proposals = $team ? $team->proposals()->with(['theme', 'challenge', 'files'])->latest('id')->get() : collect();
        $proposal = $proposals->first();

        return view('portal.dashboard', [
            'user' => $user,
            'team' => $team,
            'pending' => $team ? null : $user->pendingMembership(),
            'proposal' => $proposal,
            'proposals' => $proposals,
            'checklist' => $proposal ? ProposalChecklist::items($proposal) : [],
            'percent' => $proposal ? ProposalChecklist::percent($proposal) : 0,
            'openCall' => Settings::openCallState(),
            'announcements' => Announcement::query()->forUser($user)->limit(4)->get(),
        ]);
    }
}
