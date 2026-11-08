<x-layouts.portal title="Penilaian">
    <x-page-header title="Penilaian" subtitle="Semua penilaian reviewer. Admin dapat membuka kembali penilaian final; nilai lama tersimpan di log audit." />
    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <x-form.select name="phase_id" :options="$phases" :value="request('phase_id')" placeholder="Semua fase" data-autosubmit class="w-60" aria-label="Fase" />
        <x-form.select name="status" :options="['draft' => 'Draf', 'submitted' => 'Final']" :value="request('status')" placeholder="Semua status" data-autosubmit class="w-44" aria-label="Status" />
        <x-form.select name="reviewer_id" :options="$reviewers" :value="request('reviewer_id')" placeholder="Semua reviewer" data-autosubmit class="w-60" aria-label="Reviewer" />
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Proposal</th><th>Reviewer</th><th>Fase</th><th>Status</th><th>Rekomendasi</th><th>Skor</th><th>Diperbarui</th><th class="sr-only">Aksi</th></tr></thead>
            <tbody>
                @forelse ($reviews as $review)
                    <tr>
                        <td class="min-w-56"><a href="{{ route('admin.proposals.show', $review->proposal_id) }}" class="font-semibold text-navy-950 hover:text-forest-700">{{ $review->proposal?->product_name }}</a></td>
                        <td>{{ $review->reviewer?->name }}</td>
                        <td>{{ $review->phase?->name }}</td>
                        <td><x-badge :tone="$review->isSubmitted() ? 'success' : 'warning'">{{ $review->isSubmitted() ? 'Final' : 'Draf' }}</x-badge>@if ($review->assignment?->status === 'removed')<x-badge class="ml-1">Penugasan dicabut</x-badge>@endif</td>
                        <td>{{ $review->recommendationLabel() }}</td>
                        <td class="font-semibold tabular-nums">{{ $review->weighted_score !== null ? number_format((float) $review->weighted_score, 2, ',', '.') : '—' }}</td>
                        <td class="whitespace-nowrap">{{ fmt_date($review->updated_at, true) }}</td>
                        <td class="text-right"><a href="{{ route('admin.reviews.show', $review) }}" class="btn btn-outline btn-sm">Detail</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="py-8 text-center text-navy-500">Belum ada penilaian.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $reviews->links() }}
</x-layouts.portal>
