<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Rules\FileContentType;
use App\Services\Audit;
use App\Services\ProposalWorkflow;
use App\Services\SecureFileStore;
use App\Support\Options;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Produk disetujui: otomatis tampil publik saat disetujui. Admin dapat menyunting ringkasan publik,
 * menyembunyikan/menerbitkan ulang, dan membuka PDF proposal untuk publik secara eksplisit.
 */
class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Proposal::query()
            ->whereNotNull('approved_at')
            ->when($request->query('visibility') === 'public', fn ($q) => $q->where('is_public', true))
            ->when($request->query('visibility') === 'hidden', fn ($q) => $q->where('is_public', false))
            ->with(['theme', 'team', 'challenge'])
            ->latest('approved_at')
            ->paginate(25)->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    public function edit(Proposal $proposal): View
    {
        abort_unless($proposal->approved_at, 404);
        $proposal->load(['theme', 'team', 'challenge.owner', 'files']);

        return view('admin.products.edit', [
            'proposal' => $proposal,
            'badges' => Options::publicBadges(),
        ]);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        abort_unless($proposal->approved_at, 404);
        $text = ['nullable', 'string', 'max:6000'];
        $data = $request->validate([
            'public_title' => ['required', 'string', 'max:190'],
            'public_short_description' => ['required', 'string', 'max:500'],
            'public_full_description' => ['nullable', 'string', 'max:20000'],
            'public_problem' => $text, 'public_solution' => $text, 'public_target_users' => $text,
            'public_technology' => $text, 'public_impact' => $text,
            'public_team_label' => ['nullable', 'string', 'max:190'],
            'public_badge' => ['nullable', Rule::in(array_keys(Options::publicBadges()))],
            'public_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', new FileContentType(['image/jpeg', 'image/png', 'image/webp']), 'max:'.(max(1, (int) setting('max_image_size_mb', 5)) * 1024)],
        ]);

        foreach (['public_show_team', 'public_show_tkt', 'public_show_links'] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        if ($request->hasFile('public_image')) {
            $stored = SecureFileStore::store($request->file('public_image'), 'public-products/'.$proposal->id);
            $old = $proposal->public_image_path;
            $proposal->forceFill(['public_image_path' => $stored['path']])->save();
            SecureFileStore::delete($old);
            Audit::log('product.image_replaced', $proposal);
        }

        (new ProposalWorkflow)->updatePublicSummary($proposal, collect($data)->except('public_image')->all(), $request->user());

        return back()->with('success', 'Ringkasan publik diperbarui.');
    }

    public function unpublish(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:1000']], ['reason.required' => 'Isi alasan pencabutan publikasi.']);
        (new ProposalWorkflow)->unpublish($proposal, $request->user(), $data['reason']);

        return back()->with('success', 'Publikasi dicabut. Halaman publik produk kini tidak dapat diakses; proposal, penilaian, dan log audit tetap tersimpan.');
    }

    public function publish(Request $request, Proposal $proposal): RedirectResponse
    {
        try {
            (new ProposalWorkflow)->republish($proposal, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Produk diterbitkan dan tampil di katalog publik.');
    }

    public function pdf(Request $request, Proposal $proposal): RedirectResponse
    {
        abort_unless($proposal->approved_at, 404);
        $enable = $request->boolean('enable');
        if ($enable && ! $proposal->currentFile('proposal_pdf')) {
            return back()->with('error', 'Proposal ini belum memiliki berkas PDF.');
        }
        (new ProposalWorkflow)->setPublicPdf($proposal, $enable, $request->user());

        return back()->with('success', $enable ? 'PDF proposal kini dapat diunduh publik dari halaman produk.' : 'PDF proposal kembali privat.');
    }
}
