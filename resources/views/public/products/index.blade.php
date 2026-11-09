<x-layouts.public title="Produk Disetujui" description="Katalog produk inovasi DEEPNEST yang telah disetujui.">
    <x-page-hero title="Produk inovasi yang disetujui" eyebrow="Etalase produk" lead="Ringkasan publik produk DEEPNEST yang telah melalui seleksi dan keputusan panitia. Proposal dan dokumen asli tetap rahasia." />

    @php $filtering = request()->hasAny(['q', 'tema', 'mitra', 'tahap', 'tahun', 'institusi', 'urut']); @endphp
    <section class="section pt-10 sm:pt-12">
        <div class="container-page">
            @if ($products->isEmpty() && ! $filtering)
                <div class="grid gap-8 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-7">
                        <p class="subhead">Belum ada produk yang disetujui.</p>
                        <p class="mt-3 leading-relaxed text-navy-600">Produk tampil di halaman ini setelah melalui seleksi dan keputusan panitia. Yang ditampilkan hanya ringkasan publik; proposal dan dokumen asli tetap rahasia.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:col-span-5 lg:justify-end lg:self-end">
                        <a href="{{ route('stages') }}" class="btn btn-outline">Tahapan Program</a>
                    </div>
                </div>
            @else
                <form method="GET" class="grid gap-3 border-y border-navy-950 py-4 sm:grid-cols-2 lg:grid-cols-6">
                    <label class="sr-only" for="q">Cari produk</label>
                    <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama produk…" class="form-input lg:col-span-2">
                    <select name="tema" class="form-select" aria-label="Tema">
                        <option value="">Semua tema</option>
                        @foreach ($themes as $theme)<option value="{{ $theme->code }}" @selected($activeTheme?->id === $theme->id)>{{ $theme->name }}</option>@endforeach
                    </select>
                    <button type="button" class="btn btn-outline lg:hidden" data-toggle="#product-filters" data-toggle-class="is-open" aria-controls="product-filters" aria-expanded="false">
                        <x-icon name="settings" class="size-4" /> Filter lainnya
                    </button>
                    <div id="product-filters" class="filter-extra {{ request()->hasAny(['mitra', 'tahap', 'tahun', 'institusi', 'urut']) ? 'is-open' : '' }}">
                        <select name="mitra" class="form-select" aria-label="Mitra">
                            <option value="">Semua mitra</option>
                            @foreach ($owners as $slug => $name)<option value="{{ $slug }}" @selected(request('mitra') === $slug)>{{ $name }}</option>@endforeach
                        </select>
                        <select name="tahap" class="form-select" aria-label="Tahap program">
                            <option value="">Semua tahap</option>
                            @foreach ($stages as $code => $name)<option value="{{ $code }}" @selected(request('tahap') === $code)>{{ $name }}</option>@endforeach
                        </select>
                        <select name="tahun" class="form-select" aria-label="Tahun terbit">
                            <option value="">Semua tahun</option>
                            @foreach ($years as $year)<option value="{{ $year }}" @selected(request('tahun') == $year)>{{ $year }}</option>@endforeach
                        </select>
                        <input type="search" name="institusi" value="{{ request('institusi') }}" placeholder="Institusi" class="form-input lg:col-span-2" aria-label="Institusi">
                        <select name="urut" class="form-select" aria-label="Urutkan">
                            <option value="">Terbaru</option>
                            <option value="terlama" @selected(request('urut') === 'terlama')>Terlama</option>
                        </select>
                    </div>
                    <div class="flex gap-2 lg:col-span-3">
                        <button class="btn btn-primary flex-1"><x-icon name="search" class="size-4" /> Terapkan</button>
                        <a href="{{ route('products.index') }}" class="btn btn-ghost">Atur ulang</a>
                    </div>
                </form>

                @if ($products->isEmpty())
                    <x-empty class="mt-10" icon="search" title="Tidak ada produk yang cocok" text="Coba ubah atau atur ulang penyaring." />
                @else
                    <div class="mt-12 grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                    {{ $products->links() }}
                @endif
            @endif
        </div>
    </section>
</x-layouts.public>
