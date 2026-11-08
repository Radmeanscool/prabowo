<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChallengeOwner;
use Illuminate\View\View;

class ChallengeOwnerController extends Controller
{
    public function index(): View
    {
        return view('public.owners.index', [
            'owners' => ChallengeOwner::query()->public()->with('theme')->withCount(['challenges' => fn ($q) => $q->published()])->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $owner = ChallengeOwner::query()->public()->where('slug', $slug)->with('theme')->firstOrFail();

        return view('public.owners.show', [
            'owner' => $owner,
            'challenges' => $owner->challenges()->published()->with('theme')->get(),
        ]);
    }
}
