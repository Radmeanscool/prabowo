@props(['challenge'])
@php $owner = $challenge->publicOwner(); @endphp
<article {{ $attributes->merge(['class' => 'flex flex-col border-t border-navy-950 pt-3']) }}>
    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-navy-500">
        @if ($challenge->theme)
            <span class="inline-flex items-center gap-1.5"><span class="size-2" style="background: {{ $challenge->theme->color }}" aria-hidden="true"></span>{{ $challenge->theme->name }}</span>
        @endif
        @if ($owner)<span aria-hidden="true">·</span><span>{{ $owner->displayName() }}</span>@endif
    </p>
    <h3 class="mt-4 font-serif text-[1.6rem] leading-snug text-navy-950"><a href="{{ route('challenges.show', $challenge->slug) }}" class="decoration-1 underline-offset-4 hover:underline">{{ $challenge->title }}</a></h3>
    <p class="mt-2 text-[0.95rem] leading-relaxed text-navy-600">{{ $challenge->summary }}</p>
    <div class="mt-auto pt-5">
        <a href="{{ route('challenges.show', $challenge->slug) }}" class="link-arrow text-sm">Pelajari tantangan <x-icon name="arrow-right" class="size-4" /></a>
    </div>
</article>
