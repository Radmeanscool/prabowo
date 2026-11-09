<x-layouts.public title="Mitra Challenge Owner">
    <x-page-hero title="Mitra pemilik tantangan" eyebrow="Challenge owner" lead="Mitra merumuskan masalah operasional nyata, menyediakan data dan fasilitas, mendampingi tim, memvalidasi purwarupa, menyediakan lokasi uji, dan berpotensi menjadi pengguna serta mitra hilirisasi." />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page">
            @if ($owners->isEmpty())
                <x-empty icon="building" title="Daftar mitra ditampilkan setelah ada persetujuan publikasi" text="Mitra berasal dari unsur pemerintah daerah dan instansi pemerintah, industri, koperasi dan asosiasi, serta fasilitas pelayanan kesehatan." />
            @else
                <ul class="grid gap-x-8 gap-y-14 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($owners as $owner)
                        <li class="border-t border-navy-950 pt-3">
                            <a href="{{ route('owners.show', $owner->slug) }}" class="group block">
                                <p class="text-sm text-navy-500">{{ $owner->typeLabel() }}{{ $owner->theme ? ' · '.$owner->theme->name : '' }}</p>
                                @if ($owner->logo_path)
                                    <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="Logo {{ $owner->name }}" class="mt-5 h-12 w-auto max-w-40 object-contain" loading="lazy">
                                @endif
                                <p class="mt-4 font-serif text-[1.6rem] leading-snug text-navy-950 decoration-1 underline-offset-4 group-hover:underline">{{ $owner->name }}</p>
                                @if ($owner->initial_focus)<p class="mt-2 text-[0.95rem] leading-relaxed text-navy-600">{{ $owner->initial_focus }}</p>@endif
                                <p class="mt-4 text-sm text-navy-500">{{ $owner->challenges_count }} tantangan terbit</p>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layouts.public>
