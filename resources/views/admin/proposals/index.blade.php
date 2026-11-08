<x-layouts.portal title="Proposal">
    <x-page-header title="Proposal" subtitle="Saring berdasarkan tema, tantangan, mitra, status, kelengkapan, dan penugasan." />

    <form method="GET" class="card card-body mb-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Cari produk/tim/institusi…" class="form-input xl:col-span-2" aria-label="Cari">
        <x-form.select name="theme_id" :options="$themes" :value="$filters['theme_id'] ?? null" placeholder="Semua tema" aria-label="Tema" />
        <x-form.select name="challenge_id" :options="$challenges" :value="$filters['challenge_id'] ?? null" placeholder="Semua tantangan" aria-label="Tantangan" />
        <x-form.select name="owner_id" :options="$owners" :value="$filters['owner_id'] ?? null" placeholder="Semua mitra" aria-label="Mitra" />
        <x-form.select name="status" :options="$statuses" :value="$filters['status'] ?? null" placeholder="Semua status" aria-label="Status" />
        <x-form.select name="completeness" :options="['files_ok' => 'Berkas wajib lengkap', 'missing_files' => 'Berkas wajib kurang']" :value="$filters['completeness'] ?? null" placeholder="Semua kelengkapan" aria-label="Kelengkapan" />
        <x-form.select name="assignment" :options="['none' => 'Belum ditugaskan', 'assigned' => 'Sudah ditugaskan']" :value="$filters['assignment'] ?? null" placeholder="Semua penugasan" aria-label="Penugasan" />
        <div class="flex gap-2 xl:col-span-2">
            <button class="btn btn-dark flex-1">Terapkan</button>
            <a href="{{ route('admin.proposals.index') }}" class="btn btn-ghost">Atur ulang</a>
        </div>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Proposal</th><th>Tema</th><th>Tantangan</th><th>Status</th><th>Berkas</th><th>Reviewer</th><th>Skor rata-rata</th><th>Dikirim</th></tr></thead>
            <tbody>
                @forelse ($proposals as $proposal)
                    @php $hasPdf = $proposal->files->contains('type', 'proposal_pdf'); $hasImg = $proposal->files->contains('type', 'cover_image'); @endphp
                    <tr>
                        <td class="min-w-64">
                            <a href="{{ route('admin.proposals.show', $proposal) }}" class="font-semibold text-navy-950 hover:text-forest-700">{{ $proposal->product_name }}</a>
                            <p class="text-xs text-navy-500">{{ $proposal->team?->name }} · {{ $proposal->team?->institution }}</p>
                        </td>
                        <td><x-theme-badge :theme="$proposal->theme" /></td>
                        <td class="min-w-48 text-sm">{{ $proposal->challenge?->title ?? '—' }}<p class="text-xs text-navy-500">{{ $proposal->challenge?->owner?->displayName() }}</p></td>
                        <td><x-status-badge :status="$proposal->status" />@if ($proposal->is_public)<x-badge tone="success" class="mt-1">Publik</x-badge>@endif</td>
                        <td class="whitespace-nowrap text-xs">
                            <span class="{{ $hasPdf ? 'text-forest-700' : 'text-red-600' }}">PDF {{ $hasPdf ? '✓' : '✗' }}</span> ·
                            <span class="{{ $hasImg ? 'text-forest-700' : 'text-red-600' }}">Gambar {{ $hasImg ? '✓' : '✗' }}</span>
                        </td>
                        <td class="tabular-nums">{{ $proposal->active_assignments_count }} <span class="text-xs text-navy-500">({{ $proposal->submitted_reviews_count }} selesai)</span></td>
                        <td class="tabular-nums font-semibold">{{ $proposal->avg_score !== null ? number_format((float) $proposal->avg_score, 2, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap text-sm">{{ $proposal->submitted_at ? fmt_date($proposal->submitted_at) : 'Belum' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-navy-500">Tidak ada proposal yang cocok.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $proposals->links() }}
</x-layouts.portal>
