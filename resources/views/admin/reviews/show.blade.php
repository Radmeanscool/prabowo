<x-layouts.portal title="Detail Penilaian">
    <x-page-header :eyebrow="$review->phase?->name" :title="'Penilaian: '.$review->proposal?->product_name" :subtitle="'Reviewer: '.$review->reviewer?->name">
        <x-slot:actions><a href="{{ route('admin.proposals.show', $review->proposal_id) }}" class="btn btn-ghost btn-sm"><x-icon name="arrow-left" class="size-4" /> Proposal</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Kriteria</th><th>Bobot</th><th>Skor</th><th>Komentar</th></tr></thead>
                    <tbody>
                        @foreach ($review->scores as $score)
                            <tr>
                                <td class="min-w-48 font-medium">{{ $score->criterion?->name }}</td>
                                <td class="tabular-nums">{{ rtrim(rtrim(number_format((float) $score->criterion?->weight, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="tabular-nums font-bold">{{ $score->score ?? '—' }} / {{ $score->criterion?->max_score }}</td>
                                <td class="min-w-64 whitespace-pre-line text-navy-700">{{ $score->comment ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card card-body">
                <h2 class="card-title">Komentar umum</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-navy-700">{{ $review->overall_comment ?: '—' }}</p>
                <h2 class="card-title mt-6">Catatan pribadi untuk admin</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-navy-700">{{ $review->private_notes ?: '—' }}</p>
            </div>
        </div>
        <div class="space-y-6">
            <div class="card card-body">
                <p class="text-sm text-navy-500">Skor tertimbang (dihitung server)</p>
                <p class="text-4xl font-extrabold tabular-nums">{{ $review->weighted_score !== null ? number_format((float) $review->weighted_score, 2, ',', '.') : '—' }}</p>
                <dl class="data-list mt-3">
                    <div><dt>Status</dt><dd>{{ $review->isSubmitted() ? 'Final (terkunci)' : 'Draf' }}</dd></div>
                    <div><dt>Rekomendasi</dt><dd>{{ $review->recommendationLabel() }}</dd></div>
                    <div><dt>Dikirim</dt><dd>{{ $review->submitted_at ? fmt_date($review->submitted_at, true) : '—' }}</dd></div>
                    @if ($review->reopened_at)<div><dt>Dibuka kembali</dt><dd>{{ fmt_date($review->reopened_at, true) }} · {{ $review->reopenedBy?->name }}</dd></div>@endif
                </dl>
            </div>
            @if ($review->isSubmitted())
                <form method="POST" action="{{ route('admin.reviews.reopen', $review) }}" class="card card-body space-y-3" data-confirm="Buka kembali penilaian ini? Reviewer dapat mengubah nilai.">
                    @csrf
                    <h2 class="card-title">Buka kembali penilaian</h2>
                    <x-form.textarea name="reason" label="Alasan" rows="2" required />
                    <button class="btn btn-outline">Buka Kembali</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.portal>
