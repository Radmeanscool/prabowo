<x-layouts.public :title="$challenge->title" :description="$challenge->summary">
    @php
        $registrationOpen = (bool) setting('registration_enabled', true);
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

    <x-page-hero :title="$challenge->title" :eyebrow="'Tantangan · '.$challenge->theme?->name" :lead="$challenge->summary">
        @if ($registrationOpen)
            <a href="{{ route('register') }}" class="btn btn-primary">Jawab Tantangan Ini</a>
        @endif
        <a href="{{ route('challenges.index') }}" class="btn btn-outline">Semua Tantangan</a>
    </x-page-hero>

    <section class="section pt-14 sm:pt-16">
        <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
            <article class="lg:col-span-8">
                @foreach ($sections as $heading => $text)
                    <div class="grid gap-3 border-t border-navy-950/15 py-7 first:border-navy-950 first:pt-5 sm:grid-cols-[11rem_1fr] sm:gap-8">
                        <h2 class="text-sm font-semibold text-navy-950">{{ $heading }}</h2>
                        <div class="space-y-3 leading-relaxed text-navy-700">{{ paragraphs($text) }}</div>
                    </div>
                @endforeach
            </article>
            <aside class="space-y-10 lg:sticky lg:top-24 lg:col-span-3 lg:col-start-10 lg:self-start">
                <div class="border-t border-navy-950 pt-3">
                    <p class="eyebrow text-navy-500">Dapat dipilih oleh tema</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($challenge->themes as $theme)
                            <li class="flex items-center gap-2 text-navy-800"><span class="size-2" style="background: {{ $theme->color }}" aria-hidden="true"></span>{{ $theme->name }}</li>
                        @endforeach
                    </ul>
                    @if ($owner)
                        <p class="eyebrow mt-8 text-navy-500">Pemilik tantangan</p>
                        <a href="{{ route('owners.show', $owner->slug) }}" class="mt-3 block font-serif text-[1.6rem] leading-snug text-navy-950 decoration-1 underline-offset-4 hover:underline">{{ $owner->name }}</a>
                    @endif
                </div>
                <div class="border-t border-navy-950 pt-3">
                    <p class="eyebrow text-navy-500">Cara menjawab tantangan</p>
                    <ol class="mt-3 space-y-2.5 text-sm leading-relaxed text-navy-700">
                        @foreach (['Buat akun dan bentuk tim lintas disiplin.', 'Pilih tema, lalu pilih tantangan ini.', 'Lengkapi proposal dan unggah berkas.', 'Kirim saat Open Call dibuka.'] as $i => $step)
                            <li class="flex gap-3"><span class="tabular-nums text-navy-400">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>{{ $step }}</li>
                        @endforeach
                    </ol>
                    @if ($registrationOpen)
                        <a href="{{ route('register') }}" class="btn btn-primary mt-5">Daftar Sekarang</a>
                    @endif
                </div>
            </aside>
        </div>
        @if ($related->isNotEmpty())
            <div class="container-page mt-20">
                <div class="border-b border-navy-950 pb-3"><h2 class="subhead">Tantangan lain di tema ini</h2></div>
                <div class="mt-10 grid gap-x-8 gap-y-14 md:grid-cols-3">
                    @foreach ($related as $item)
                        <x-challenge-card :challenge="$item" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-layouts.public>
