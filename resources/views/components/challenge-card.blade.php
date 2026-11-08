@props(['challenge'])
@php $owner = $challenge->publicOwner(); @endphp
<article {{ $attributes->merge(['class' => 'card card-link flex flex-col p-6']) }}>
    <div class="flex flex-wrap items-center gap-2">
        <x-theme-badge :theme="$challenge->theme" />
        @if ($owner)<span class="badge badge-neutral"><x-icon name="building" class="size-3.5" /> {{ $owner->displayName() }}</span>@endif
    </div>
    <h3 class="mt-4 text-lg font-bold leading-snug text-navy-950"><a href="{{ route('challenges.show', $challenge->slug) }}" class="hover:text-forest-700">{{ $challenge->title }}</a></h3>
    <p class="mt-2 text-sm leading-relaxed text-navy-600">{{ $challenge->summary }}</p>
    <div class="mt-auto pt-5">
        <a href="{{ route('challenges.show', $challenge->slug) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-forest-700 hover:text-forest-600">Pelajari tantangan <x-icon name="arrow-right" class="size-4" /></a>
    </div>
</article>
