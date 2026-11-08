<?php

namespace App\Http\Controllers\PublicSite;

use App\Http\Controllers\Controller;
use App\Models\ChallengeOwner;
use App\Models\ProgramStage;
use App\Models\Proposal;
use App\Models\Theme;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Katalog produk publik: hanya produk yang disetujui dan tidak disembunyikan.
 * Data yang tampil berasal dari kolom ringkasan publik, bukan isian privat.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $themes = Theme::query()->active()->get();
        $theme = $themes->firstWhere('code', $request->query('tema'));
        $sort = $request->query('urut') === 'terlama' ? 'asc' : 'desc';

        $products = Proposal::query()->publicProducts()
            ->with(['theme', 'challenge.owner', 'team', 'programStage'])
            ->when($theme, fn ($q) => $q->where('theme_id', $theme->id))
            ->when($request->query('mitra'), fn ($q, $slug) => $q->whereHas('challenge.owner', fn ($o) => $o->where('slug', $slug)->where('is_public', true)))
            ->when($request->query('tahap'), fn ($q, $code) => $q->whereHas('programStage', fn ($s) => $s->where('code', $code)))
            ->when($request->query('institusi'), fn ($q, $term) => $q->where('public_show_team', true)->where(fn ($qq) => $qq->where('public_team_label', 'like', "%{$term}%")->orWhereHas('team', fn ($t) => $t->where('institution', 'like', "%{$term}%"))))
            ->when($request->query('tahun'), fn ($q, $year) => $q->whereYear('published_at', (int) $year))
            ->when($request->query('q'), fn ($q, $term) => $q->where('public_title', 'like', "%{$term}%"))
            ->orderBy('published_at', $sort)
            ->paginate(12)->withQueryString();

        $years = Proposal::query()->publicProducts()->whereNotNull('published_at')->pluck('published_at')
            ->map(fn ($d) => $d->format('Y'))->unique()->sortDesc()->values();

        return view('public.products.index', [
            'products' => $products,
            'themes' => $themes,
            'activeTheme' => $theme,
            'owners' => ChallengeOwner::query()->public()->pluck('name', 'slug')->all(),
            'stages' => ProgramStage::query()->public()->pluck('name', 'code')->all(),
            'years' => $years,
        ]);
    }

    public function show(string $slug): View
    {
        $product = Proposal::query()->publicProducts()->where('public_slug', $slug)
            ->with(['theme', 'challenge.owner', 'team', 'programStage', 'files'])
            ->first();

        // Produk yang disembunyikan, ditolak, atau belum disetujui tidak dapat diakses publik.
        abort_if($product === null, 404);

        return view('public.products.show', [
            'product' => $product,
            'pdfAvailable' => $product->public_pdf_enabled && $product->currentFile('proposal_pdf') !== null,
        ]);
    }
}
