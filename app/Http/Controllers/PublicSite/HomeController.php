<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\AgendaItem;
use App\Models\Announcement;
use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Document;
use App\Models\Person;
use App\Models\ProgramStage;
use App\Models\Proposal;
use App\Models\Theme;
use App\Support\Settings;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        return view('public.home', [
            'openCall' => Settings::openCallState(),
            'themes' => Theme::query()->active()->withCount(['eligibleChallenges as published_challenges_count' => fn ($q) => $q->where('status', 'published')])->get(),
            'challenges' => Challenge::query()->published()->with(['theme', 'owner'])->orderBy('sort_order')->limit(6)->get(),
            'owners' => ChallengeOwner::query()->public()->with('theme')->limit(12)->get(),
            'stages' => ProgramStage::query()->public()->get(),
            'stats' => collect(setting('homepage_stats', []))->filter(fn ($s) => filled($s['value'] ?? null)),
            'people' => Person::query()->public()->whereIn('type', ['mentor', 'juror'])->limit(8)->get(),
            'products' => Proposal::query()->publicProducts()->with(['theme', 'challenge.owner', 'team'])->latest('published_at')->limit(6)->get(),
            'announcements' => Announcement::query()->forPublic()->limit(3)->get(),
            'documents' => Document::query()->published()->where('visibility', 'public')->limit(4)->get(),
            'agenda' => AgendaItem::query()->public()->where(fn ($q) => $q->whereNull('date')->orWhere('date', '>=', now()->toDateString()))->where('status', '!=', 'completed')->ordered()->limit(3)->get(),
        ]);
    }
}
