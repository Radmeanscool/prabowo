@props(['icon' => 'inbox', 'title' => 'Belum ada data', 'text' => null])
<div {{ $attributes->merge(['class' => 'border border-dashed border-navy-300 px-6 py-12 text-center']) }}>
    <x-icon :name="$icon" class="mx-auto size-6 text-navy-400" />
    <p class="mt-4 font-serif text-2xl leading-snug text-navy-950">{{ $title }}</p>
    @if ($text)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-navy-500">{{ $text }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-5 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</div>
