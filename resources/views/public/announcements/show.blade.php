<x-layouts.public :title="$announcement->title" :description="$announcement->excerpt">
    <x-page-hero :title="$announcement->title" :eyebrow="$announcement->categoryLabel().' · '.fmt_date($announcement->published_at)" :lead="$announcement->excerpt" />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
            <article class="lg:col-span-8"><div class="prose-deepnest">{{ md($announcement->body) }}</div></article>
            <aside class="lg:col-span-3 lg:col-start-10">
                <div class="border-t border-navy-950 pt-3">
                    <p class="eyebrow text-navy-500">Pengumuman lain</p>
                    <ul class="mt-2">
                        @forelse ($latest as $item)
                            <li class="border-b border-navy-950/10">
                                <a href="{{ route('announcements.show', $item->slug) }}" class="group block py-3">
                                    <span class="block text-xs text-navy-500">{{ fmt_date($item->published_at) }}</span>
                                    <span class="mt-1 block font-medium leading-snug text-navy-900 group-hover:underline">{{ $item->title }}</span>
                                </a>
                            </li>
                        @empty
                            <li class="py-3 text-sm text-navy-500">Belum ada pengumuman lain.</li>
                        @endforelse
                    </ul>
                    <a href="{{ route('announcements.index') }}" class="link mt-4 inline-block text-sm">Semua pengumuman</a>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
