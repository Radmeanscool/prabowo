<x-layouts.portal title="Berkas Proposal">
    <x-page-header eyebrow="Langkah 2 dari 3" title="Berkas proposal" :subtitle="$proposal->product_name.' · Semua berkas disimpan privat dan hanya dapat dibuka oleh tim, reviewer yang ditugaskan, dan admin.'">
        <x-slot:actions>
            @if ($canEdit)
                <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-outline btn-sm"><x-icon name="arrow-left" class="size-4" /> Kembali ke formulir</a>
                <a href="{{ route('portal.proposals.confirm', $proposal) }}" class="btn btn-primary btn-sm">Lanjut ke pengiriman <x-icon name="arrow-right" class="size-4" /></a>
            @endif
        </x-slot:actions>
    </x-page-header>

    @php
        $descriptions = [
            'cover_image' => ['JPEG, PNG, atau WebP · maks. '.$maxImage.' MB · minimal 400×300 piksel. Dipakai sebagai gambar produk; hanya tampil publik bila produk disetujui.', 'image/jpeg,image/png,image/webp', true],
            'proposal_pdf' => ['PDF · maks. '.$maxPdf.' MB. Proposal resmi sesuai format yang diumumkan panitia. Tetap privat.', 'application/pdf', true],
            'technical_appendix' => ['PDF · maks. '.$maxPdf.' MB. Opsional: gambar teknik, data uji, spesifikasi.', 'application/pdf', false],
            'supporting_evidence' => ['PDF atau gambar · maks. '.$maxPdf.' MB · hingga 5 berkas. Opsional: foto purwarupa, surat dukungan mitra, hasil uji.', 'application/pdf,image/jpeg,image/png,image/webp', false],
            'legal_evidence' => ['PDF · maks. '.$maxPdf.' MB · hingga 5 berkas. Opsional: bukti KI, izin, sertifikat, perjanjian. Bersifat rahasia.', 'application/pdf', false],
        ];
    @endphp

    <div class="grid gap-5 lg:grid-cols-2">
        @foreach ($types as $type => $config)
            @php [$hint, $accept, $required] = $descriptions[$type]; $files = $proposal->filesOfType($type); @endphp
            <div class="card card-body">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h2 class="font-bold text-navy-950">{{ $labels[$type] }} @if ($required)<span class="text-red-600">*</span>@endif</h2>
                        <p class="mt-1 text-xs leading-relaxed text-navy-500">{{ $hint }}</p>
                    </div>
                    @if ($files->isNotEmpty())
                        <x-badge tone="success"><x-icon name="check" class="size-3" stroke="3" /> Terunggah</x-badge>
                    @elseif ($required)
                        <x-badge tone="warning">Wajib</x-badge>
                    @endif
                </div>

                @foreach ($files as $file)
                    <div class="mt-4 flex items-center gap-3 rounded-xl border border-navy-100 p-3">
                        @if ($file->isImage())
                            <img src="{{ route('files.show', $file->uuid) }}" alt="Pratinjau {{ $labels[$type] }}" class="size-14 rounded-lg object-cover">
                        @else
                            <span class="flex size-14 items-center justify-center rounded-lg bg-navy-50 text-navy-500"><x-icon name="file" /></span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ $file->original_name }}</p>
                            <p class="text-xs text-navy-500">{{ format_bytes($file->size) }} · {{ fmt_date($file->created_at, true) }}</p>
                        </div>
                        <a href="{{ route('files.show', $file->uuid) }}" target="_blank" class="btn btn-ghost btn-sm">Buka</a>
                        @if ($canEdit)
                            <form method="POST" action="{{ route('portal.proposals.files.destroy', [$proposal, $file->uuid]) }}" data-confirm="Hapus berkas ini?">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger-outline btn-sm" aria-label="Hapus {{ $file->original_name }}"><x-icon name="trash" class="size-4" /></button>
                            </form>
                        @endif
                    </div>
                @endforeach

                @if ($canEdit && ($config['single'] || $files->count() < ($config['max'] ?? 5)))
                    <form method="POST" action="{{ route('portal.proposals.files.store', $proposal) }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-start">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <x-form.file name="file" :accept="$accept" :id="'file_'.$type" class="flex-1" :required="true" />
                        <button class="btn btn-dark shrink-0"><x-icon name="upload" class="size-4" /> {{ $config['single'] && $files->isNotEmpty() ? 'Ganti' : 'Unggah' }}</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
</x-layouts.portal>
