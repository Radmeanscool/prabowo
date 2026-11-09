<x-layouts.public title="Pengumuman">
    <x-page-hero title="Pengumuman, berita, dan cerita" eyebrow="Kabar DEEPNEST" lead="Informasi resmi Open Call, jadwal kegiatan, dan cerita dari perjalanan program." />
    <section class="section pt-10 sm:pt-12">
        <div class="container-page">
            <nav class="flex flex-wrap gap-x-7 gap-y-2 overflow-x-auto border-b border-navy-950/15" aria-label="Kategori">
                <a href="{{ route('announcements.index') }}" class="tab-link {{ ! $category ? 'is-active' : '' }}">Semua</a>
                @foreach ($categories as $key => $label)
                    <a href="{{ route('announcements.index', ['kategori' => $key]) }}" class="tab-link {{ $category === $key ? 'is-active' : '' }}">{{ $label }}</a>
                @endforeach
            </nav>
            @if ($announcements->isEmpty())
                <x-empty class="mt-10" icon="megaphone" title="Belum ada pengumuman" text="Pengumuman resmi akan dipublikasikan di halaman ini." />
            @else
                <ul>
                    @foreach ($announcements as $announcement)
                        <li class="border-b border-navy-950/15">
                            <a href="{{ route('announcements.show', $announcement->slug) }}" class="group grid gap-2 py-7 sm:grid-cols-[10rem_1fr] sm:gap-8">
                                <span class="text-sm text-navy-500">
                                    {{ fmt_date($announcement->published_at) }}
                                    <span class="block">{{ $announcement->categoryLabel() }}</span>
                                    @if ($announcement->is_pinned)<span class="block font-medium text-signal-600">Disematkan</span>@endif
                                </span>
                                <span>
                                    <span class="block font-serif text-[1.75rem] leading-snug text-navy-950 decoration-1 underline-offset-4 group-hover:underline">{{ $announcement->title }}</span>
                                    @if ($announcement->excerpt)<span class="mt-2 line-clamp-2 block max-w-3xl text-[0.95rem] leading-relaxed text-navy-600">{{ $announcement->excerpt }}</span>@endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
                {{ $announcements->links() }}
            @endif
        </div>
    </section>
</x-layouts.public>
