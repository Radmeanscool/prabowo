<x-layouts.public title="Sumber Daya">
    <x-page-hero title="Sumber daya dan dokumen" eyebrow="Sumber daya" lead="Panduan, templat, kebijakan, dan materi resmi DEEPNEST. Dokumen khusus peserta tersedia di dashboard peserta." />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page space-y-16">
            @forelse ($documents as $category => $items)
                <div class="grid gap-4 lg:grid-cols-12 lg:gap-10">
                    <h2 class="eyebrow pt-4 text-navy-500 lg:col-span-3">{{ $category }}</h2>
                    <ul class="border-t border-navy-950 lg:col-span-9">
                        @foreach ($items as $document)
                            <li class="flex flex-col gap-4 border-b border-navy-950/15 py-5 sm:flex-row sm:items-start sm:justify-between sm:gap-8">
                                <div class="min-w-0">
                                    <p class="font-serif text-[1.45rem] leading-snug text-navy-950">{{ $document->title }}</p>
                                    @if ($document->description)<p class="mt-1 max-w-2xl text-sm leading-relaxed text-navy-600">{{ $document->description }}</p>@endif
                                </div>
                                <div class="shrink-0">
                                    @if ($document->hasFile())
                                        <a href="{{ route('resources.download', $document->slug) }}" class="btn btn-outline btn-sm"><x-icon name="download" class="size-4" /> Unduh · {{ format_bytes($document->size) }}</a>
                                    @elseif ($document->external_url)
                                        <a href="{{ $document->external_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline btn-sm">Buka tautan <x-icon name="arrow-up-right" class="size-4" /></a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <x-empty icon="book" title="Dokumen akan tersedia" text="Panduan dan templat proposal dipublikasikan bersama pembukaan Open Call." />
            @endforelse
        </div>
    </section>
</x-layouts.public>
