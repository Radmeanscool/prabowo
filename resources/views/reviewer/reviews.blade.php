<x-layouts.portal :title="$status === 'submitted' ? 'Penilaian Terkirim' : 'Draf Penilaian'">
    <x-page-header :title="$status === 'submitted' ? 'Penilaian terkirim' : 'Draf penilaian'" :subtitle="$status === 'submitted' ? 'Penilaian final terkunci. Admin dapat membukanya kembali bila perlu koreksi.' : 'Penilaian yang sudah disimpan namun belum dikirim final.'" />
    @if ($reviews->isEmpty())
        <x-empty icon="list-check" :title="$status === 'submitted' ? 'Belum ada penilaian terkirim' : 'Tidak ada draf'" />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Proposal</th><th>Fase</th><th>Rekomendasi</th><th>Skor tertimbang</th><th>Diperbarui</th><th class="sr-only">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($reviews as $review)
                        <tr>
                            <td class="font-semibold">{{ $review->proposal->product_name }}</td>
                            <td>{{ $review->phase->name }}</td>
                            <td>{{ $review->recommendationLabel() }}</td>
                            <td class="tabular-nums">{{ $review->weighted_score !== null ? number_format((float) $review->weighted_score, 2, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap">{{ fmt_date($review->submitted_at ?? $review->updated_at, true) }}</td>
                            <td class="text-right"><a href="{{ route('reviewer.assignments.show', $review->assignment) }}" class="btn btn-outline btn-sm">Buka</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $reviews->links() }}
    @endif
</x-layouts.portal>
