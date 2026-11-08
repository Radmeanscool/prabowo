<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Pintasan menu peserta: menuju proposal tim yang aktif, atau ke langkah yang tepat.
 */
class ShortcutController extends Controller
{
    public function challenge(Request $request): RedirectResponse
    {
        $proposal = $this->current($request);
        if (! $proposal) {
            return $this->noProposal($request);
        }

        return redirect()->route($request->user()->can('update', $proposal) ? 'portal.proposals.challenge' : 'portal.proposals.show', $proposal);
    }

    public function files(Request $request): RedirectResponse
    {
        $proposal = $this->current($request);

        return $proposal ? redirect()->route('portal.proposals.files', $proposal) : $this->noProposal($request);
    }

    public function status(Request $request): RedirectResponse
    {
        $proposal = $this->current($request);

        return $proposal ? redirect()->route('portal.proposals.status', $proposal) : $this->noProposal($request);
    }

    private function current(Request $request): ?Proposal
    {
        $team = $request->user()->activeTeam();

        return $team ? Proposal::query()->where('team_id', $team->id)->latest('id')->first() : null;
    }

    private function noProposal(Request $request): RedirectResponse
    {
        if (! $request->user()->activeTeam()) {
            return redirect()->route('portal.team')->with('warning', 'Buat atau bergabung dengan tim terlebih dahulu.');
        }

        return redirect()->route('portal.proposals.create')->with('warning', 'Mulai dengan memilih tema dan tantangan.');
    }
}
