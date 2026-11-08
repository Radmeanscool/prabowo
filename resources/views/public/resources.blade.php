<x-layouts.public title="Sumber Daya">
    <x-page-hero title="Sumber daya dan dokumen" eyebrow="Sumber daya" lead="Panduan, templat, kebijakan, dan materi resmi DEEPNEST. Dokumen khusus peserta tersedia di dashboard peserta." />
    <section class="section">
        <div class="container-page space-y-10">
            @forelse ($documents as $category => $items)
                <div>
                    <h2 class="text-2xl font-extrabold">{{ $category }}</h2>
                    <div class="mt-5 grid gap-4 md:grid-cols-2">
                        @foreach ($items as $document)
                            <div class="card flex items-start gap-4 p-5">
                                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-navy-50 text-navy-600"><x-icon name="file" /></span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-bold">{{ $document->title }}</p>
                                    @if ($document->description)<p class="mt-1 text-sm text-navy-600">{{ $document->description }}</p>@endif
                                    <div class="mt-3">
                                        @if ($document->hasFile())
                                            <a href="{{ route('resources.download', $document->slug) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Unduh ({{ format_bytes($document->size) }})</a>
                                        @elseif ($document->external_url)
                                            <a href="{{ $document->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Buka tautan <x-icon name="arrow-up-right" class="size-4" /></a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-empty icon="book" title="Dokumen akan tersedia" text="Panduan dan templat proposal dipublikasikan bersama pembukaan Open Call." />
            @endforelse
        </div>
    </section>
</x-layouts.public>
