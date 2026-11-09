@props(['light' => false, 'compact' => false])
{{-- Logomark DEEPNEST orisinal: tiga busur bersarang (ekosistem) dengan satu simpul (purwarupa). --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5 '.($light ? 'text-paper' : 'text-navy-950')]) }}>
    <svg viewBox="0 0 32 32" class="size-8 shrink-0" aria-hidden="true" focusable="false">
        <path d="M3.5 13.5a12.5 12.5 0 0 0 25 0" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/>
        <path d="M8.5 13.5a7.5 7.5 0 0 0 15 0" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/>
        <path d="M13.5 13.5a2.5 2.5 0 0 0 5 0" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round"/>
        <circle cx="16" cy="6" r="2.7" fill="#c4613a"/>
    </svg>
    @unless ($compact)
        <span class="flex flex-col leading-none">
            <span class="text-[0.98rem] font-semibold tracking-[0.14em]">DEEPNEST</span>
            <span class="mt-1 text-[0.6rem] font-medium uppercase tracking-[0.16em] opacity-60">Universitas Hasanuddin</span>
        </span>
    @endunless
</span>
