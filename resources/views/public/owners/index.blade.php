<x-layouts.public title="Mitra Challenge Owner">
    <x-page-hero title="Mitra pemilik tantangan" eyebrow="Challenge owner" lead="Mitra merumuskan masalah operasional nyata, menyediakan data dan fasilitas, mendampingi tim, memvalidasi purwarupa, menyediakan lokasi uji, dan berpotensi menjadi pengguna serta mitra hilirisasi." />
    <section class="section">
        <div class="container-page">
            @if ($owners->isEmpty())
                <x-empty icon="building" title="Daftar mitra akan ditampilkan setelah persetujuan publikasi" text="Mitra berasal dari unsur pemerintah daerah dan instansi pemerintah, industri, koperasi dan asosiasi, serta fasilitas pelayanan kesehatan." />
            @else
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($owners as $owner)
                        <a href="{{ route('owners.show', $owner->slug) }}" class="card card-link flex flex-col p-6">
                            <div class="flex items-center gap-4">
                                @if ($owner->logo_path)
                                    <img src="{{ route('media.owner-logo', $owner->slug) }}" alt="Logo {{ $owner->name }}" class="h-12 w-16 object-contain" loading="lazy">
                                @else
                                    <span class="flex size-12 items-center justify-center rounded-xl bg-navy-950 text-sm font-extrabold text-signal-400">{{ $owner->initials() }}</span>
                                @endif
                                <div class="min-w-0">
                                    <p class="font-bold leading-snug text-navy-950">{{ $owner->name }}</p>
                                    <p class="text-xs text-navy-500">{{ $owner->typeLabel() }}{{ $owner->theme ? ' · '.$owner->theme->name : '' }}</p>
                                </div>
                            </div>
                            @if ($owner->initial_focus)<p class="mt-4 text-sm leading-relaxed text-navy-600">{{ $owner->initial_focus }}</p>@endif
                            <p class="mt-auto pt-4 text-xs font-bold text-forest-700">{{ $owner->challenges_count }} tantangan terbit</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
