<x-layouts.public :title="$challenge->title" :description="$challenge->summary">
    <x-page-hero :title="$challenge->title" :eyebrow="'Tantangan · '.$challenge->theme?->name" :lead="$challenge->summary">
        <a href="{{ route('register') }}" class="btn btn-primary">Jawab Tantangan Ini</a>
        <a href="{{ route('challenges.index') }}" class="btn btn-outline-light">Semua Tantangan</a>
    </x-page-hero>

    @php
        $sections = array_filter([
            'Rumusan tantangan' => $challenge->challenge_statement,
            'Pernyataan masalah' => $challenge->problem_statement,
            'Pengguna terdampak' => $challenge->affected_users,
            'Kondisi saat ini' => $challenge->current_baseline,
            'Hasil yang diharapkan' => $challenge->desired_outcome,
            'Fungsi minimum' => $challenge->minimum_functions,
            'Indikator keberhasilan' => $challenge->success_indicators,
            'Batasan' => $challenge->constraints,
            'Data dan fasilitas tersedia' => $challenge->data_public ? $challenge->available_data : null,
            'Kemungkinan lokasi pilot' => $challenge->pilot_location_public ? $challenge->pilot_location : null,
            'Kontak/mentor' => $challenge->contact_public ? $challenge->contact_info : null,
        ], fn ($v) => filled($v));
    @endphp

    <section class="section">
        <div class="container-page grid gap-10 lg:grid-cols-[1fr_340px]">
            <article class="card divide-y divide-navy-100">
                @foreach ($sections as $heading => $text)
                    <div class="p-6 sm:px-10">
                        <h2 class="text-lg font-extrabold">{{ $heading }}</h2>
                        <div class="mt-3 space-y-3 leading-relaxed text-navy-700">{{ paragraphs($text) }}</div>
                    </div>
                @endforeach
            </article>
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                <div class="card p-6">
                    <p class="eyebrow text-navy-400">Dapat dipilih oleh tema</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($challenge->themes as $theme)
                            <x-theme-badge :theme="$theme" />
                        @endforeach
                    </div>
                    @if ($owner)
                        <p class="eyebrow mt-6 text-navy-400">Pemilik tantangan</p>
                        <a href="{{ route('owners.show', $owner->slug) }}" class="mt-2 flex items-center gap-3 font-bold text-navy-950 hover:text-forest-700">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-navy-950 text-xs font-extrabold text-signal-400">{{ $owner->initials() }}</span>
                            {{ $owner->name }}
                        </a>
                    @endif
                </div>
                <div class="card p-6">
                    <p class="font-bold">Cara menjawab tantangan</p>
                    <ol class="mt-3 list-decimal space-y-1.5 pl-5 text-sm text-navy-600">
                        <li>Buat akun dan bentuk tim lintas disiplin.</li>
                        <li>Pilih tema, lalu pilih tantangan ini.</li>
                        <li>Lengkapi proposal dan unggah berkas.</li>
                        <li>Kirim saat Open Call dibuka.</li>
                    </ol>
                    <a href="{{ route('register') }}" class="btn btn-primary mt-5 w-full">Daftar Sekarang</a>
                </div>
            </aside>
        </div>
        @if ($related->isNotEmpty())
            <div class="container-page mt-16">
                <h2 class="text-2xl font-extrabold">Tantangan lain di tema ini</h2>
                <div class="mt-6 grid gap-6 md:grid-cols-3">
                    @foreach ($related as $item)
                        <x-challenge-card :challenge="$item" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts.public>
