@props(['product'])
@php
    $owner = $product->challenge?->publicOwner();
    $team = $product->publicTeamLabel();
    $url = route('products.show', $product->public_slug);
@endphp
<article {{ $attributes->merge(['class' => 'group flex flex-col']) }}>
    <a href="{{ $url }}" class="relative block aspect-[4/3] overflow-hidden bg-navy-100" tabindex="-1" aria-hidden="true">
        @if ($product->public_image_path)
            <img src="{{ route('media.product-image', $product->public_slug) }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-[1.02]">
        @else
            <span class="flex size-full items-end p-5">
                <span class="font-serif text-6xl leading-none text-navy-400">{{ mb_strtoupper(mb_substr($product->displayTitle(), 0, 2)) }}</span>
            </span>
        @endif
    </a>
    <div class="mt-4 flex flex-1 flex-col border-t border-navy-950 pt-3">
        <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-navy-500">
            @if ($product->theme)
                <span class="inline-flex items-center gap-1.5"><span class="size-2" style="background: {{ $product->theme->color }}" aria-hidden="true"></span>{{ $product->theme->name }}</span>
            @endif
            @if ($product->publicBadgeLabel())
                <span aria-hidden="true">·</span><span>{{ $product->publicBadgeLabel() }}</span>
            @endif
        </p>
        <h3 class="mt-3 font-serif text-[1.6rem] leading-snug text-navy-950"><a href="{{ $url }}" class="decoration-1 underline-offset-4 hover:underline">{{ $product->displayTitle() }}</a></h3>
        <p class="mt-2 line-clamp-3 text-[0.95rem] leading-relaxed text-navy-600">{{ $product->public_short_description }}</p>
        @if ($owner || $team || ($product->challenge && $product->challenge->isPublished()))
            <dl class="mt-4 space-y-1 text-sm text-navy-500">
                @if ($owner)<div><dt class="inline">Mitra:</dt> <dd class="inline text-navy-700">{{ $owner->displayName() }}</dd></div>@endif
                @if ($product->challenge && $product->challenge->isPublished())<div><dt class="inline">Tantangan:</dt> <dd class="inline text-navy-700">{{ $product->challenge->title }}</dd></div>@endif
                @if ($team)<div><dt class="inline">Tim:</dt> <dd class="inline text-navy-700">{{ $team }}</dd></div>@endif
            </dl>
        @endif
    </div>
</article>
