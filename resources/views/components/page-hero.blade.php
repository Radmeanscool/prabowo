@props(['title', 'eyebrow' => null, 'lead' => null])
<section class="border-b border-navy-950/10">
    <div class="container-page pb-12 pt-8 sm:pb-16 sm:pt-12">
        <p class="border-b border-navy-950 pb-3 text-[0.82rem] font-medium text-navy-700">{{ $eyebrow ?? 'DEEPNEST' }}</p>
        <h1 class="mt-10 max-w-4xl font-serif text-[clamp(2.6rem,5.6vw,5rem)] font-normal leading-[0.98] tracking-[-0.012em] text-navy-950 sm:mt-14">{{ $title }}</h1>
        @if ($lead)<p class="lede mt-6 max-w-3xl">{{ $lead }}</p>@endif
        @if (trim($slot))<div class="mt-8 flex flex-wrap items-center gap-3">{{ $slot }}</div>@endif
    </div>
</section>
