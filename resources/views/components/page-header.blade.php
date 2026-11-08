@props(['title', 'subtitle' => null, 'eyebrow' => null])
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between']) }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="eyebrow text-forest-600">{{ $eyebrow }}</p>
        @endif
        <h1 class="mt-1 text-2xl font-extrabold sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-1.5 max-w-3xl text-sm leading-relaxed text-navy-500">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
