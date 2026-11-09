@props(['label', 'value', 'icon' => null, 'hint' => null, 'href' => null])
@php $classes = 'card card-body flex items-start gap-4'.($href ? ' card-link' : ''); @endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
@else
<div {{ $attributes->merge(['class' => $classes]) }}>
@endif
    @if ($icon)
        <span class="flex size-10 shrink-0 items-center justify-center rounded-[4px] bg-navy-50 text-navy-600">
            <x-icon :name="$icon" />
        </span>
    @endif
    <div class="min-w-0">
        <p class="text-sm font-medium text-navy-500">{{ $label }}</p>
        <p class="mt-1 font-serif text-[2.1rem] leading-none tabular-nums text-navy-950">{{ $value }}</p>
        @if ($hint)
            <p class="mt-0.5 text-xs text-navy-400">{{ $hint }}</p>
        @endif
    </div>
@if ($href)
</a>
@else
</div>
@endif
