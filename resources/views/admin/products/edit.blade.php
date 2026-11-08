<x-layouts.portal title="Kelola Publikasi">
    <x-page-header eyebrow="Produk disetujui" :title="$proposal->displayTitle()" subtitle="Ringkasan publik disunting terpisah dari isian privat proposal. Hanya ringkasan ini yang tampil di katalog.">
        <x-slot:actions>
            <a href="{{ route('admin.proposals.show', $proposal) }}" class="btn btn-ghost btn-sm"><x-icon name="arrow-left" class="size-4" /> Proposal</a>
            @if ($proposal->is_public)<a href="{{ route('products.show', $proposal->public_slug) }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="globe" class="size-4" /> Halaman publik</a>@endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <form method="POST" action="{{ route('admin.products.update', $proposal) }}" enctype="multipart/form-data" class="card card-body space-y-4 xl:col-span-2">
            @csrf
            @method('PUT')
            <x-form.input name="public_title" label="Judul publik" :value="$proposal->public_title" required />
            <x-form.textarea name="public_short_description" label="Deskripsi singkat (kartu produk)" :value="$proposal->public_short_description" rows="2" required />
            <x-form.textarea name="public_full_description" label="Deskripsi lengkap" :value="$proposal->public_full_description" rows="6" help="Mendukung Markdown. Jangan memuat data rahasia, nilai, atau catatan reviewer." />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.textarea name="public_problem" label="Masalah yang dijawab" :value="$proposal->public_problem" rows="3" />
                <x-form.textarea name="public_solution" label="Solusi yang diusulkan/didemonstrasikan" :value="$proposal->public_solution" rows="3" />
                <x-form.textarea name="public_target_users" label="Pengguna/penerima manfaat" :value="$proposal->public_target_users" rows="3" />
                <x-form.textarea name="public_technology" label="Ringkasan teknologi" :value="$proposal->public_technology" rows="3" />
            </div>
            <x-form.textarea name="public_impact" label="Indikator dampak" :value="$proposal->public_impact" rows="3" />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select name="public_badge" label="Lencana status" :options="$badges" :value="$proposal->public_badge" placeholder="Tanpa lencana" help="Tampilkan hanya status yang sudah resmi." />
                <x-form.input name="public_team_label" label="Label tim/institusi publik" :value="$proposal->public_team_label" :placeholder="$proposal->team?->name.' · '.$proposal->team?->institution" help="Kosongkan untuk memakai nama tim dan institusi." />
            </div>
            <div class="grid gap-3 rounded-xl border border-navy-100 p-4 sm:grid-cols-3">
                <x-form.checkbox name="public_show_team" label="Tampilkan tim/institusi" :checked="$proposal->public_show_team" />
                <x-form.checkbox name="public_show_tkt" label="Tampilkan TKT" :checked="$proposal->public_show_tkt" />
                <x-form.checkbox name="public_show_links" label="Tampilkan tautan demo/repositori" :checked="$proposal->public_show_links" />
            </div>
            <div class="flex flex-col gap-4 rounded-xl border border-navy-100 p-4 sm:flex-row sm:items-center">
                @if ($proposal->public_image_path)
                    <img src="{{ route('admin.media', ['type' => 'product', 'id' => $proposal->id]) }}" alt="Gambar publik" class="h-24 w-36 rounded-lg object-cover">
                @else
                    <span class="flex h-24 w-36 items-center justify-center rounded-lg bg-navy-50 text-xs text-navy-400">Tanpa gambar</span>
                @endif
                <x-form.file name="public_image" label="Ganti gambar publik (opsional)" accept="image/jpeg,image/png,image/webp" help="Salinan gambar terpisah dari berkas peserta." class="flex-1" />
            </div>
            <button class="btn btn-dark">Simpan Ringkasan Publik</button>
        </form>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title">Status publikasi</h2>
                @if ($proposal->is_public)
                    <p class="mt-2 text-sm"><x-badge tone="success">Tampil di katalog</x-badge> sejak {{ fmt_date($proposal->published_at, true) }}</p>
                    <form method="POST" action="{{ route('admin.products.unpublish', $proposal) }}" class="mt-4 space-y-3" data-confirm="Cabut publikasi produk ini? Halaman publik akan tidak dapat diakses.">
                        @csrf
                        <x-form.textarea name="reason" label="Alasan pencabutan" rows="2" required help="Mis. permintaan kerahasiaan mitra, koreksi data, persetujuan keliru." />
                        <button class="btn btn-danger w-full"><x-icon name="eye-off" class="size-4" /> Cabut Publikasi</button>
                    </form>
                @else
                    <p class="mt-2 text-sm"><x-badge tone="danger">Tersembunyi</x-badge> @if ($proposal->hidden_reason)· {{ $proposal->hidden_reason }}@endif</p>
                    @if ($proposal->isPublishable())
                        <form method="POST" action="{{ route('admin.products.publish', $proposal) }}" class="mt-4" data-confirm="Terbitkan produk ini ke katalog publik?">
                            @csrf
                            <button class="btn btn-forest w-full"><x-icon name="globe" class="size-4" /> Terbitkan Produk</button>
                        </form>
                    @else
                        <p class="mt-3 text-xs text-navy-500">Produk dengan status {{ $proposal->status->label() }} tidak dapat diterbitkan.</p>
                    @endif
                @endif
                <p class="mt-4 text-xs text-navy-500">Menyembunyikan produk tidak menghapus proposal, penilaian, maupun log audit.</p>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Akses PDF proposal</h2>
                <p class="mt-1 text-sm text-navy-600">PDF asli bersifat privat. Buka untuk publik hanya bila tim dan mitra mengizinkan.</p>
                <p class="mt-3 text-sm">Saat ini: <strong>{{ $proposal->public_pdf_enabled ? 'dapat diunduh publik' : 'privat' }}</strong></p>
                <form method="POST" action="{{ route('admin.products.pdf', $proposal) }}" class="mt-3" data-confirm="{{ $proposal->public_pdf_enabled ? 'Jadikan PDF privat kembali?' : 'Buka PDF proposal untuk publik?' }}">
                    @csrf
                    <input type="hidden" name="enable" value="{{ $proposal->public_pdf_enabled ? 0 : 1 }}">
                    <button class="btn {{ $proposal->public_pdf_enabled ? 'btn-outline' : 'btn-danger-outline' }} w-full">{{ $proposal->public_pdf_enabled ? 'Jadikan PDF Privat' : 'Buka PDF untuk Publik' }}</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.portal>
