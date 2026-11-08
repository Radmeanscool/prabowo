<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Models\Page;
use App\Models\Theme;
use Illuminate\View\View;

class PageController extends Controller
{
    public function about(): View
    {
        return $this->page('tentang', ['themes' => Theme::query()->active()->get()]);
    }

    public function rules(): View
    {
        return $this->page('ketentuan');
    }

    public function privacy(): View
    {
        return $this->page('privasi');
    }

    public function terms(): View
    {
        return $this->page('syarat');
    }

    public function faq(): View
    {
        return view('public.faq', [
            'faqs' => Faq::query()->published()->get()->groupBy(fn ($faq) => $faq->category ?: 'Umum'),
        ]);
    }

    private function page(string $slug, array $extra = []): View
    {
        $page = Page::query()->where('slug', $slug)->where('is_published', true)->firstOrFail();

        return view('public.page', ['page' => $page] + $extra);
    }
}
