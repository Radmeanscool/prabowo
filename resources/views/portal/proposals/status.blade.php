<x-layouts.portal title="Status Pengajuan">
    <x-page-header eyebrow="Status pengajuan" :title="$proposal->product_name">
        <x-slot:actions>
            <a href="{{ route('portal.proposals.show', $proposal) }}" class="btn btn-outline btn-sm">Lihat Proposal</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm font-semibold text-navy-500">Status saat ini</span>
                    <x-status-badge :status="$proposal->status" class="text-sm" />
                    @if ($proposal->programStage)<x-badge tone="teal">Tahap: {{ $proposal->programStage->name }}</x-badge>@endif
                </div>
                <dl class="data-list mt-3">
                    <div><dt>Tema</dt><dd><x-theme-badge :theme="$proposal->theme" /></dd></div>
                    <div><dt>Tantangan</dt><dd>{{ $proposal->challenge?->title ?? '—' }}</dd></div>
                    <div><dt>Pertama dikirim</dt><dd>{{ $proposal->first_submitted_at ? fmt_date($proposal->first_submitted_at, true) : 'Belum dikirim' }}</dd></div>
                    <div><dt>Pengiriman terakhir</dt><dd>{{ $proposal->submitted_at ? fmt_date($proposal->submitted_at, true).' · versi '.$proposal->version : '—' }}</dd></div>
                    @if ($proposal->is_public)
                        <div><dt>Publikasi</dt><dd><a href="{{ route('products.show', $proposal->public_slug) }}" class="font-semibold text-forest-700 underline">Tampil di katalog produk</a></dd></div>
                    @endif
                </dl>
                @if ($proposal->decision_note && in_array($proposal->status->value, ['revision_requested', 'rejected', 'accepted', 'approved', 'shortlisted'], true))
                    <div class="mt-4 rounded-xl border border-navy-100 bg-navy-50/60 p-4 text-sm">
                        <p class="font-bold">Catatan panitia</p>
                        <p class="mt-1 whitespace-pre-line text-navy-800">{{ $proposal->decision_note }}</p>
                    </div>
                @endif
                @if ($proposal->isEditableByParticipant())
                    <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-dark mt-4">{{ $proposal->status->value === 'revision_requested' ? 'Revisi Proposal' : 'Lanjutkan Mengisi' }}</a>
                @endif
            </div>

            @if ($reviews->isNotEmpty())
                <div class="card card-body">
                    <h2 class="card-title">Hasil penilaian</h2>
                    <p class="text-xs text-navy-500">Identitas reviewer tidak ditampilkan.</p>
                    @foreach ($reviews as $review)
                        <div class="mt-4 rounded-xl border border-navy-100 p-4">
                            <p class="text-sm font-semibold">{{ $review->phase?->name }} · Penilai {{ $loop->iteration }}</p>
                            @if ($showScores)<p class="mt-1 text-2xl font-extrabold tabular-nums">{{ number_format((float) $review->weighted_score, 2, ',', '.') }} <span class="text-sm font-medium text-navy-500">/ 100</span></p>@endif
                            @if ($showComments && $review->overall_comment)<p class="mt-2 whitespace-pre-line text-sm text-navy-700">{{ $review->overall_comment }}</p>@endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="space-y-6">
            <div class="card card-body">
                <h2 class="card-title">Riwayat status</h2>
                <ol class="relative mt-4 space-y-5 border-l-2 border-navy-100 pl-5">
                    @foreach ($proposal->statusHistories as $history)
                        <li class="relative">
                            <span class="absolute -left-[1.72rem] top-1 size-3 rounded-full border-2 border-white bg-forest-500 ring-2 ring-forest-100"></span>
                            <x-status-badge :status="$history->to_status" />
                            <p class="mt-1 text-xs text-navy-500">{{ fmt_date($history->created_at, true) }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
            @if ($proposal->versions->isNotEmpty())
                <div class="card card-body">
                    <h2 class="card-title">Versi terkirim</h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        @foreach ($proposal->versions as $version)
                            <li class="flex justify-between"><span class="font-semibold">Versi {{ $version->version }}</span><span class="text-navy-500">{{ fmt_date($version->submitted_at, true) }}</span></li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</x-layouts.portal>
