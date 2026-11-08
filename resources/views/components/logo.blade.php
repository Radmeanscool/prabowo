@props(['light' => false, 'compact' => false])
{{-- Logomark DEEPNEST orisinal: "sarang" tiga lapis (ekosistem) dengan simpul purwarupa di tengah. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <svg viewBox="0 0 40 40" class="size-9 shrink-0" aria-hidden="true" focusable="false">
        <rect width="40" height="40" rx="11" fill="#0c1c34"/>
        <path d="M7.5 18.5a12.5 12.5 0 0025 0" fill="none" stroke="#f5b93a" stroke-width="2.6" stroke-linecap="round"/>
        <path d="M12 18.5a8 8 0 0016 0" fill="none" stroke="#45a57f" stroke-width="2.6" stroke-linecap="round"/>
        <path d="M16.5 18.5a3.5 3.5 0 007 0" fill="none" stroke="#ffffff" stroke-width="2.6" stroke-linecap="round"/>
        <circle cx="20" cy="11" r="3.2" fill="#f5b93a"/>
    </svg>
    @unless ($compact)
        <span class="flex flex-col leading-none">
            <span class="text-[1.05rem] font-extrabold tracking-[0.08em] {{ $light ? 'text-white' : 'text-navy-950' }}">DEEPNEST</span>
            <span class="mt-1 text-[0.62rem] font-semibold uppercase tracking-[0.14em] {{ $light ? 'text-navy-200' : 'text-navy-500' }}">Universitas Hasanuddin</span>
        </span>
    @endunless
</span>
