<x-layouts.public :title="$page->title" :description="$page->summary">
    <x-page-hero :title="$page->title" eyebrow="DEEPNEST" :lead="$page->summary" />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
            <article class="lg:col-span-8">
                <div class="prose-deepnest">{{ md($page->body) }}</div>
            </article>
            <aside class="space-y-10 lg:sticky lg:top-24 lg:col-span-3 lg:col-start-10 lg:self-start">
                @isset($themes)
                    <div class="border-t border-navy-950 pt-3">
                        <p class="eyebrow text-navy-500">Tema</p>
                        <ul class="mt-3">
                            @foreach ($themes as $theme)
                                <li class="border-b border-navy-950/10">
                                    <a href="{{ route('challenges.index', ['tema' => $theme->code]) }}" class="flex items-center gap-2 py-2.5 text-navy-800 hover:text-navy-950 hover:underline">
                                        <span class="size-2" style="background: {{ $theme->color }}" aria-hidden="true"></span>{{ $theme->name }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endisset
                <div class="border-t border-navy-950 pt-3">
                    <p class="eyebrow text-navy-500">Ikut serta</p>
                    <p class="mt-3 text-sm leading-relaxed text-navy-700">Buat akun, bentuk tim, dan siapkan proposal. Proposal dapat dikirim saat Open Call dibuka.</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        @if (setting('registration_enabled', true))
                            <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang</a>
                        @endif
                        <a href="{{ route('faq') }}" class="btn btn-outline">Baca FAQ</a>
                    </div>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
