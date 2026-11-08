<x-layouts.public title="Produk Disetujui" description="Katalog produk inovasi DEEPNEST yang telah disetujui.">
    <x-page-hero title="Produk inovasi yang disetujui" eyebrow="Etalase produk" lead="Ringkasan publik produk DEEPNEST yang telah melalui seleksi dan keputusan panitia. Proposal dan dokumen asli tetap rahasia." />

    <section class="section">
        <div class="container-page">
            <form method="GET" class="card grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
                <label class="sr-only" for="q">Cari produk</label>
                <input type="search" id="q" name="q" value="{{ request('q') }}" placeholder="Cari nama produk…" class="form-input lg:col-span-2">
                <select name="tema" class="form-select" aria-label="Tema">
                    <option value="">Semua tema</option>
                    @foreach ($themes as $theme)<option value="{{ $theme->code }}" @selected($activeTheme?->id === $theme->id)>{{ $theme->name }}</option>@endforeach
                </select>
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
                <div class="flex gap-2 lg:col-span-3">
                    <button class="btn btn-dark flex-1"><x-icon name="search" class="size-4" /> Terapkan</button>
                    <a href="{{ route('products.index') }}" class="btn btn-ghost">Atur ulang</a>
                </div>
            </form>

            @if ($products->isEmpty())
                <x-empty class="mt-10" icon="sparkles" title="Belum ada produk yang ditampilkan" text="Produk tampil setelah disetujui panitia. Coba ubah penyaring bila Anda sedang mencari produk tertentu." />
            @else
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
                {{ $products->links() }}
            @endif
        </div>
    </section>
</x-layouts.public>
