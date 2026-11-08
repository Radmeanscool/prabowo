@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'text' => null])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-navy-200 bg-white/60 px-6 py-12 text-center']) }}>
    <span class="mx-auto flex size-12 items-center justify-center rounded-2xl bg-navy-50 text-navy-400">
        <x-icon :name="$icon" class="size-6" />
    </span>
    <p class="mt-4 font-bold text-navy-900">{{ $title }}</p>
    @if ($text)
        <p class="mx-auto mt-1 max-w-md text-sm leading-relaxed text-navy-500">{{ $text }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
