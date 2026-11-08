<x-layouts.public title="Pengumuman">
    <x-page-hero title="Pengumuman, berita, dan cerita" eyebrow="Kabar DEEPNEST" lead="Informasi resmi Open Call, jadwal kegiatan, dan cerita dari perjalanan program." />
    <section class="section">
        <div class="container-page">
            <nav class="flex flex-wrap gap-2" aria-label="Kategori">
                <a href="{{ route('announcements.index') }}" class="btn btn-sm {{ ! $category ? 'btn-dark' : 'btn-outline' }}">Semua</a>
                @foreach ($categories as $key => $label)
                    <a href="{{ route('announcements.index', ['kategori' => $key]) }}" class="btn btn-sm {{ $category === $key ? 'btn-dark' : 'btn-outline' }}">{{ $label }}</a>
                @endforeach
            </nav>
            @if ($announcements->isEmpty())
                <x-empty class="mt-10" icon="megaphone" title="Belum ada pengumuman" text="Pengumuman resmi akan dipublikasikan di halaman ini." />
            @else
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($announcements as $announcement)
                        <a href="{{ route('announcements.show', $announcement->slug) }}" class="card card-link flex flex-col p-6">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="badge badge-neutral">{{ $announcement->categoryLabel() }}</span>
                                @if ($announcement->is_pinned)<span class="badge badge-warning">Disematkan</span>@endif
                            </div>
                            <h2 class="mt-4 text-lg font-bold leading-snug text-navy-950">{{ $announcement->title }}</h2>
                            <p class="mt-2 line-clamp-3 text-sm text-navy-600">{{ $announcement->excerpt }}</p>
                            <p class="mt-auto pt-5 text-xs text-navy-400">{{ fmt_date($announcement->published_at) }}</p>
                        </a>
                    @endforeach
                </div>
                {{ $announcements->links() }}
            @endif
        </div>
    </section>
</x-layouts.public>
