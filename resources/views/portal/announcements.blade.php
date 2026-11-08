<x-layouts.portal title="Pengumuman">
    <x-page-header title="Pengumuman" subtitle="Informasi terbaru dari panitia DEEPNEST." />
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-4 lg:col-span-2">
            @forelse ($announcements as $announcement)
                <article class="card card-body">
                    <div class="flex flex-wrap items-center gap-2 text-xs text-navy-500">
                        <x-badge>{{ $announcement->categoryLabel() }}</x-badge>
                        @if ($announcement->is_pinned)<x-badge tone="warning">Disematkan</x-badge>@endif
                        <span>{{ fmt_date($announcement->published_at ?? $announcement->created_at) }}</span>
                    </div>
                    <h2 class="mt-2 text-lg font-bold">{{ $announcement->title }}</h2>
                    <div class="prose-deepnest mt-3">{{ md($announcement->body) }}</div>
                </article>
            @empty
                <x-empty icon="megaphone" title="Belum ada pengumuman" />
            @endforelse
            {{ $announcements->links() }}
        </div>
        <div class="card card-body self-start">
            <h2 class="card-title">Dokumen</h2>
            <ul class="mt-3 space-y-3">
                @forelse ($documents as $document)
                    <li>
                        <a href="{{ $document->hasFile() ? route('resources.download', $document->slug) : $document->external_url }}" @unless ($document->hasFile()) target="_blank" rel="noopener noreferrer" @endunless class="flex items-start gap-2 text-sm font-semibold text-navy-800 hover:text-forest-700">
                            <x-icon name="file" class="mt-0.5 size-4 text-navy-400" /> {{ $document->title }}
                        </a>
                    </li>
                @empty
                    <li class="text-sm text-navy-500">Belum ada dokumen.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.portal>
