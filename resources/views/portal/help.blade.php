<x-layouts.portal title="Bantuan">
    <x-page-header title="Bantuan & FAQ" subtitle="Jawaban atas pertanyaan yang sering diajukan. Hubungi panitia bila butuh bantuan lebih lanjut." />
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            @forelse ($faqs as $category => $items)
                <section>
                    <h2 class="eyebrow text-navy-400">{{ $category }}</h2>
                    <div class="mt-3 space-y-3">
                        @foreach ($items as $faq)
                            <details class="card group p-5">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 font-semibold text-navy-950 [&::-webkit-details-marker]:hidden">
                                    {{ $faq->question }} <x-icon name="chevron-down" class="size-4 text-navy-400 transition group-open:rotate-180" />
                                </summary>
                                <div class="prose-deepnest mt-3">{{ md($faq->answer) }}</div>
                            </details>
                        @endforeach
                    </div>
                </section>
            @empty
                <x-empty icon="help" title="Belum ada FAQ" />
            @endforelse
        </div>
        <div class="card card-body self-start">
            <h2 class="card-title">Kontak panitia</h2>
            <ul class="mt-3 space-y-3 text-sm">
                @if (setting('contact_email'))<li class="flex gap-2"><x-icon name="mail" class="size-4 text-navy-400" /><a href="mailto:{{ setting('contact_email') }}" class="font-semibold text-forest-700">{{ setting('contact_email') }}</a></li>@endif
                @if (setting('contact_phone'))<li class="flex gap-2"><x-icon name="phone" class="size-4 text-navy-400" />{{ setting('contact_phone') }}</li>@endif
                @if (setting('contact_address'))<li class="flex gap-2"><x-icon name="map-pin" class="size-4 text-navy-400" />{{ setting('contact_address') }}</li>@endif
            </ul>
        </div>
    </div>
</x-layouts.portal>
