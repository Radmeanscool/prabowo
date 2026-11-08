<x-layouts.public :title="$announcement->title" :description="$announcement->excerpt">
    <x-page-hero :title="$announcement->title" :eyebrow="$announcement->categoryLabel().' · '.fmt_date($announcement->published_at)" :lead="$announcement->excerpt" />
    <section class="section">
        <div class="container-page grid gap-10 lg:grid-cols-[1fr_320px]">
            <article class="card p-6 sm:p-10"><div class="prose-deepnest">{{ md($announcement->body) }}</div></article>
            <aside class="space-y-4">
                <p class="eyebrow text-navy-400">Lainnya</p>
                @forelse ($latest as $item)
                    <a href="{{ route('announcements.show', $item->slug) }}" class="card card-link block p-5">
                        <p class="text-xs text-navy-400">{{ fmt_date($item->published_at) }}</p>
                        <p class="mt-1 font-bold leading-snug">{{ $item->title }}</p>
                    </a>
                @empty
                    <p class="text-sm text-navy-500">Belum ada pengumuman lain.</p>
                @endforelse
            </aside>
        </div>
    </section>
</x-layouts.public>
