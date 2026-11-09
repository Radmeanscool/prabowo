<x-layouts.public title="FAQ">
    <x-page-hero title="Pertanyaan yang sering diajukan" eyebrow="FAQ" lead="Belum menemukan jawaban? Hubungi panitia melalui kontak di bagian bawah halaman." />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page space-y-16">
            @forelse ($faqs as $category => $items)
                <div class="grid gap-4 lg:grid-cols-12 lg:gap-10">
                    <h2 class="eyebrow pt-4 text-navy-500 lg:col-span-3">{{ $category }}</h2>
                    <div class="border-t border-navy-950 lg:col-span-9">
                        @foreach ($items as $faq)
                            <details class="group border-b border-navy-950/15">
                                <summary class="flex cursor-pointer list-none items-start justify-between gap-6 py-5 font-serif text-[1.45rem] leading-snug text-navy-950 [&::-webkit-details-marker]:hidden">
                                    {{ $faq->question }}
                                    <x-icon name="plus" class="mt-1.5 size-5 shrink-0 text-navy-500 transition-transform group-open:rotate-45" />
                                </summary>
                                <div class="prose-deepnest max-w-3xl pb-6">{{ md($faq->answer) }}</div>
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
