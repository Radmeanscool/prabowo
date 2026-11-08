<?php

namespace App\Http\Controllers\Reviewer;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\ReviewPhase;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfoController extends Controller
{
    public function rubric(): View
    {
        return view('reviewer.rubric', [
            'phases' => ReviewPhase::query()->ordered()->where('is_active', true)
                ->with(['criteria' => fn ($q) => $q->where('is_active', true)->with('theme')])->get(),
        ]);
    }

    public function announcements(Request $request): View
    {
        return view('portal.announcements', [
            'announcements' => Announcement::query()->forUser($request->user())->paginate(10),
            'documents' => Document::query()->published()->whereIn('visibility', Document::visibilitiesFor($request->user()))->get(),
        ]);
    }
}
