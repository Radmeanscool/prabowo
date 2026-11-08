<x-layouts.portal title="Rubrik & Kriteria">
    <x-page-header title="Rubrik dan kriteria" subtitle="Kriteria awal hanyalah contoh dan dapat diubah. Skor akhir = Σ(skor ÷ maks × bobot) ÷ Σbobot × 100, dihitung di server.">
        <x-slot:actions>
            <a href="{{ route('admin.review-phases.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Fase baru</a>
        </x-slot:actions>
    </x-page-header>

    @foreach ($phases as $phase)
        @php $total = $phase->criteria->where('is_active', true)->sum(fn ($c) => (float) $c->weight); @endphp
        <section class="card mb-6">
            <div class="card-body flex flex-col gap-3 border-b border-navy-100 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h2 class="card-title">{{ $phase->name }} @unless ($phase->is_active)<x-badge>Nonaktif</x-badge>@endunless</h2>
                    <p class="mt-1 text-sm text-navy-600">{{ $phase->description }}</p>
                    <p class="mt-1 text-xs text-navy-500">{{ $phase->assignments_count }} penugasan · batas {{ $phase->ends_at ? fmt_date($phase->ends_at, true) : 'belum ditetapkan' }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <x-badge :tone="abs($total - 100) < 0.01 ? 'success' : 'warning'">Total bobot aktif {{ rtrim(rtrim(number_format($total, 2, ',', '.'), '0'), ',') }}%</x-badge>
                    <a href="{{ route('admin.review-phases.edit', $phase) }}" class="btn btn-outline btn-sm">Ubah fase</a>
                    <a href="{{ route('admin.criteria.create', ['phase' => $phase->id]) }}" class="btn btn-dark btn-sm"><x-icon name="plus" class="size-4" /> Kriteria</a>
                </div>
            </div>
            <div class="table-wrap rounded-none border-0">
                <table class="table">
                    <thead><tr><th>#</th><th>Kriteria</th><th>Tema</th><th>Maks</th><th>Bobot</th><th>Komentar</th><th>Aktif</th><th class="sr-only">Aksi</th></tr></thead>
                    <tbody>
                        @forelse ($phase->criteria as $criterion)
                            <tr class="{{ $criterion->is_active ? '' : 'opacity-50' }}">
                                <td class="tabular-nums">{{ $criterion->sort_order }}</td>
                                <td class="min-w-64"><p class="font-semibold">{{ $criterion->name }}</p><p class="text-xs text-navy-500">{{ $criterion->description }}</p></td>
                                <td>{{ $criterion->theme?->name ?? 'Semua' }}</td>
                                <td class="tabular-nums">{{ $criterion->max_score }}</td>
                                <td class="tabular-nums">{{ rtrim(rtrim(number_format((float) $criterion->weight, 2, ',', '.'), '0'), ',') }}%</td>
                                <td>{{ $criterion->requires_comment ? 'Wajib' : 'Opsional' }}</td>
                                <td>{{ $criterion->is_active ? 'Ya' : 'Tidak' }}</td>
                                <td class="whitespace-nowrap text-right">
                                    <a href="{{ route('admin.criteria.edit', $criterion) }}" class="btn btn-outline btn-sm">Ubah</a>
                                    <form method="POST" action="{{ route('admin.criteria.destroy', $criterion) }}" class="inline" data-confirm="Hapus kriteria ini? Bila sudah dipakai, kriteria hanya dinonaktifkan.">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-danger-outline btn-sm" aria-label="Hapus"><x-icon name="trash" class="size-3.5" /></button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-navy-500">Belum ada kriteria.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
</x-layouts.portal>
