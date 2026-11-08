<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\ProposalFile;
use App\Rules\FileContentType;
use App\Services\Audit;
use App\Services\SecureFileStore;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProposalFileController extends Controller
{
    /** Jenis berkas yang dapat diunggah peserta, dan apakah satu berkas saja (diganti) atau boleh banyak. */
    public const PARTICIPANT_TYPES = [
        'cover_image' => ['single' => true, 'kind' => 'image'],
        'proposal_pdf' => ['single' => true, 'kind' => 'pdf'],
        'technical_appendix' => ['single' => true, 'kind' => 'pdf'],
        'supporting_evidence' => ['single' => false, 'kind' => 'pdf_or_image', 'max' => 5],
        'legal_evidence' => ['single' => false, 'kind' => 'pdf', 'max' => 5],
    ];

    public function index(Request $request, Proposal $proposal): View
    {
        $this->authorize('view', $proposal);
        $proposal->load('files');

        return view('portal.proposals.files', [
            'proposal' => $proposal,
            'types' => self::PARTICIPANT_TYPES,
            'labels' => Options::fileTypes(),
            'canEdit' => $request->user()->can('update', $proposal),
            'maxPdf' => (int) setting('max_pdf_size_mb', 10),
            'maxImage' => (int) setting('max_image_size_mb', 5),
        ]);
    }

    public function store(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $request->validate(['type' => ['required', Rule::in(array_keys(self::PARTICIPANT_TYPES))]]);
        $type = $request->input('type');
        $config = self::PARTICIPANT_TYPES[$type];

        $request->validate(['file' => array_merge(['required', 'file'], self::rulesFor($config['kind']))], [
            'file.mimes' => 'Jenis berkas tidak diizinkan untuk kategori ini.',
            'file.mimetypes' => 'Isi berkas tidak sesuai dengan jenis yang diizinkan.',
            'file.max' => 'Ukuran berkas melebihi batas.',
            'file.dimensions' => 'Dimensi gambar minimal 400×300 piksel.',
        ]);

        if (! $config['single'] && $proposal->files()->where('type', $type)->count() >= ($config['max'] ?? 5)) {
            return back()->with('error', 'Jumlah berkas untuk kategori ini sudah maksimal.');
        }

        $file = SecureFileStore::storeProposalFile($proposal, $request->file('file'), $type, $request->user());

        if ($config['single']) {
            // Berkas lama tidak dihapus permanen (riwayat versi tetap dapat ditelusuri admin).
            $proposal->files()->where('type', $type)->whereKeyNot($file->id)->get()->each->delete();
        }

        Audit::log('file.uploaded', $proposal, [], ['file' => $file->uuid, 'type' => $type, 'size' => $file->size]);

        return back()->with('success', Options::label(Options::fileTypes(), $type).' berhasil diunggah.');
    }

    public function destroy(Request $request, Proposal $proposal, string $file): RedirectResponse
    {
        $record = ProposalFile::query()->where('uuid', $file)->where('proposal_id', $proposal->id)->firstOrFail();
        $this->authorize('delete', $record);

        $record->delete();
        Audit::log('file.deleted', $proposal, ['file' => $record->uuid, 'type' => $record->type], []);

        return back()->with('success', 'Berkas dihapus.');
    }

    /** Aturan validasi berkas di sisi server (MIME dari isi berkas, ekstensi, dan ukuran). */
    public static function rulesFor(string $kind): array
    {
        $pdfKb = max(1, (int) setting('max_pdf_size_mb', 10)) * 1024;
        $imageKb = max(1, (int) setting('max_image_size_mb', 5)) * 1024;

        $images = ['image/jpeg', 'image/png', 'image/webp'];

        return match ($kind) {
            'image' => ['mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', new FileContentType($images), 'max:'.$imageKb, 'dimensions:min_width=400,min_height=300'],
            'pdf' => ['mimes:pdf', 'mimetypes:application/pdf', new FileContentType(['application/pdf']), 'max:'.$pdfKb],
            default => ['mimes:pdf,jpg,jpeg,png,webp', 'mimetypes:application/pdf,image/jpeg,image/png,image/webp', new FileContentType(array_merge(['application/pdf'], $images)), 'max:'.$pdfKb],
        };
    }
}
