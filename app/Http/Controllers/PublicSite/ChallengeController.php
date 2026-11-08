<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChallengeController extends Controller
{
    public function index(Request $request): View
    {
        $themes = Theme::query()->active()->get();
        $theme = $themes->firstWhere('code', $request->query('tema'));

        $challenges = Challenge::query()->published()
            ->with(['theme', 'owner', 'themes'])
            ->when($theme, fn ($q) => $q->whereHas('themes', fn ($t) => $t->where('themes.id', $theme->id)))
            ->when($request->query('mitra'), fn ($q, $slug) => $q->whereHas('owner', fn ($o) => $o->where('slug', $slug)->where('is_public', true)))
            ->orderBy('sort_order')->orderBy('title')
            ->get();

        return view('public.challenges.index', [
            'challenges' => $challenges,
            'themes' => $themes,
            'activeTheme' => $theme,
            'owners' => ChallengeOwner::query()->public()->whereHas('challenges', fn ($q) => $q->published())->get(),
        ]);
    }

    public function show(string $slug): View
    {
        $challenge = Challenge::query()->published()->where('slug', $slug)->with(['theme', 'owner', 'themes'])->firstOrFail();

        return view('public.challenges.show', [
            'challenge' => $challenge,
            'owner' => $challenge->publicOwner(),
            'related' => Challenge::query()->published()->whereKeyNot($challenge->id)->where('theme_id', $challenge->theme_id)->limit(3)->get(),
        ]);
    }
}
