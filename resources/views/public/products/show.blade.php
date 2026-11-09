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

    <section class="border-b border-navy-950/10">
        <div class="container-page pb-14 pt-8 sm:pb-16 sm:pt-12">
            <div class="flex flex-wrap items-center justify-between gap-x-6 gap-y-1 border-b border-navy-950 pb-3 text-[0.82rem] font-medium text-navy-700">
                <a href="{{ route('products.index') }}" class="inline-flex items-center gap-1.5 hover:text-navy-950"><x-icon name="arrow-left" class="size-4" /> Semua produk</a>
                <span class="inline-flex items-center gap-2">
                    @if ($product->theme)<span class="size-2" style="background: {{ $product->theme->color }}" aria-hidden="true"></span>{{ $product->theme->name }}@endif
                    @if ($product->publicBadgeLabel()) · {{ $product->publicBadgeLabel() }}@endif
                </span>
            </div>
            <div class="mt-10 grid gap-10 lg:grid-cols-12 lg:gap-10">
                <div class="lg:col-span-6">
                    <h1 class="font-serif text-[clamp(2.6rem,5.4vw,4.75rem)] font-normal leading-[0.98] tracking-[-0.012em] text-navy-950">{{ $product->displayTitle() }}</h1>
                    <p class="lede mt-6">{{ $product->public_short_description }}</p>
                    <dl class="mt-10 grid gap-x-8 gap-y-5 border-t border-navy-950/15 pt-6 sm:grid-cols-2">
                        @if ($product->challenge && $product->challenge->isPublished())
                            <div><dt class="eyebrow text-navy-500">Tantangan</dt><dd class="mt-1 font-medium"><a href="{{ route('challenges.show', $product->challenge->slug) }}" class="link">{{ $product->challenge->title }}</a></dd></div>
                        @endif
                        @if ($owner)<div><dt class="eyebrow text-navy-500">Mitra</dt><dd class="mt-1 font-medium text-navy-950">{{ $owner->name }}</dd></div>@endif
                        @if ($team)<div><dt class="eyebrow text-navy-500">Tim</dt><dd class="mt-1 font-medium text-navy-950">{{ $team }}</dd></div>@endif
                        @if ($product->public_show_tkt && $product->tkt_level)<div><dt class="eyebrow text-navy-500">Tingkat kesiapan</dt><dd class="mt-1 font-medium text-navy-950">TKT {{ $product->tkt_level }}</dd></div>@endif
                        @if ($product->programStage)<div><dt class="eyebrow text-navy-500">Tahap program</dt><dd class="mt-1 font-medium text-navy-950">{{ $product->programStage->name }}</dd></div>@endif
                        <div><dt class="eyebrow text-navy-500">Diterbitkan</dt><dd class="mt-1 font-medium text-navy-950">{{ fmt_date($product->published_at) }}</dd></div>
                    </dl>
                </div>
                <div class="lg:col-span-6">
                    @if ($product->public_image_path)
                        <img src="{{ route('media.product-image', $product->public_slug) }}" alt="Gambar {{ $product->displayTitle() }}" class="aspect-[4/3] w-full object-cover">
                    @else
                        <div class="flex aspect-[4/3] items-end bg-navy-100 p-6 font-serif text-7xl leading-none text-navy-400" aria-hidden="true">{{ mb_strtoupper(mb_substr($product->displayTitle(), 0, 2)) }}</div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="section pt-14 sm:pt-16">
        <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
            <article class="lg:col-span-8">
                @if ($product->public_full_description)
                    <div class="prose-deepnest">{{ md($product->public_full_description) }}</div>
                @endif
                @if ($sections)
                    <div class="{{ $product->public_full_description ? 'mt-14' : '' }}">
                        @foreach ($sections as $heading => $text)
                            <div class="grid gap-3 border-t border-navy-950/15 py-7 first:border-navy-950 first:pt-5 sm:grid-cols-[11rem_1fr] sm:gap-8">
                                <h2 class="text-sm font-semibold text-navy-950">{{ $heading }}</h2>
                                <div class="space-y-3 leading-relaxed text-navy-700">{{ paragraphs($text) }}</div>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if (! $product->public_full_description && ! $sections)
                    <p class="text-navy-500">Deskripsi lengkap produk akan dilengkapi panitia.</p>
                @endif
            </article>
            <aside class="space-y-10 lg:sticky lg:top-24 lg:col-span-3 lg:col-start-10 lg:self-start">
                @if ($product->public_show_links && ($product->demo_video_url || $product->repository_url))
                    <div class="border-t border-navy-950 pt-3">
                        <p class="eyebrow text-navy-500">Tautan</p>
                        <ul class="mt-3 space-y-2">
                            @if ($product->demo_video_url)<li><a href="{{ $product->demo_video_url }}" target="_blank" rel="noopener noreferrer nofollow" class="link-arrow">Video demo <x-icon name="arrow-up-right" class="size-4" /></a></li>@endif
                            @if ($product->repository_url)<li><a href="{{ $product->repository_url }}" target="_blank" rel="noopener noreferrer nofollow" class="link-arrow">Repositori <x-icon name="arrow-up-right" class="size-4" /></a></li>@endif
                        </ul>
                    </div>
                @endif
                @if ($pdfAvailable)
                    <div class="border-t border-navy-950 pt-3">
                        <p class="eyebrow text-navy-500">Dokumen proposal</p>
                        <p class="mt-3 text-sm leading-relaxed text-navy-700">Dokumen dibuka untuk publik atas izin panitia.</p>
                        <a href="{{ route('media.product-pdf', $product->public_slug) }}" class="btn btn-primary mt-4"><x-icon name="download" class="size-4" /> Unduh PDF</a>
                    </div>
                @endif
                <div class="border-t border-navy-950 pt-3">
                    <p class="eyebrow text-navy-500">Tertarik berkolaborasi?</p>
                    <p class="mt-3 text-sm leading-relaxed text-navy-700">Hubungi panitia DEEPNEST untuk peluang pilot, pengembangan bersama, atau adopsi.</p>
                    @if (setting('contact_email'))<a href="mailto:{{ setting('contact_email') }}" class="btn btn-outline mt-4">Hubungi Panitia</a>@endif
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
