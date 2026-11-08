@props(['title', 'eyebrow' => null, 'lead' => null])
<section class="hero-surface relative overflow-hidden text-white">
    <div class="grid-lines absolute inset-0" aria-hidden="true"></div>
    <div class="container-page relative py-14 sm:py-16">
        @if ($eyebrow)<p class="eyebrow text-signal-400">{{ $eyebrow }}</p>@endif
        <h1 class="mt-3 max-w-4xl text-3xl font-extrabold text-white sm:text-5xl">{{ $title }}</h1>
        @if ($lead)<p class="mt-4 max-w-3xl text-base leading-relaxed text-navy-200 sm:text-lg">{{ $lead }}</p>@endif
        @if (trim($slot))<div class="mt-6 flex flex-wrap gap-3">{{ $slot }}</div>@endif
    </div>
</section>
