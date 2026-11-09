<x-layouts.public title="Beranda">
    @php
        $phaseMeta = [
            1 => ['Formulasi Tantangan', 'Memahami masalah nyata dari mitra pengguna'],
            2 => ['Penjaringan Inovator', 'Menghimpun talenta dan membentuk tim'],
            3 => ['Hackathon & Pengembangan Purwarupa', 'Dari ide menjadi solusi nyata'],
            4 => ['Pilot, Hilirisasi & Dampak', 'Validasi, adopsi, dan keberlanjutan'],
        ];
        $phaseStages = $stages->whereNotNull('phase_number')->groupBy('phase_number');
        $registrationOpen = (bool) setting('registration_enabled', true);

        $openCallText = match ($openCall['phase']) {
            'open' => $openCall['end'] ? 'Dibuka hingga '.fmt_date($openCall['end'], true) : 'Dibuka',
            'scheduled' => 'Dibuka mulai '.fmt_date($openCall['start'], true),
            'ended' => 'Telah ditutup',
            'closed' => 'Belum dibuka',
            default => 'Jadwal akan diumumkan',
        };

        $ctaText = match (true) {
            $openCall['open'] => 'Open Call sedang dibuka'.($openCall['end'] ? ' hingga '.fmt_date($openCall['end'], true) : '').'. Lengkapi dan kirim proposal tim Anda melalui portal.',
            $openCall['phase'] === 'ended' => 'Open Call telah ditutup. Pantau pengumuman untuk informasi tahap berikutnya.',
            $registrationOpen => 'Open Call belum dibuka. Anda sudah dapat membuat akun, membentuk tim, dan menyimpan draf proposal; pengiriman proposal dibuka bersama pengumuman Open Call.',
            default => 'Pendaftaran akun belum dibuka. Pantau halaman pengumuman untuk informasi Open Call.',
        };
    @endphp

    {{-- Pembuka --}}
    <section class="border-b border-navy-950/10">
        <div class="container-page pb-16 pt-8 sm:pb-20 sm:pt-12 lg:pb-24">
            <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-1 border-b border-navy-950 pb-3 text-[0.82rem] font-medium text-navy-700">
                <p>Hackathon Deeptech · {{ setting('organizer') }}</p>
                <p>Ketahanan Pangan &amp; Kesehatan · {{ setting('event_period') }}</p>
            </div>

            <h1 class="display mt-10 sm:mt-14">{{ setting('tagline') }}</h1>

            <div class="mt-12 grid gap-12 lg:mt-16 lg:grid-cols-12 lg:gap-10">
                <div class="lg:col-span-7">
                    <p class="lede max-w-2xl">{{ setting('hero_intro') }}</p>
                    <div class="mt-8 flex flex-wrap items-center gap-3">
                        <a href="{{ route('challenges.index') }}" class="btn btn-primary btn-lg">Jelajahi Tantangan <x-icon name="arrow-right" class="size-4" /></a>
                        @if ($registrationOpen)
                            <a href="{{ route('register') }}" class="btn btn-outline btn-lg">Daftar Sekarang</a>
                        @endif
                    </div>
                </div>

                <aside class="lg:col-span-5" aria-labelledby="status-program">
                    <div class="border-t border-navy-950 pt-3">
                        <h2 id="status-program" class="eyebrow text-navy-600">Status program</h2>
                        <dl class="mt-1 divide-y divide-navy-950/10 text-[0.95rem]">
                            <div class="grid grid-cols-[8rem_1fr] gap-4 py-3">
                                <dt class="text-navy-500">Tahap berjalan</dt>
                                <dd class="font-medium text-navy-950">
                                    @if ($currentStage)
                                        {{ $currentStage->name }}
                                        @if ($currentStage->name_id && $currentStage->name_id !== $currentStage->name)<span class="block text-sm font-normal text-navy-500">{{ $currentStage->name_id }}</span>@endif
                                    @else
                                        Akan diumumkan
                                    @endif
                                </dd>
                            </div>
                            @if ($lastActivity)
                                <div class="grid grid-cols-[8rem_1fr] gap-4 py-3">
                                    <dt class="text-navy-500">Kegiatan terakhir</dt>
                                    <dd class="font-medium text-navy-950">{{ $lastActivity->title }}<span class="block text-sm font-normal text-navy-500">{{ $lastActivity->dateText() }} · {{ $lastActivity->statusLabel() }}</span></dd>
                                </div>
                            @endif
                            <div class="grid grid-cols-[8rem_1fr] gap-4 py-3">
                                <dt class="text-navy-500">Tantangan final</dt>
                                <dd class="font-medium text-navy-950">{{ $challengeCount > 0 ? $challengeCount.' tantangan telah diumumkan' : 'Sedang dirumuskan bersama mitra' }}</dd>
                            </div>
                            <div class="grid grid-cols-[8rem_1fr] gap-4 py-3">
                                <dt class="text-navy-500">Open Call</dt>
                                <dd class="font-medium {{ $openCall['open'] ? 'text-forest-700' : 'text-navy-950' }}">{{ $openCallText }}</dd>
                            </div>
                        </dl>
                    </div>
                </aside>
            </div>
        </div>
    </section>

    {{-- Tentang singkat --}}
    <section class="section">
        <div class="container-page grid gap-6 lg:grid-cols-12 lg:gap-10">
            <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Tentang program</p>
            <div class="lg:col-span-9">
                <p class="font-serif text-[1.85rem] leading-[1.18] text-navy-950 sm:text-[2.5rem] sm:leading-[1.12]">
                    DEEPNEST mempertemukan pemilik masalah nyata dengan tim inovator lintas disiplin, mentor, dan mitra hilirisasi untuk membangun purwarupa teknologi di bidang ketahanan pangan dan kesehatan.
                </p>
                <a href="{{ route('about') }}" class="link-arrow mt-8">Tentang DEEPNEST <x-icon name="arrow-right" class="size-4" /></a>
            </div>
        </div>
        <div class="container-page mt-16">
            <ol class="grid gap-x-8 gap-y-10 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([
                    ['Tantangan mitra', 'Pemerintah daerah, industri, koperasi, asosiasi, dan fasilitas kesehatan merumuskan masalah prioritas beserta indikator keberhasilannya.'],
                    ['Tim lintas disiplin', 'Perguruan tinggi, startup, lembaga riset, dan inovator masyarakat membentuk tim untuk menjawab tantangan.'],
                    ['Purwarupa tervalidasi', 'Bootcamp, mentoring sprint, dan demo day dengan empat stage gate menghasilkan fungsi utama yang terdemonstrasi.'],
                    ['Pilot dan hilirisasi', 'Purwarupa prioritas diuji di lingkungan mitra, didampingi perlindungan KI, inkubasi, dan business matching.'],
                ] as $i => [$heading, $text])
                    <li class="border-t border-navy-950 pt-4">
                        <p class="text-sm tabular-nums text-navy-500">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</p>
                        <h3 class="mt-8 text-lg font-semibold">{{ $heading }}</h3>
                        <p class="mt-2 text-[0.95rem] leading-relaxed text-navy-600">{{ $text }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- Tema --}}
    <section class="section border-t border-navy-950/10 bg-sheet">
        <div class="container-page">
            <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Tema proposal</p>
                <div class="lg:col-span-9">
                    <h2 class="headline">Tiga tema proposal</h2>
                    <p class="mt-5 max-w-2xl text-lg leading-relaxed text-navy-600">Peserta memilih satu tema, melihat tantangan terkait, lalu memilih satu tantangan untuk dijawab.</p>
                </div>
            </div>
            <div class="mt-14 grid gap-px border-y border-navy-950/15 bg-navy-950/15 lg:grid-cols-3">
                @foreach ($themes as $theme)
                    @php $pad = match (true) { $loop->first => 'lg:pl-0 lg:pr-8', $loop->last => 'lg:pl-8 lg:pr-0', default => 'lg:pl-8 lg:pr-8' }; @endphp
                    <a href="{{ route('challenges.index', ['tema' => $theme->code]) }}" class="group flex flex-col bg-sheet py-8 {{ $pad }}">
                        <span class="block h-[3px] w-10" style="background: {{ $theme->color }}"></span>
                        <h3 class="mt-6 font-serif text-[2.2rem] leading-none decoration-1 underline-offset-[6px] group-hover:underline">{{ $theme->name }}</h3>
                        <p class="mt-2 text-sm text-navy-500">{{ $theme->name_en }}</p>
                        <p class="mt-5 text-[0.95rem] leading-relaxed text-navy-700">{{ $theme->description }}</p>
                        <span class="mt-auto inline-flex items-center gap-1.5 pt-8 text-sm font-semibold text-navy-950">
                            <span class="underline decoration-navy-300 underline-offset-4 group-hover:decoration-navy-950">{{ $theme->published_challenges_count > 0 ? $theme->published_challenges_count.' tantangan terbuka' : 'Lihat tantangan' }}</span>
                            <x-icon name="arrow-right" class="size-4 transition-transform group-hover:translate-x-0.5" />
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Tantangan & mitra --}}
    <section class="section border-t border-navy-950/10">
        <div class="container-page">
            <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Tantangan</p>
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between lg:col-span-9">
                    <h2 class="headline max-w-2xl">Tantangan dari pemilik masalah</h2>
                    @if ($challenges->isNotEmpty())
                        <a href="{{ route('challenges.index') }}" class="link-arrow shrink-0">Semua tantangan <x-icon name="arrow-right" class="size-4" /></a>
                    @endif
                </div>
            </div>

            @if ($challenges->isNotEmpty())
                <ul class="mt-12 border-t border-navy-950">
                    @foreach ($challenges as $challenge)
                        @php $challengeOwner = $challenge->publicOwner(); @endphp
                        <li class="border-b border-navy-950/15">
                            <a href="{{ route('challenges.show', $challenge->slug) }}" class="group grid gap-2 py-6 lg:grid-cols-12 lg:gap-10">
                                <span class="text-sm text-navy-500 lg:col-span-3">
                                    {{ $challenge->theme?->name }}@if ($challengeOwner)<span class="block">{{ $challengeOwner->displayName() }}</span>@endif
                                </span>
                                <span class="lg:col-span-8">
                                    <span class="block font-serif text-[1.65rem] leading-snug text-navy-950 decoration-1 underline-offset-4 group-hover:underline">{{ $challenge->title }}</span>
                                    <span class="mt-2 line-clamp-2 block text-[0.95rem] leading-relaxed text-navy-600">{{ $challenge->summary }}</span>
                                </span>
                                <span class="hidden justify-end pt-2 lg:col-span-1 lg:flex"><x-icon name="arrow-right" class="size-5 transition-transform group-hover:translate-x-1" /></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="mt-12 grid gap-6 border-t border-navy-950 pt-8 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-6 lg:col-start-4">
                        <p class="subhead">Tantangan final sedang dirumuskan bersama mitra.</p>
                        <p class="mt-3 leading-relaxed text-navy-600">Tantangan disusun dari hasil FGD Challenge Discovery bersama calon mitra pemilik masalah, divalidasi, lalu diumumkan bersama pembukaan Open Call lengkap dengan indikator keberhasilannya.</p>
                    </div>
                    <div class="lg:col-span-3 lg:text-right">
                        <a href="{{ route('timeline') }}" class="link-arrow">Lihat linimasa <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                </div>
            @endif

            @if ($owners->isNotEmpty())
                <div class="mt-20 grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Mitra pemilik tantangan</p>
                    <ul class="grid grid-cols-2 gap-x-6 sm:grid-cols-3 lg:col-span-9">
                        @foreach ($owners as $owner)
                            <li class="border-t border-navy-950/20">
                                <a href="{{ route('owners.show', $owner->slug) }}" class="group flex items-center gap-3 py-4">
                                    @if ($owner->logo_path)
                                        <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="" class="h-9 w-12 shrink-0 object-contain" loading="lazy">
                                    @endif
                                    <span class="text-sm font-medium leading-snug text-navy-800 group-hover:text-navy-950 group-hover:underline">{{ $owner->displayName() }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </section>

    {{-- Alur program --}}
    <section class="section bg-navy-950 text-paper">
        <div class="container-page">
            <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                <p class="eyebrow pt-3 text-navy-400 lg:col-span-3">Alur program</p>
                <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between lg:col-span-9">
                    <div>
                        <h2 class="headline text-paper">Empat fase, dari masalah hingga dampak</h2>
                        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-navy-300">DEEPNEST tidak berhenti pada kompetisi. Setiap tahap menghasilkan luaran yang menjadi dasar untuk melanjutkan ke tahap berikutnya.</p>
                    </div>
                    <a href="{{ route('stages') }}" class="inline-flex shrink-0 items-center gap-1.5 font-semibold text-paper underline decoration-navy-500 underline-offset-4 hover:decoration-paper">Detail tahapan <x-icon name="arrow-right" class="size-4" /></a>
                </div>
            </div>

            <ol class="mt-14 grid gap-px border-y border-white/10 bg-white/10 md:grid-cols-2 xl:grid-cols-4">
                @foreach ($phaseMeta as $number => [$name, $tagline])
                    @php $pad = [1 => 'md:pl-0 md:pr-6', 2 => 'md:pl-6 md:pr-0 xl:pr-6', 3 => 'md:pl-0 md:pr-6 xl:pl-6', 4 => 'md:pl-6 md:pr-0'][$number] ?? 'md:px-6'; @endphp
                    <li class="phase-{{ $number }} bg-navy-950 py-8 {{ $pad }}">
                        <p class="flex items-center gap-2 text-sm text-navy-400"><span class="size-2" style="background: var(--phase)" aria-hidden="true"></span> Fase {{ $number }}</p>
                        <h3 class="mt-4 font-serif text-[1.75rem] leading-[1.1] text-paper">{{ $name }}</h3>
                        <p class="mt-2 text-sm text-navy-400">{{ $tagline }}</p>
                        <ul class="mt-6 space-y-4 border-t border-white/10 pt-5">
                            @foreach ($phaseStages[$number] ?? [] as $stage)
                                <li>
                                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-[0.95rem] font-medium text-paper">
                                        {{ $stage->name }}
                                        @if ($stage->status === 'ongoing')
                                            <span class="inline-flex items-center gap-1.5 text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-signal-300"><span class="size-1.5 rounded-full bg-signal-300" aria-hidden="true"></span>Berlangsung</span>
                                        @elseif ($stage->status === 'completed')
                                            <span class="text-[0.68rem] font-semibold uppercase tracking-[0.12em] text-forest-300">Selesai</span>
                                        @endif
                                    </p>
                                    <p class="mt-1 text-xs leading-relaxed text-navy-400">{{ $stage->month_label }}@if ($stage->output) · Luaran: {{ $stage->output }}@endif</p>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ol>
            <p class="mt-8 max-w-3xl text-sm leading-relaxed text-navy-400"><span class="font-semibold text-paper">Monitoring & evaluasi</span> berjalan di setiap tahap untuk memastikan kualitas, akuntabilitas, dan pencapaian luaran. Bulan dihitung sejak program dimulai; jadwal pasti diumumkan melalui agenda dan pengumuman.</p>
        </div>
    </section>

    {{-- Target --}}
    @if ($stats->isNotEmpty())
        <section class="section">
            <div class="container-page">
                <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Target program</p>
                    <div class="lg:col-span-9">
                        <h2 class="headline">Target dan rencana program</h2>
                        <p class="mt-5 max-w-2xl text-lg leading-relaxed text-navy-600">Angka berikut adalah target dan rencana dalam proposal program, bukan capaian. Capaian dilaporkan setelah tahapan terkait selesai.</p>
                    </div>
                </div>
                <div class="mt-14 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($stats as $stat)
                        <div class="border-t border-navy-950 pt-4">
                            <p class="numeral text-[4.25rem] sm:text-[5rem]">{{ $stat['value'] }}</p>
                            <p class="mt-4 font-semibold text-navy-950">{{ $stat['label'] }}</p>
                            @if (! empty($stat['note']))<p class="mt-1 text-sm leading-relaxed text-navy-500">{{ $stat['note'] }}</p>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Mentor & juri --}}
    <section class="section border-t border-navy-950/10">
        <div class="container-page grid gap-6 lg:grid-cols-12 lg:gap-10">
            <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Mentor dan juri</p>
            <div class="lg:col-span-9">
                <h2 class="headline">Pendampingan mentor ganda</h2>
                <p class="mt-5 max-w-3xl text-lg leading-relaxed text-navy-600">Mentor internal memastikan ketepatan ilmiah, desain teknologi, pengujian, dan dokumentasi. Mentor mitra menilai kesesuaian dengan kebutuhan operasional, standar, biaya penerapan, dan peluang adopsi. Juri tidak menjadi mentor langsung bagi tim yang dinilainya.</p>
                @if ($people->isNotEmpty())
                    <ul class="mt-10 grid gap-x-8 sm:grid-cols-2">
                        @foreach ($people as $person)
                            <li class="flex items-center gap-4 border-t border-navy-950/15 py-4">
                                @if ($person->photo_path)
                                    <img src="{{ route('media.person-photo', $person->slug) }}" alt="" class="size-14 shrink-0 rounded-full object-cover grayscale" loading="lazy">
                                @else
                                    <span class="flex size-14 shrink-0 items-center justify-center rounded-full bg-navy-100 font-serif text-xl text-navy-700" aria-hidden="true">{{ $person->initials() }}</span>
                                @endif
                                <span class="min-w-0">
                                    <span class="block truncate font-semibold text-navy-950">{{ $person->name }}</span>
                                    <span class="block truncate text-sm text-navy-500">{{ $person->typeLabel() }}@if ($person->affiliation) · {{ $person->affiliation }}@endif</span>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('people.index') }}" class="link-arrow mt-8">Direktori mentor & juri <x-icon name="arrow-right" class="size-4" /></a>
                @else
                    <p class="mt-8 max-w-3xl border-l-2 border-navy-950 pl-4 text-[0.95rem] leading-relaxed text-navy-700">Rencana dalam proposal program: 30 mentor (15 dosen Universitas Hasanuddin dan 15 praktisi mitra) serta 7 juri (4 internal Universitas Hasanuddin dan 3 eksternal). Nama dan profil diumumkan setelah terkonfirmasi.</p>
                @endif
            </div>
        </div>
    </section>

    {{-- Produk: hanya tampil bila sudah ada produk yang disetujui dan ditayangkan --}}
    @if ($products->isNotEmpty())
        <section class="section border-t border-navy-950/10">
            <div class="container-page">
                <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Produk</p>
                    <div class="flex flex-col gap-6 md:flex-row md:items-end md:justify-between lg:col-span-9">
                        <div>
                            <h2 class="headline">Produk yang disetujui</h2>
                            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-navy-600">Hanya ringkasan publik yang ditampilkan; proposal dan dokumen asli tetap rahasia.</p>
                        </div>
                        <a href="{{ route('products.index') }}" class="link-arrow shrink-0">Semua produk <x-icon name="arrow-right" class="size-4" /></a>
                    </div>
                </div>
                <div class="mt-12 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($products as $product)
                        <x-product-card :product="$product" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- Kabar terbaru: hanya tampil bila ada isi --}}
    @if ($announcements->isNotEmpty() || $agenda->isNotEmpty() || $documents->isNotEmpty())
        <section class="section border-t border-navy-950/10">
            <div class="container-page grid gap-6 lg:grid-cols-12 lg:gap-10">
                <p class="eyebrow pt-3 text-navy-500 lg:col-span-3">Kabar terbaru</p>
                <div class="space-y-14 lg:col-span-9">
                    @if ($announcements->isNotEmpty())
                        <div>
                            <div class="flex items-end justify-between gap-4 border-b border-navy-950 pb-3">
                                <h2 class="subhead">Pengumuman</h2>
                                <a href="{{ route('announcements.index') }}" class="link text-sm">Semua pengumuman</a>
                            </div>
                            <ul>
                                @foreach ($announcements as $announcement)
                                    <li class="border-b border-navy-950/15">
                                        <a href="{{ route('announcements.show', $announcement->slug) }}" class="group grid gap-2 py-5 sm:grid-cols-[9rem_1fr] sm:gap-6">
                                            <span class="text-sm text-navy-500">{{ fmt_date($announcement->published_at) }}<span class="block">{{ $announcement->categoryLabel() }}</span></span>
                                            <span>
                                                <span class="block font-serif text-[1.45rem] leading-snug text-navy-950 decoration-1 underline-offset-4 group-hover:underline">{{ $announcement->title }}</span>
                                                @if ($announcement->excerpt)<span class="mt-1 line-clamp-2 block text-sm leading-relaxed text-navy-600">{{ $announcement->excerpt }}</span>@endif
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @if ($agenda->isNotEmpty() || $documents->isNotEmpty())
                        <div class="grid gap-14 md:grid-cols-2 md:gap-8">
                            @if ($agenda->isNotEmpty())
                                <div>
                                    <div class="flex items-end justify-between gap-4 border-b border-navy-950 pb-3">
                                        <h2 class="subhead">Agenda</h2>
                                        <a href="{{ route('agenda') }}" class="link text-sm">Semua agenda</a>
                                    </div>
                                    <ul>
                                        @foreach ($agenda as $item)
                                            <li class="border-b border-navy-950/15 py-4">
                                                <p class="font-medium leading-snug text-navy-950">{{ $item->title }}</p>
                                                <p class="mt-1 text-sm text-navy-500">{{ $item->dateText() }} · {{ $item->statusLabel() }}</p>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            @if ($documents->isNotEmpty())
                                <div>
                                    <div class="flex items-end justify-between gap-4 border-b border-navy-950 pb-3">
                                        <h2 class="subhead">Dokumen</h2>
                                        <a href="{{ route('resources.index') }}" class="link text-sm">Sumber daya</a>
                                    </div>
                                    <ul>
                                        @foreach ($documents as $document)
                                            <li class="border-b border-navy-950/15">
                                                <a href="{{ $document->hasFile() ? route('resources.download', $document->slug) : $document->external_url }}" class="flex items-start justify-between gap-4 py-4 font-medium text-navy-900 hover:underline">
                                                    {{ $document->title }} <x-icon name="arrow-right" class="mt-1 size-4 shrink-0 text-navy-400" />
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>
    @endif

    {{-- Ajakan --}}
    <section class="section border-t border-navy-950/10 bg-sheet">
        <div class="container-page grid gap-10 lg:grid-cols-12 lg:items-end lg:gap-10">
            <div class="lg:col-span-7">
                <p class="eyebrow text-navy-500">Untuk calon peserta</p>
                <h2 class="mt-6 font-serif text-[clamp(2.6rem,6vw,5.25rem)] leading-[0.98] tracking-[-0.01em] text-navy-950">Bawa riset dan teknologi Anda ke masalah nyata.</h2>
            </div>
            <div class="lg:col-span-5">
                <p class="leading-relaxed text-navy-700">{{ $ctaText }}</p>
                <div class="mt-6 flex flex-wrap gap-3">
                    @if ($registrationOpen)
                        <a href="{{ route('register') }}" class="btn btn-primary btn-lg">Buat akun</a>
                    @endif
                    <a href="{{ route('rules') }}" class="btn btn-outline btn-lg">Ketentuan &amp; kelayakan</a>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
