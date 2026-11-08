<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $category = array_key_exists((string) $request->query('kategori'), Options::announcementCategories()) ? $request->query('kategori') : null;

        return view('public.announcements.index', [
            'announcements' => Announcement::query()->forPublic()->when($category, fn ($q) => $q->where('category', $category))->paginate(9)->withQueryString(),
            'category' => $category,
            'categories' => Options::announcementCategories(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $announcement = Announcement::query()->where('slug', $slug)->published()->firstOrFail();

        // Pengumuman khusus peserta/reviewer hanya untuk pengguna yang berhak.
        if ($announcement->audience !== 'public') {
            $user = $request->user();
            abort_unless($user && Announcement::query()->forUser($user)->whereKey($announcement->id)->exists(), 404);
        }

        return view('public.announcements.show', [
            'announcement' => $announcement,
            'latest' => Announcement::query()->forPublic()->whereKeyNot($announcement->id)->limit(3)->get(),
        ]);
    }
}
