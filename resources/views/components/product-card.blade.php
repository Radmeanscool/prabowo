@props(['product'])
@php
    $owner = $product->challenge?->publicOwner();
    $team = $product->publicTeamLabel();
@endphp
<article {{ $attributes->merge(['class' => 'card card-link group flex flex-col overflow-hidden']) }}>
    <a href="{{ route('products.show', $product->public_slug) }}" class="relative block aspect-[16/10] overflow-hidden bg-navy-900" tabindex="-1" aria-hidden="true">
        @if ($product->public_image_path)
            <img src="{{ route('media.product-image', $product->public_slug) }}" alt="" loading="lazy" class="size-full object-cover transition duration-500 group-hover:scale-105">
        @else
            <span class="hero-surface flex size-full items-center justify-center">
                <span class="text-4xl font-extrabold tracking-widest text-white/80">{{ mb_strtoupper(mb_substr($product->displayTitle(), 0, 2)) }}</span>
            </span>
        @endif
        @if ($product->publicBadgeLabel())
            <span class="badge badge-dark absolute left-3 top-3 bg-navy-950/85 ring-white/20 backdrop-blur">{{ $product->publicBadgeLabel() }}</span>
        @endif
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap gap-2">
            <x-theme-badge :theme="$product->theme" />
            @if ($owner)<span class="badge badge-neutral">{{ $owner->displayName() }}</span>@endif
        </div>
        <h3 class="mt-3 text-lg font-bold leading-snug text-navy-950"><a href="{{ route('products.show', $product->public_slug) }}" class="hover:text-forest-700">{{ $product->displayTitle() }}</a></h3>
        <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-navy-600">{{ $product->public_short_description }}</p>
        @if ($product->challenge && $product->challenge->isPublished())
            <p class="mt-3 text-xs text-navy-500"><span class="font-semibold">Tantangan:</span> {{ $product->challenge->title }}</p>
        @endif
        @if ($team)<p class="mt-1 text-xs text-navy-500"><span class="font-semibold">Tim:</span> {{ $team }}</p>@endif
        <div class="mt-auto pt-5">
            <a href="{{ route('products.show', $product->public_slug) }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-forest-700 hover:text-forest-600">Lihat Produk <x-icon name="arrow-right" class="size-4" /></a>
        </div>
    </div>
</article>
