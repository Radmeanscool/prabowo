<x-layouts.public title="Beranda">
    @php
        $focus = [
            'food_security' => ['Budidaya dan produksi cerdas', 'Otomasi dan analitika produksi', 'Mutu pakan dan bahan baku', 'Genetik unggul', 'Pengolahan hasil pangan, perikanan, dan pertanian', 'Pemasaran, distribusi, dan ketertelusuran'],
            'health' => ['Alat kesehatan berteknologi tinggi', 'Deteksi dan pemeriksaan dini', 'Pemantauan pasien', 'Layanan lebih cepat, aman, akurat, dan terjangkau', 'Efisiensi operasional rumah sakit', 'Sistem berbasis AI dan data'],
            'hybrid' => ['Menjawab pangan dan kesehatan secara sengaja', 'Memadukan data, perangkat, atau layanan lintas bidang', 'Dinilai dengan kriteria kedua bidang', 'Bukan kategori cadangan'],
        ];
        $phaseMeta = [
            1 => ['Formulasi Tantangan', 'Memahami masalah nyata dari mitra pengguna'],
            2 => ['Penjaringan Inovator', 'Menghimpun talenta dan membentuk tim'],
            3 => ['Hackathon & Pengembangan Purwarupa', 'Dari ide menjadi solusi nyata'],
            4 => ['Pilot, Hilirisasi & Dampak', 'Validasi, adopsi, dan keberlanjutan'],
        ];
        $phaseStages = $stages->whereNotNull('phase_number')->groupBy('phase_number');
    @endphp

    {{-- 1. Hero --}}
    <section class="hero-surface relative overflow-hidden text-white">
        <div class="grid-lines absolute inset-0" aria-hidden="true"></div>
        <div class="container-page relative grid gap-12 py-16 sm:py-20 lg:grid-cols-[1.15fr_0.85fr] lg:items-center lg:py-24">
            <div>
                <p class="eyebrow inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/5 px-3 py-1.5 text-signal-300">
                    <span class="size-1.5 rounded-full bg-signal-400"></span> Hackathon Deeptech · {{ setting('organizer') }}
                </p>
                <h1 class="mt-6 text-5xl font-extrabold tracking-tight text-white sm:text-7xl">DEEP<span class="text-signal-400">NEST</span></h1>
                <p class="mt-3 max-w-xl text-base font-semibold text-navy-200 sm:text-lg">{{ setting('site_full_name') }}</p>
                <p class="mt-6 max-w-2xl text-2xl font-bold leading-snug text-white sm:text-3xl">{{ setting('tagline') }}</p>
                <p class="mt-4 max-w-2xl text-base leading-relaxed text-navy-200">{{ setting('hero_intro') }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('challenges.index') }}" class="btn btn-primary btn-lg">Jelajahi Tantangan <x-icon name="arrow-right" class="size-5" /></a>
                    <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg">Daftar Sekarang</a>
                </div>

                <dl class="mt-10 grid gap-4 border-t border-white/10 pt-6 sm:grid-cols-3">
                    <div>
                        <dt class="eyebrow text-navy-400">Tema</dt>
                        <dd class="mt-1 text-sm font-semibold text-white">Ketahanan Pangan dan Kesehatan</dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-navy-400">Lokasi</dt>
                        <dd class="mt-1 text-sm font-semibold text-white">{{ setting('event_location') }}</dd>
                    </div>
                    <div>
                        <dt class="eyebrow text-navy-400">Open Call</dt>
                        <dd class="mt-1 text-sm font-semibold {{ $openCall['open'] ? 'text-forest-300' : 'text-signal-300' }}">
                            {{ $openCall['label'] }}
                            @if ($openCall['end'] && $openCall['open'])<span class="block text-xs font-medium text-navy-300">hingga {{ fmt_date($openCall['end'], true) }}</span>@endif
                        </dd>
                    </div>
                </dl>
            </div>

            <div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
                <div class="relative rounded-[2rem] border border-white/10 bg-white/[0.04] p-6 backdrop-blur-sm">
                    <svg viewBox="0 0 440 392" class="w-full" role="presentation">
                        <defs>
                            <radialGradient id="glow" cx="50%" cy="45%" r="50%">
                                <stop offset="0%" stop-color="#f5b93a" stop-opacity="0.35"/>
                                <stop offset="100%" stop-color="#f5b93a" stop-opacity="0"/>
                            </radialGradient>
                        </defs>
                        <circle cx="220" cy="150" r="140" fill="url(#glow)"/>
                        <g stroke="#94a9c8" stroke-opacity="0.35" stroke-width="1.2">
                            <line x1="80" y1="70" x2="160" y2="40"/><line x1="160" y1="40" x2="220" y2="96"/><line x1="220" y1="96" x2="300" y2="44"/>
                            <line x1="300" y1="44" x2="372" y2="88"/><line x1="80" y1="70" x2="120" y2="150"/><line x1="372" y1="88" x2="330" y2="160"/>
                            <line x1="120" y1="150" x2="220" y2="96"/><line x1="330" y1="160" x2="220" y2="96"/><line x1="56" y1="190" x2="120" y2="150"/>
                            <line x1="384" y1="196" x2="330" y2="160"/>
                        </g>
                        <g fill="#c3d0e3">
                            <circle cx="80" cy="70" r="5"/><circle cx="160" cy="40" r="4"/><circle cx="300" cy="44" r="4"/><circle cx="372" cy="88" r="5"/>
                            <circle cx="120" cy="150" r="4"/><circle cx="330" cy="160" r="4"/><circle cx="56" cy="190" r="3"/><circle cx="384" cy="196" r="3"/>
                        </g>
                        <circle cx="80" cy="70" r="9" fill="none" stroke="#45a57f" stroke-width="2"/>
                        <circle cx="372" cy="88" r="9" fill="none" stroke="#d0404b" stroke-width="2"/>
                        <path d="M60 210a160 160 0 0 0 320 0" fill="none" stroke="#f5b93a" stroke-width="14" stroke-linecap="round"/>
                        <path d="M110 210a110 110 0 0 0 220 0" fill="none" stroke="#45a57f" stroke-width="14" stroke-linecap="round"/>
                        <path d="M160 210a60 60 0 0 0 120 0" fill="none" stroke="#ffffff" stroke-width="14" stroke-linecap="round"/>
                        <circle cx="220" cy="128" r="22" fill="#f5b93a"/>
                        <circle cx="220" cy="128" r="34" fill="none" stroke="#f5b93a" stroke-opacity="0.4" stroke-width="2"/>
                        <line x1="220" y1="96" x2="220" y2="106" stroke="#f5b93a" stroke-width="2"/>
                    </svg>
                    <div class="absolute -left-3 top-10 rounded-2xl border border-white/10 bg-navy-950/80 px-4 py-3 shadow-2xl backdrop-blur sm:-left-8">
                        <p class="text-2xl font-extrabold text-white">15</p>
                        <p class="text-xs font-medium text-navy-300">tim lintas disiplin</p>
                    </div>
                    <div class="absolute -right-2 top-1/3 rounded-2xl border border-white/10 bg-navy-950/80 px-4 py-3 shadow-2xl backdrop-blur sm:-right-6">
                        <p class="text-2xl font-extrabold text-signal-400">≥ 3</p>
                        <p class="text-xs font-medium text-navy-300">purwarupa fungsional</p>
                    </div>
                    <div class="absolute -bottom-4 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full border border-white/10 bg-navy-950/90 px-4 py-2 text-xs font-semibold text-navy-200 shadow-2xl">
                        4 fase · 12 bulan · {{ setting('spirit') }}
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- 2. Penjelasan singkat --}}
    <section class="section bg-white">
        <div class="container-page grid gap-12 lg:grid-cols-[0.9fr_1.1fr] lg:items-start">
            <div>
                <p class="eyebrow text-forest-600">Mengapa DEEPNEST</p>
                <h2 class="section-title mt-3">Masalah operasional nyata menjadi purwarupa yang tervalidasi</h2>
                <p class="section-lead">Setiap tantangan berasal dari kebutuhan nyata mitra pemilik masalah. Tim lintas disiplin mengembangkan solusi dengan pendampingan mentor ganda, diuji bersama calon pengguna, lalu diarahkan ke pilot dan hilirisasi.</p>
                <a href="{{ route('about') }}" class="btn btn-outline mt-8">Tentang DEEPNEST <x-icon name="arrow-right" class="size-4" /></a>
            </div>
            <ol class="grid gap-4 sm:grid-cols-2">
                @foreach ([
                    ['target', 'Tantangan mitra', 'Pemerintah daerah, industri, koperasi, asosiasi, dan fasilitas kesehatan merumuskan masalah prioritas dan indikator keberhasilan.'],
                    ['users', 'Tim lintas disiplin', 'Perguruan tinggi, startup, lembaga riset, dan inovator masyarakat membentuk tim untuk menjawab tantangan.'],
                    ['flask', 'Purwarupa tervalidasi', 'Bootcamp, mentoring sprint, dan demo day dengan empat stage gate menghasilkan fungsi utama yang terdemonstrasi.'],
                    ['rocket', 'Pilot dan hilirisasi', 'Purwarupa prioritas diuji di lingkungan mitra, didampingi perlindungan KI, inkubasi, dan business matching.'],
                ] as $i => [$icon, $heading, $text])
                    <li class="card p-6">
                        <div class="flex items-center gap-3">
                            <span class="flex size-11 items-center justify-center rounded-xl bg-navy-950 text-signal-400"><x-icon :name="$icon" /></span>
                            <span class="text-sm font-bold text-navy-400">0{{ $i + 1 }}</span>
                        </div>
                        <h3 class="mt-4 text-lg font-bold">{{ $heading }}</h3>
                        <p class="mt-2 text-sm leading-relaxed text-navy-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- 3. Tema --}}
    <section class="section">
        <div class="container-page">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="eyebrow text-forest-600">Fokus tema</p>
                    <h2 class="section-title mt-3">Tiga tema proposal</h2>
                    <p class="section-lead">Peserta memilih tema, melihat tantangan terkait, lalu memilih satu tantangan untuk dijawab.</p>
                </div>
                <a href="{{ route('challenges.index') }}" class="btn btn-dark shrink-0">Jelajahi Tantangan</a>
            </div>
            <div class="mt-10 grid gap-6 lg:grid-cols-3">
                @foreach ($themes as $theme)
                    <a href="{{ route('challenges.index', ['tema' => $theme->code]) }}" class="card card-link group relative flex flex-col overflow-hidden p-7">
                        <span class="absolute inset-x-0 top-0 h-1.5" style="background: {{ $theme->color }}"></span>
                        <span class="flex size-12 items-center justify-center rounded-2xl text-white" style="background: {{ $theme->color }}"><x-icon :name="$theme->icon()" class="size-6" /></span>
                        <h3 class="mt-5 text-2xl font-extrabold">{{ $theme->name }}</h3>
                        <p class="text-sm font-semibold text-navy-400">{{ $theme->name_en }}</p>
                        <p class="mt-3 text-sm leading-relaxed text-navy-600">{{ $theme->description }}</p>
                        <ul class="mt-5 space-y-2 text-sm text-navy-700">
                            @foreach ($focus[$theme->code] ?? [] as $item)
                                <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 text-forest-600" stroke="2.25" />{{ $item }}</li>
                            @endforeach
                        </ul>
                        <span class="mt-auto inline-flex items-center gap-1.5 pt-6 text-sm font-bold text-forest-700">
                            {{ $theme->published_challenges_count > 0 ? $theme->published_challenges_count.' tantangan terbuka' : 'Lihat tantangan' }} <x-icon name="arrow-right" class="size-4 transition group-hover:translate-x-1" />
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 4. Tantangan & mitra --}}
    <section class="section bg-white">
        <div class="container-page">
            <p class="eyebrow text-forest-600">Tantangan nyata</p>
            <div class="mt-3 flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <h2 class="section-title">Tantangan dari pemilik masalah</h2>
                <a href="{{ route('owners.index') }}" class="btn btn-outline shrink-0">Mitra Challenge Owner</a>
            </div>
            @if ($challenges->isNotEmpty())
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($challenges as $challenge)
                        <x-challenge-card :challenge="$challenge" />
                    @endforeach
                </div>
            @else
                <div class="mt-8 grid gap-6 rounded-3xl border border-navy-100 bg-navy-50/50 p-6 sm:p-8 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-lg font-bold">Tantangan final sedang dirumuskan bersama mitra</p>
                        <p class="mt-2 max-w-3xl text-sm leading-relaxed text-navy-600">FGD Challenge Discovery telah dilaksanakan pada 1–2 November 2026. Setelah validasi lapangan dan FGD finalisasi, tantangan final beserta indikator keberhasilannya diumumkan bersama pembukaan Open Call.</p>
                    </div>
                    <a href="{{ route('timeline') }}" class="btn btn-dark">Lihat Linimasa</a>
                </div>
            @endif

            @if ($owners->isNotEmpty())
                <div class="mt-12">
                    <p class="text-sm font-bold text-navy-500">Mitra pemilik tantangan</p>
                    <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                        @foreach ($owners as $owner)
                            <a href="{{ route('owners.show', $owner->slug) }}" class="card card-link flex flex-col items-center gap-3 p-5 text-center">
                                @if ($owner->logo_path)
                                    <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="Logo {{ $owner->name }}" class="h-12 w-auto object-contain" loading="lazy">
                                @else
                                    <span class="flex size-12 items-center justify-center rounded-xl bg-navy-950 text-sm font-extrabold text-signal-400">{{ $owner->initials() }}</span>
                                @endif
                                <span class="text-xs font-semibold leading-snug text-navy-800">{{ $owner->displayName() }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>

    {{-- 5. Tahapan program --}}
    <section class="section relative overflow-hidden bg-navy-950 text-white">
        <div class="grid-lines absolute inset-0 opacity-60" aria-hidden="true"></div>
        <div class="container-page relative">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="eyebrow text-signal-400">Alur program</p>
                    <h2 class="section-title mt-3 text-white">Empat fase, dari masalah hingga dampak</h2>
                    <p class="mt-4 max-w-3xl text-navy-200">DEEPNEST tidak berhenti pada kompetisi. Setiap tahap menghasilkan luaran yang menjadi dasar untuk melanjutkan ke tahap berikutnya.</p>
                </div>
                <a href="{{ route('stages') }}" class="btn btn-outline-light shrink-0">Detail Tahapan</a>
            </div>
            <div class="mt-12 grid gap-5 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($phaseMeta as $number => [$name, $tagline])
                    <div class="phase-{{ $number }} relative rounded-3xl border border-white/10 bg-white/[0.04] p-6">
                        <span class="absolute inset-x-6 top-0 h-1 rounded-b-full" style="background: var(--phase)"></span>
                        <p class="eyebrow" style="color: var(--phase)">Fase {{ $number }}</p>
                        <h3 class="mt-2 text-lg font-bold text-white">{{ $name }}</h3>
                        <p class="mt-1 text-sm text-navy-300">{{ $tagline }}</p>
                        <ul class="mt-5 space-y-3">
                            @foreach ($phaseStages[$number] ?? [] as $stage)
                                <li class="flex items-start gap-3">
                                    <span class="mt-1 size-2.5 shrink-0 rounded-full" style="background: var(--phase)"></span>
                                    <span>
                                        <span class="block text-sm font-semibold text-white">{{ $stage->name }}</span>
                                        <span class="block text-xs text-navy-400">{{ $stage->month_label }} · {{ $stage->output }}</span>
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
            <p class="mt-6 rounded-2xl border border-white/10 bg-white/[0.03] px-5 py-3 text-sm text-navy-200"><span class="font-bold text-white">Monitoring & evaluasi</span> berjalan di setiap tahap untuk memastikan kualitas, akuntabilitas, dan pencapaian luaran.</p>
        </div>
    </section>

    {{-- 6. Target luaran --}}
    @if ($stats->isNotEmpty())
        <section class="section">
            <div class="container-page">
                <p class="eyebrow text-forest-600">Target dan dukungan program</p>
                <h2 class="section-title mt-3">Luaran yang dituju</h2>
                <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stats as $stat)
                        <div class="card p-6">
                            <p class="text-4xl font-extrabold tracking-tight text-navy-950">{{ $stat['value'] }}</p>
                            <p class="mt-2 font-bold text-navy-800">{{ $stat['label'] }}</p>
                            @if (! empty($stat['note']))<p class="mt-1 text-sm text-navy-500">{{ $stat['note'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- 7. Mentor & juri --}}
    <section class="section bg-white">
        <div class="container-page grid gap-10 lg:grid-cols-[0.8fr_1.2fr] lg:items-start">
            <div>
                <p class="eyebrow text-forest-600">Mentor dan juri</p>
                <h2 class="section-title mt-3">Pendampingan mentor ganda</h2>
                <p class="section-lead">Mentor internal memastikan ketepatan ilmiah, desain teknologi, pengujian, dan dokumentasi. Mentor mitra menilai kesesuaian dengan kebutuhan operasional, standar, biaya penerapan, dan peluang adopsi. Juri tidak menjadi mentor langsung bagi tim yang dinilainya.</p>
                <a href="{{ route('people.index') }}" class="btn btn-outline mt-8">Direktori Mentor & Juri</a>
            </div>
            @if ($people->isNotEmpty())
                <div class="grid gap-4 sm:grid-cols-2">
                    @foreach ($people as $person)
                        <div class="card flex items-center gap-4 p-4">
                            @if ($person->photo_path)
                                <img src="{{ route('media.person-photo', $person->slug) }}" alt="Foto {{ $person->name }}" class="size-14 rounded-2xl object-cover" loading="lazy">
                            @else
                                <span class="flex size-14 shrink-0 items-center justify-center rounded-2xl bg-forest-600 text-lg font-extrabold text-white">{{ $person->initials() }}</span>
                            @endif
                            <div class="min-w-0">
                                <p class="truncate font-bold">{{ $person->name }}</p>
                                <p class="truncate text-xs text-navy-500">{{ $person->typeLabel() }} · {{ $person->affiliation }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="card p-6">
                        <p class="text-4xl font-extrabold">30</p>
                        <p class="mt-1 font-bold">Mentor</p>
                        <p class="mt-2 text-sm text-navy-600">Rencana: 15 dosen Unhas dan 15 praktisi mitra, seimbang untuk ketahanan pangan (9 + 9) dan kesehatan (6 + 6).</p>
                    </div>
                    <div class="card p-6">
                        <p class="text-4xl font-extrabold">7</p>
                        <p class="mt-1 font-bold">Juri</p>
                        <p class="mt-2 text-sm text-navy-600">Rencana: 4 juri internal Unhas dan 3 juri eksternal dari BRIN, Kementerian Kesehatan, dan Kementerian Pertanian.</p>
                    </div>
                    <p class="text-sm text-navy-500 sm:col-span-2">Profil mentor dan juri diumumkan setelah terkonfirmasi.</p>
                </div>
            @endif
        </div>
    </section>

    {{-- 8. Produk --}}
    <section class="section">
        <div class="container-page">
            <div class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                <div>
                    <p class="eyebrow text-forest-600">Etalase inovasi</p>
                    <h2 class="section-title mt-3">Produk yang disetujui</h2>
                    <p class="section-lead">Produk tampil di sini setelah disetujui panitia. Yang ditampilkan hanya ringkasan publik; proposal dan dokumen asli tetap rahasia.</p>
                </div>
                <a href="{{ route('products.index') }}" class="btn btn-dark shrink-0">Jelajahi Produk</a>
            </div>
            @if ($products->isNotEmpty())
                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            @else
                <x-empty class="mt-10" icon="sparkles" title="Produk disetujui akan tampil di sini" text="Produk inovasi DEEPNEST ditampilkan setelah melalui seleksi dan keputusan panitia." />
            @endif
        </div>
    </section>

    {{-- 9. Pengumuman, agenda, dokumen --}}
    <section class="section bg-white">
        <div class="container-page grid gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-extrabold">Pengumuman terbaru</h2>
                    <a href="{{ route('announcements.index') }}" class="text-sm font-bold text-forest-700">Semua pengumuman</a>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    @forelse ($announcements as $announcement)
                        <a href="{{ route('announcements.show', $announcement->slug) }}" class="card card-link flex flex-col p-5">
                            <span class="badge badge-neutral self-start">{{ $announcement->categoryLabel() }}</span>
                            <span class="mt-3 font-bold leading-snug text-navy-950">{{ $announcement->title }}</span>
                            <span class="mt-2 line-clamp-3 text-sm text-navy-600">{{ $announcement->excerpt }}</span>
                            <span class="mt-auto pt-4 text-xs text-navy-400">{{ fmt_date($announcement->published_at) }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-navy-500 md:col-span-3">Belum ada pengumuman. Pantau halaman ini untuk informasi Open Call.</p>
                    @endforelse
                </div>
            </div>
            <div class="space-y-8">
                <div>
                    <div class="flex items-end justify-between gap-4">
                        <h2 class="text-2xl font-extrabold">Agenda</h2>
                        <a href="{{ route('agenda') }}" class="text-sm font-bold text-forest-700">Lihat agenda</a>
                    </div>
                    <ul class="mt-6 space-y-3">
                        @forelse ($agenda as $item)
                            <li class="card flex gap-4 p-4">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-signal-50 text-signal-700"><x-icon name="calendar" /></span>
                                <span class="min-w-0">
                                    <span class="block text-sm font-bold">{{ $item->title }}</span>
                                    <span class="block text-xs text-navy-500">{{ $item->dateText() }} · {{ $item->statusLabel() }}</span>
                                </span>
                            </li>
                        @empty
                            <li class="text-sm text-navy-500">Jadwal berikutnya akan diumumkan.</li>
                        @endforelse
                    </ul>
                </div>
                @if ($documents->isNotEmpty())
                    <div>
                        <h2 class="text-2xl font-extrabold">Dokumen</h2>
                        <ul class="mt-4 space-y-2">
                            @foreach ($documents as $document)
                                <li><a href="{{ $document->hasFile() ? route('resources.download', $document->slug) : $document->external_url }}" class="flex items-start gap-2 text-sm font-semibold text-navy-800 hover:text-forest-700"><x-icon name="file" class="mt-0.5 size-4 text-navy-400" /> {{ $document->title }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- 10. CTA --}}
    <section class="section">
        <div class="container-page">
            <div class="hero-surface relative overflow-hidden rounded-[2rem] px-6 py-12 text-white sm:px-12 sm:py-16">
                <div class="grid-lines absolute inset-0" aria-hidden="true"></div>
                <div class="relative grid gap-8 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="eyebrow text-signal-400">Siapkan tim Anda</p>
                        <h2 class="mt-3 text-3xl font-extrabold text-white sm:text-4xl">Bawa riset dan teknologi Anda ke masalah nyata</h2>
                        <p class="mt-4 max-w-2xl text-navy-200">Buat akun, bentuk tim lintas disiplin, pilih tema dan tantangan, lalu susun proposal. Draf dapat disimpan kapan saja dan dikirim saat Open Call dibuka.</p>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Daftar Sekarang</a>
                        <a href="{{ route('agenda') }}" class="btn btn-outline-light btn-lg">Lihat Agenda</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
