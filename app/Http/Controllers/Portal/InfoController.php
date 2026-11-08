<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\Document;
use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InfoController extends Controller
{
    public function announcements(Request $request): View
    {
        return view('portal.announcements', [
            'announcements' => Announcement::query()->forUser($request->user())->paginate(10),
            'documents' => Document::query()->published()->whereIn('visibility', Document::visibilitiesFor($request->user()))->get(),
        ]);
    }

    public function help(): View
    {
        return view('portal.help', [
            'faqs' => Faq::query()->published()->get()->groupBy(fn ($faq) => $faq->category ?: 'Umum'),
        ]);
    }
}
