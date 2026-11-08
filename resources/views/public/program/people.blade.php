<x-layouts.public title="Mentor & Juri">
    <x-page-hero title="Mentor, juri, dan narasumber" eyebrow="Tim pakar" lead="Skema mentor ganda: mentor internal untuk ketepatan ilmiah dan metode, mentor mitra untuk kesesuaian operasional, standar, biaya, dan adopsi. Juri tidak menjadi mentor langsung bagi tim yang dinilainya." />
    <section class="section">
        <div class="container-page space-y-14">
            @php $labels = ['mentor' => 'Mentor', 'juror' => 'Juri', 'speaker' => 'Narasumber']; @endphp
            @if ($groups->isEmpty())
                <div class="grid gap-6 md:grid-cols-2">
                    <div class="card p-8">
                        <p class="text-5xl font-extrabold">30</p>
                        <p class="mt-2 text-lg font-bold">Mentor (rencana)</p>
                        <ul class="mt-4 space-y-2 text-sm text-navy-600">
                            <li>9 dosen Unhas · ketahanan pangan</li>
                            <li>9 praktisi mitra · ketahanan pangan</li>
                            <li>6 dosen Unhas · kesehatan</li>
                            <li>6 praktisi mitra · kesehatan</li>
                        </ul>
                    </div>
                    <div class="card p-8">
                        <p class="text-5xl font-extrabold">7</p>
                        <p class="mt-2 text-lg font-bold">Juri (rencana)</p>
                        <ul class="mt-4 space-y-2 text-sm text-navy-600">
                            <li>4 juri internal Unhas: ketahanan pangan, kesehatan, rekayasa produk, serta inovasi dan komersialisasi</li>
                            <li>3 juri eksternal dari BRIN, Kementerian Kesehatan, dan Kementerian Pertanian</li>
                        </ul>
                    </div>
                    <p class="text-sm text-navy-500 md:col-span-2">Profil mentor dan juri ditampilkan setelah terkonfirmasi.</p>
                </div>
            @endif
            @foreach (['juror', 'mentor', 'speaker'] as $type)
                @if (isset($groups[$type]))
                    <div>
                        <h2 class="text-2xl font-extrabold">{{ $labels[$type] }}</h2>
                        <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($groups[$type] as $person)
                                <article class="card p-5">
                                    @if ($person->photo_path)
                                        <img src="{{ route('media.person-photo', $person->slug) }}" alt="Foto {{ $person->name }}" class="aspect-square w-full rounded-2xl object-cover" loading="lazy">
                                    @else
                                        <span class="hero-surface flex aspect-square w-full items-center justify-center rounded-2xl text-4xl font-extrabold text-white/90">{{ $person->initials() }}</span>
                                    @endif
                                    <h3 class="mt-4 font-bold leading-snug">{{ $person->name }}</h3>
                                    <p class="mt-1 text-sm text-navy-500">{{ trim($person->position.($person->position && $person->affiliation ? ', ' : '').$person->affiliation) }}</p>
                                    <div class="mt-3 flex flex-wrap gap-1.5">
                                        @if ($person->category)<x-badge>{{ $person->categoryLabel() }}</x-badge>@endif
                                        @if ($person->theme)<x-theme-badge :theme="$person->theme" :icon="false" />@endif
                                    </div>
                                    @if ($person->expertise)<p class="mt-3 text-sm text-navy-600">{{ $person->expertise }}</p>@endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
</x-layouts.public>
