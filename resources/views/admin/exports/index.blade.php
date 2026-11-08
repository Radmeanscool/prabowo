<x-layouts.portal title="Laporan & Ekspor">
    <x-page-header title="Laporan dan ekspor" subtitle="Unduh data dalam CSV (UTF-8). Untuk Excel berbahasa Indonesia, pilih pemisah titik koma. Setiap unduhan tercatat di log audit." />
    <div class="grid gap-4 md:grid-cols-2">
        @foreach ($types as $type => $label)
            <div class="card card-body flex flex-col justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex size-10 items-center justify-center rounded-xl bg-navy-50 text-navy-600"><x-icon name="download" /></span>
                    <p class="font-semibold text-navy-950">{{ $label }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.exports.download', ['type' => $type, 'delimiter' => 'semicolon']) }}" class="btn btn-dark btn-sm">CSV untuk Excel (;)</a>
                    <a href="{{ route('admin.exports.download', ['type' => $type]) }}" class="btn btn-outline btn-sm">CSV standar (,)</a>
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.portal>
