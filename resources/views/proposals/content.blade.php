{{--
    Tampilan baca-saja isi proposal, dipakai oleh peserta, reviewer, dan admin.
    $blind = true menyembunyikan identitas tim (opsi review buta).
    $viewer: participant | reviewer | admin
--}}
@php
    $blind = $blind ?? false;
    $viewer = $viewer ?? 'participant';
    $field = fn ($label, $value) => ['label' => $label, 'value' => $value];
    $blocks = [
        'D. Aspek teknis' => [
            $field('Deskripsi teknis', $proposal->technical_description),
            $field('Validasi yang sudah dilakukan', $proposal->technical_validation),
            $field('Keterbatasan teknis utama', $proposal->technical_limitations),
        ],
        'E. Pasar, pengguna, dan dampak' => [
            $field('Pengguna sasaran', $proposal->target_users),
            $field('Kebutuhan pasar/operasional', $proposal->market_description),
            $field('Alternatif dan kompetitor', $proposal->competitors),
            $field('Nilai tambah', $proposal->value_proposition),
            $field('Metode validasi', $proposal->market_validation),
            $field('Dampak yang diharapkan', $proposal->impact_description),
            $field('Rencana adopsi', $proposal->adoption_plan),
        ],
        'F. Kesesuaian dengan tantangan' => [
            $field('Masalah yang dijawab', $proposal->problem_statement),
            $field('Mengapa penting', $proposal->challenge_importance),
            $field('Usulan solusi', $proposal->solution_description),
            $field('Fungsi minimum', $proposal->minimum_functions),
            $field('Indikator keberhasilan', $proposal->success_indicators),
            $field('Kebutuhan data dan fasilitas', $proposal->data_facility_needs),
            $field('Lokasi pilot/calon pengguna', $proposal->pilot_location),
        ],
    ];
    $visibleFiles = $proposal->files->filter(fn ($f) => $viewer === 'admin' || ! $f->is_internal);
@endphp

<div class="space-y-6">
    <section class="card card-body">
        <h2 class="text-lg font-extrabold">A. Identitas produk</h2>
        <dl class="data-list mt-2">
            <div><dt>Nama produk/teknologi</dt><dd class="font-semibold">{{ $proposal->product_name }}</dd></div>
            <div><dt>Ringkasan</dt><dd>{{ $proposal->short_summary ?: '—' }}</dd></div>
            <div><dt>Tema</dt><dd><x-theme-badge :theme="$proposal->theme" /></dd></div>
            <div><dt>Tantangan</dt><dd>{{ $proposal->challenge?->title ?? '—' }}@if ($proposal->challenge?->owner) <span class="text-navy-500">· {{ $proposal->challenge->owner->displayName() }}</span>@endif</dd></div>
            <div><dt>TKT</dt><dd>{{ $proposal->tkt_level ? 'TKT '.$proposal->tkt_level : '—' }}</dd></div>
            <div><dt>Status purwarupa</dt><dd>{{ \App\Support\Options::label(\App\Support\Options::prototypeStatuses(), $proposal->prototype_status) }}</dd></div>
        </dl>
    </section>

    <section class="card card-body">
        <h2 class="text-lg font-extrabold">B. Tim dan institusi</h2>
        @if ($blind)
            <p class="mt-3 rounded-xl bg-navy-50 px-4 py-3 text-sm text-navy-600"><x-icon name="eye-off" class="inline size-4" /> Identitas tim disembunyikan (review buta). Komposisi keahlian tetap ditampilkan.</p>
            <ul class="mt-3 grid gap-2 text-sm sm:grid-cols-2">
                @foreach ($proposal->team->members->where('status', 'active') as $member)
                    <li class="rounded-lg border border-navy-100 px-3 py-2">Anggota {{ $loop->iteration }} · <span class="font-semibold">{{ $member->discipline ?: '—' }}</span></li>
                @endforeach
            </ul>
        @else
            <dl class="data-list mt-2">
                <div><dt>Tim</dt><dd class="font-semibold">{{ $proposal->team->name }} <span class="font-normal text-navy-500">· {{ $proposal->team->institution }}</span></dd></div>
                <div><dt>Narahubung</dt><dd>{{ $proposal->contact_name ?: '—' }}@if ($viewer !== 'reviewer' && $proposal->contact_email) <span class="text-navy-500">· {{ $proposal->contact_email }} {{ $proposal->contact_phone ? '· '.$proposal->contact_phone : '' }}</span>@endif</dd></div>
            </dl>
            <div class="table-wrap mt-3">
                <table class="table">
                    <thead><tr><th>Nama</th><th>Keahlian</th><th>Institusi</th><th>Peran</th></tr></thead>
                    <tbody>
                        @foreach ($proposal->team->members->where('status', 'active') as $member)
                            <tr><td class="font-medium">{{ $member->name }}</td><td>{{ $member->discipline ?: '—' }}</td><td>{{ $member->institution ?: '—' }}</td><td>{{ $member->isLeader() ? 'Ketua' : 'Anggota' }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div><p class="text-sm font-semibold text-navy-500">Peneliti/inventor</p><div class="mt-1 space-y-2 text-sm text-navy-800">{{ paragraphs($proposal->inventors) }}</div></div>
                <div><p class="text-sm font-semibold text-navy-500">Pengalaman relevan</p><div class="mt-1 space-y-2 text-sm text-navy-800">{{ paragraphs($proposal->previous_work) }}</div></div>
            </div>
        @endif
    </section>

    <section class="card card-body">
        <h2 class="text-lg font-extrabold">C. Latar belakang riset</h2>
        @if ($proposal->researchHistories->isNotEmpty())
            <div class="table-wrap mt-3">
                <table class="table">
                    <thead><tr><th>Tahun</th><th>Judul</th><th>Dana (Rp)</th><th>Sumber</th></tr></thead>
                    <tbody>
                        @foreach ($proposal->researchHistories as $research)
                            <tr>
                                <td class="whitespace-nowrap">{{ $research->year }}</td>
                                <td>{{ $research->title }}@if (! $blind && $research->researchers)<p class="text-xs text-navy-500">{{ $research->researchers }}</p>@endif</td>
                                <td class="whitespace-nowrap tabular-nums">{{ $research->funding_amount !== null ? number_format((float) $research->funding_amount, 0, ',', '.') : '—' }}</td>
                                <td>{{ $research->funding_source ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            <div><p class="text-sm font-semibold text-navy-500">Hasil riset/bukti purwarupa</p><div class="mt-1 space-y-2 text-sm text-navy-800">{{ paragraphs($proposal->existing_results) }}</div></div>
            <div><p class="text-sm font-semibold text-navy-500">KI dan publikasi</p><div class="mt-1 space-y-2 text-sm text-navy-800">{{ paragraphs($proposal->ip_publications) }}</div></div>
        </div>
    </section>

    @foreach ($blocks as $heading => $items)
        <section class="card card-body">
            <h2 class="text-lg font-extrabold">{{ $heading }}</h2>
            <div class="mt-3 divide-y divide-navy-100">
                @foreach ($items as $item)
                    <div class="py-3">
                        <p class="text-sm font-semibold text-navy-500">{{ $item['label'] }}</p>
                        <div class="mt-1 space-y-2 text-sm leading-relaxed text-navy-800">{{ paragraphs($item['value']) }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="card card-body">
        <h2 class="text-lg font-extrabold">G. Kesiapan legal, keselamatan, etik, dan data</h2>
        <p class="mt-1 text-xs text-navy-500">Catatan kesiapan untuk telaah pakar, bukan pernyataan kepatuhan hukum.</p>
        <dl class="data-list mt-2">
            <div><dt>Status KI</dt><dd>{{ \App\Support\Options::label(\App\Support\Options::ipStatuses(), $proposal->ip_status) }}</dd></div>
            <div><dt>Izin/standar</dt><dd>{{ \App\Support\Options::label(\App\Support\Options::regulatoryStatuses(), $proposal->regulatory_status) }}</dd></div>
            <div><dt>Data pribadi/kesehatan</dt><dd>{{ \App\Support\Options::label(\App\Support\Options::dataRiskStatuses(), $proposal->data_risk_status) }}</dd></div>
            <div><dt>Persetujuan etik</dt><dd>{{ \App\Support\Options::label(\App\Support\Options::ethicsStatuses(), $proposal->ethics_status) }}</dd></div>
        </dl>
        <div class="mt-2 divide-y divide-navy-100">
            @foreach ([
                'Isu KI dan kepemilikan' => $proposal->ip_notes,
                'Izin, registrasi, standar' => $proposal->regulatory_notes,
                'Ketergantungan pihak ketiga' => $proposal->third_party_dependencies,
                'Risiko keselamatan' => $proposal->safety_risks,
                'Pengelolaan data' => $proposal->data_notes,
                'Catatan etik' => $proposal->ethics_notes,
                'Risiko lingkungan/operasional' => $proposal->environmental_risks,
                'Rencana mitigasi' => $proposal->mitigation_plan,
            ] as $label => $value)
                <div class="py-3">
                    <p class="text-sm font-semibold text-navy-500">{{ $label }}</p>
                    <div class="mt-1 space-y-2 text-sm leading-relaxed text-navy-800">{{ paragraphs($value) }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($proposal->answers->isNotEmpty())
        <section class="card card-body">
            <h2 class="text-lg font-extrabold">Pertanyaan khusus tema</h2>
            <div class="mt-3 divide-y divide-navy-100">
                @foreach ($proposal->answers as $answer)
                    <div class="py-3">
                        <p class="text-sm font-semibold text-navy-500">{{ $answer->question?->question }}</p>
                        <div class="mt-1 space-y-2 text-sm text-navy-800">{{ paragraphs($answer->answer) }}</div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    <section class="card card-body">
        <h2 class="text-lg font-extrabold">H. Berkas dan tautan</h2>
        <dl class="data-list mt-2">
            <div><dt>Video demo</dt><dd>@if ($proposal->demo_video_url)<a href="{{ $proposal->demo_video_url }}" target="_blank" rel="noopener noreferrer nofollow" class="font-semibold text-forest-700 underline break-all">{{ $proposal->demo_video_url }}</a>@else — @endif</dd></div>
            <div><dt>Repositori</dt><dd>@if ($proposal->repository_url)<a href="{{ $proposal->repository_url }}" target="_blank" rel="noopener noreferrer nofollow" class="font-semibold text-forest-700 underline break-all">{{ $proposal->repository_url }}</a>@else — @endif</dd></div>
        </dl>
        <ul class="mt-3 divide-y divide-navy-100 rounded-xl border border-navy-100">
            @forelse ($visibleFiles as $file)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                    <span class="flex min-w-0 items-center gap-3">
                        <x-icon :name="$file->isImage() ? 'eye' : 'file'" class="size-4 text-navy-400" />
                        <span class="min-w-0">
                            <span class="block font-semibold text-navy-900">{{ $file->typeLabel() }} @if ($file->is_internal)<x-badge tone="danger">Internal</x-badge>@endif</span>
                            <span class="block truncate text-xs text-navy-500">{{ $file->original_name }} · {{ format_bytes($file->size) }} · {{ fmt_date($file->created_at) }}</span>
                        </span>
                    </span>
                    <a href="{{ route('files.show', $file->uuid) }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="lock" class="size-3.5" /> Buka</a>
                </li>
            @empty
                <li class="px-4 py-3 text-sm text-navy-500">Belum ada berkas.</li>
            @endforelse
        </ul>
    </section>
</div>
