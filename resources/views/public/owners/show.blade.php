<x-layouts.public :title="$owner->name">
    <x-page-hero :title="$owner->name" :eyebrow="$owner->typeLabel()" :lead="$owner->initial_focus" />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page grid gap-14 lg:grid-cols-12 lg:gap-10">
            <div class="space-y-14 lg:col-span-8">
                @if ($owner->description)
                    <div class="space-y-4 text-[1.04rem] leading-8 text-navy-800">{{ paragraphs($owner->description) }}</div>
                @endif
                <div>
                    <div class="border-b border-navy-950 pb-3"><h2 class="subhead">Tantangan dari {{ $owner->displayName() }}</h2></div>
                    @if ($challenges->isNotEmpty())
                        <div class="mt-10 grid gap-x-8 gap-y-14 md:grid-cols-2">
                            @foreach ($challenges as $challenge)
                                <x-challenge-card :challenge="$challenge" />
                            @endforeach
                        </div>
                    @else
                        <p class="mt-6 text-navy-500">Tantangan dari mitra ini akan diumumkan.</p>
                    @endif
                </div>
            </div>
            <aside class="lg:col-span-3 lg:col-start-10 lg:self-start">
                <div class="border-t border-navy-950 pt-3">
                    @if ($owner->logo_path)
                        <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="Logo {{ $owner->name }}" class="mb-6 mt-3 h-16 w-auto object-contain">
                    @endif
                    <dl class="space-y-5 text-sm">
                        <div><dt class="eyebrow text-navy-500">Jenis</dt><dd class="mt-1 text-navy-900">{{ $owner->typeLabel() }}</dd></div>
                        @if ($owner->theme)<div><dt class="eyebrow text-navy-500">Bidang</dt><dd class="mt-1 flex items-center gap-2 text-navy-900"><span class="size-2" style="background: {{ $owner->theme->color }}" aria-hidden="true"></span>{{ $owner->theme->name }}</dd></div>@endif
                        @if ($owner->website)<div><dt class="eyebrow text-navy-500">Situs</dt><dd class="mt-1"><a href="{{ $owner->website }}" target="_blank" rel="noopener noreferrer" class="link break-all">{{ $owner->website }}</a></dd></div>@endif
                    </dl>
                </div>
            </aside>
        </div>
    </section>
</x-layouts.public>
