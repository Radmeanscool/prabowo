<x-layouts.public :title="$page->title" :description="$page->summary">
    <x-page-hero :title="$page->title" eyebrow="DEEPNEST" :lead="$page->summary" />
    <section class="section">
        <div class="container-page grid gap-10 lg:grid-cols-[1fr_320px]">
            <article class="card p-6 sm:p-10">
                <div class="prose-deepnest">{{ md($page->body) }}</div>
            </article>
            <aside class="space-y-4 lg:sticky lg:top-24 lg:self-start">
                @isset($themes)
                    <div class="card p-6">
                        <p class="eyebrow text-navy-400">Tema</p>
                        <ul class="mt-3 space-y-3">
                            @foreach ($themes as $theme)
                                <li><a href="{{ route('challenges.index', ['tema' => $theme->code]) }}"><x-theme-badge :theme="$theme" /></a></li>
                            @endforeach
                        </ul>
                    </div>
                @endisset
                <div class="card p-6">
                    <p class="font-bold">Siap bergabung?</p>
                    <p class="mt-1 text-sm text-navy-600">Buat akun, bentuk tim, dan siapkan proposal.</p>
                    <a href="{{ route('register') }}" class="btn btn-primary mt-4 w-full">Daftar Sekarang</a>
                    <a href="{{ route('faq') }}" class="btn btn-ghost mt-2 w-full">Baca FAQ</a>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
