<x-layouts.portal title="Produk Disetujui">
    <x-page-header title="Produk disetujui" subtitle="Produk otomatis tampil publik saat disetujui. Admin dapat menyunting ringkasan publik, menyembunyikan, menerbitkan ulang, dan membuka PDF secara eksplisit.">
        <x-slot:actions>
            <form method="GET"><x-form.select name="visibility" :options="['public' => 'Tampil publik', 'hidden' => 'Tersembunyi']" :value="request('visibility')" placeholder="Semua" data-autosubmit class="w-44" aria-label="Visibilitas" /></form>
        </x-slot:actions>
    </x-page-header>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Produk</th><th>Tema</th><th>Status</th><th>Publikasi</th><th>PDF</th><th>Disetujui</th><th class="sr-only">Aksi</th></tr></thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td class="min-w-64"><p class="font-semibold text-navy-950">{{ $product->displayTitle() }}</p><p class="text-xs text-navy-500">{{ $product->team?->name }}</p></td>
                        <td><x-theme-badge :theme="$product->theme" /></td>
                        <td><x-status-badge :status="$product->status" /></td>
                        <td>@if ($product->is_public)<x-badge tone="success">Tampil</x-badge>@else<x-badge tone="danger">Tersembunyi</x-badge>@endif @if ($product->hidden_reason)<p class="mt-1 max-w-48 text-xs text-navy-500">{{ $product->hidden_reason }}</p>@endif</td>
                        <td>{{ $product->public_pdf_enabled ? 'Publik' : 'Privat' }}</td>
                        <td class="whitespace-nowrap">{{ fmt_date($product->approved_at) }}</td>
                        <td class="whitespace-nowrap text-right">
                            @if ($product->is_public)<a href="{{ route('products.show', $product->public_slug) }}" target="_blank" class="btn btn-ghost btn-sm">Lihat</a>@endif
                            <a href="{{ route('admin.products.edit', $product) }}" class="btn btn-outline btn-sm">Kelola</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-8 text-center text-navy-500">Belum ada produk disetujui.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $products->links() }}
</x-layouts.portal>
