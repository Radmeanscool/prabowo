<x-layouts.public :title="$owner->name">
    <x-page-hero :title="$owner->name" :eyebrow="$owner->typeLabel()" :lead="$owner->initial_focus" />
    <section class="section">
        <div class="container-page grid gap-10 lg:grid-cols-[1fr_320px]">
            <div class="space-y-8">
                @if ($owner->description)
                    <div class="card p-6 sm:p-8"><div class="space-y-3 leading-relaxed text-navy-700">{{ paragraphs($owner->description) }}</div></div>
                @endif
                <div>
                    <h2 class="text-2xl font-extrabold">Tantangan dari {{ $owner->displayName() }}</h2>
                    <div class="mt-6 grid gap-6 md:grid-cols-2">
                        @forelse ($challenges as $challenge)
                            <x-challenge-card :challenge="$challenge" />
                        @empty
                            <p class="text-sm text-navy-500">Tantangan dari mitra ini akan diumumkan.</p>
                        @endforelse
                    </div>
                </div>
            </div>
            <aside class="card self-start p-6">
                @if ($owner->logo_path)
                    <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="Logo {{ $owner->name }}" class="mb-4 h-16 w-auto object-contain">
                @endif
                <dl class="space-y-3 text-sm">
                    <div><dt class="font-semibold text-navy-500">Jenis</dt><dd>{{ $owner->typeLabel() }}</dd></div>
                    @if ($owner->theme)<div><dt class="font-semibold text-navy-500">Bidang</dt><dd><x-theme-badge :theme="$owner->theme" /></dd></div>@endif
                    @if ($owner->website)<div><dt class="font-semibold text-navy-500">Situs</dt><dd><a href="{{ $owner->website }}" target="_blank" rel="noopener noreferrer" class="font-semibold text-forest-700 underline break-all">{{ $owner->website }}</a></dd></div>@endif
                </dl>
            </aside>
        </div>
    </section>
</x-layouts.public>
