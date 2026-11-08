<x-layouts.public title="FAQ">
    <x-page-hero title="Pertanyaan yang sering diajukan" eyebrow="FAQ" lead="Belum menemukan jawaban? Hubungi panitia melalui kontak di bagian bawah halaman." />
    <section class="section">
        <div class="container-page max-w-4xl space-y-10">
            @forelse ($faqs as $category => $items)
                <div>
                    <h2 class="eyebrow text-forest-600">{{ $category }}</h2>
                    <div class="mt-4 space-y-3">
                        @foreach ($items as $faq)
                            <details class="card group p-6">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-4 text-lg font-bold text-navy-950 [&::-webkit-details-marker]:hidden">
                                    {{ $faq->question }}
                                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy-50 text-navy-500 transition group-open:rotate-45"><x-icon name="plus" class="size-4" /></span>
                                </summary>
                                <div class="prose-deepnest mt-4">{{ md($faq->answer) }}</div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-empty icon="help" title="Belum ada FAQ" />
            @endforelse
        </div>
    </section>
</x-layouts.public>
