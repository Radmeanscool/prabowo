<x-layouts.public :title="$product->displayTitle()" :description="$product->public_short_description">
    @php
        $owner = $product->challenge?->publicOwner();
        $team = $product->publicTeamLabel();
        $sections = array_filter([
            'Masalah yang dijawab' => $product->public_problem,
            'Solusi' => $product->public_solution,
            'Pengguna dan penerima manfaat' => $product->public_target_users,
            'Ringkasan teknologi' => $product->public_technology,
            'Indikator dampak' => $product->public_impact,
        ], fn ($v) => filled($v));
    @endphp

    <section class="hero-surface relative overflow-hidden text-white">
        <div class="grid-lines absolute inset-0" aria-hidden="true"></div>
        <div class="container-page relative grid gap-10 py-14 lg:grid-cols-2 lg:items-center">
            <div>
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-navy-300 hover:text-white"><x-icon name="arrow-left" class="size-4" /> Semua produk</a>
                <div class="mt-5 flex flex-wrap gap-2">
                    <x-theme-badge :theme="$product->theme" />
                    @if ($product->publicBadgeLabel())<span class="badge badge-dark bg-white/10 ring-white/20">{{ $product->publicBadgeLabel() }}</span>@endif
                </div>
                <h1 class="mt-4 text-3xl font-extrabold text-white sm:text-5xl">{{ $product->displayTitle() }}</h1>
                <p class="mt-4 text-lg leading-relaxed text-navy-200">{{ $product->public_short_description }}</p>
                <dl class="mt-8 grid gap-4 border-t border-white/10 pt-6 sm:grid-cols-2">
                    @if ($product->challenge && $product->challenge->isPublished())
                        <div><dt class="eyebrow text-navy-400">Tantangan</dt><dd class="mt-1 text-sm font-semibold"><a href="{{ route('challenges.show', $product->challenge->slug) }}" class="hover:text-signal-300">{{ $product->challenge->title }}</a></dd></div>
                    @endif
                    @if ($owner)<div><dt class="eyebrow text-navy-400">Mitra</dt><dd class="mt-1 text-sm font-semibold">{{ $owner->name }}</dd></div>@endif
                    @if ($team)<div><dt class="eyebrow text-navy-400">Tim</dt><dd class="mt-1 text-sm font-semibold">{{ $team }}</dd></div>@endif
                    @if ($product->public_show_tkt && $product->tkt_level)<div><dt class="eyebrow text-navy-400">Tingkat kesiapan</dt><dd class="mt-1 text-sm font-semibold">TKT {{ $product->tkt_level }}</dd></div>@endif
                    @if ($product->programStage)<div><dt class="eyebrow text-navy-400">Tahap program</dt><dd class="mt-1 text-sm font-semibold">{{ $product->programStage->name }}</dd></div>@endif
                    <div><dt class="eyebrow text-navy-400">Diterbitkan</dt><dd class="mt-1 text-sm font-semibold">{{ fmt_date($product->published_at) }}</dd></div>
                </dl>
            </div>
            <div class="overflow-hidden rounded-[2rem] border border-white/10 bg-white/5">
                @if ($product->public_image_path)
                    <img src="{{ route('media.product-image', $product->public_slug) }}" alt="Gambar {{ $product->displayTitle() }}" class="aspect-[4/3] w-full object-cover">
                @else
                    <div class="flex aspect-[4/3] items-center justify-center text-6xl font-extrabold text-white/70">{{ mb_strtoupper(mb_substr($product->displayTitle(), 0, 2)) }}</div>
                @endif
            </div>
        </div>
    </section>

    <section class="section">
        <div class="container-page grid gap-10 lg:grid-cols-[1fr_320px]">
            <article class="space-y-6">
                @if ($product->public_full_description)
                    <div class="card p-6 sm:p-10"><div class="prose-deepnest">{{ md($product->public_full_description) }}</div></div>
                @endif
                @if ($sections)
                    <div class="grid gap-6 md:grid-cols-2">
                        @foreach ($sections as $heading => $text)
                            <div class="card p-6">
                                <h2 class="text-lg font-extrabold">{{ $heading }}</h2>
                                <div class="mt-3 space-y-3 text-sm leading-relaxed text-navy-700">{{ paragraphs($text) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (! $product->public_full_description && ! $sections)
                    <p class="text-navy-500">Deskripsi lengkap produk akan dilengkapi panitia.</p>
                @endif
            </article>
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                @if ($product->public_show_links && ($product->demo_video_url || $product->repository_url))
                    <div class="card p-6">
                        <p class="font-bold">Tautan</p>
                        <ul class="mt-3 space-y-2 text-sm">
                            @if ($product->demo_video_url)<li><a href="{{ $product->demo_video_url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1.5 font-semibold text-forest-700">Video demo <x-icon name="arrow-up-right" class="size-4" /></a></li>@endif
                            @if ($product->repository_url)<li><a href="{{ $product->repository_url }}" target="_blank" rel="noopener noreferrer nofollow" class="inline-flex items-center gap-1.5 font-semibold text-forest-700">Repositori <x-icon name="arrow-up-right" class="size-4" /></a></li>@endif
                        </ul>
                    </div>
                @endif
                @if ($pdfAvailable)
                    <div class="card p-6">
                        <p class="font-bold">Dokumen proposal</p>
                        <p class="mt-1 text-sm text-navy-600">Dokumen dibuka untuk publik atas izin panitia.</p>
                        <a href="{{ route('media.product-pdf', $product->public_slug) }}" class="btn btn-dark mt-4 w-full"><x-icon name="download" class="size-4" /> Unduh PDF</a>
                    </div>
                @endif
                <div class="card p-6">
                    <p class="font-bold">Tertarik berkolaborasi?</p>
                    <p class="mt-1 text-sm text-navy-600">Hubungi panitia DEEPNEST untuk peluang pilot, pengembangan bersama, atau adopsi.</p>
                    @if (setting('contact_email'))<a href="mailto:{{ setting('contact_email') }}" class="btn btn-outline mt-4 w-full">Hubungi Panitia</a>@endif
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
