<x-layouts.public title="Mentor & Juri">
    <x-page-hero title="Mentor, juri, dan narasumber" eyebrow="Tim pakar" lead="Skema mentor ganda: mentor internal untuk ketepatan ilmiah dan metode, mentor mitra untuk kesesuaian operasional, standar, biaya, dan adopsi. Juri tidak menjadi mentor langsung bagi tim yang dinilainya." />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page space-y-20">
            @php $labels = ['mentor' => 'Mentor', 'juror' => 'Juri', 'speaker' => 'Narasumber']; @endphp
            @if ($groups->isEmpty())
                <div>
                    <div class="grid gap-x-8 gap-y-12 md:grid-cols-2">
                        <div class="border-t border-navy-950 pt-4">
                            <p class="numeral text-[4.5rem] sm:text-[5.5rem]">30</p>
                            <p class="mt-4 font-semibold text-navy-950">Mentor (rencana)</p>
                            <ul class="mt-3 space-y-1.5 text-[0.95rem] text-navy-600">
                                <li>9 dosen Unhas · ketahanan pangan</li>
                                <li>9 praktisi mitra · ketahanan pangan</li>
                                <li>6 dosen Unhas · kesehatan</li>
                                <li>6 praktisi mitra · kesehatan</li>
                            </ul>
                        </div>
                        <div class="border-t border-navy-950 pt-4">
                            <p class="numeral text-[4.5rem] sm:text-[5.5rem]">7</p>
                            <p class="mt-4 font-semibold text-navy-950">Juri (rencana)</p>
                            <ul class="mt-3 space-y-1.5 text-[0.95rem] text-navy-600">
                                <li>4 juri internal Unhas: ketahanan pangan, kesehatan, rekayasa produk, serta inovasi dan komersialisasi</li>
                                <li>3 juri eksternal</li>
                            </ul>
                        </div>
                    </div>
                    <p class="mt-10 max-w-3xl border-l-2 border-navy-950 pl-4 leading-relaxed text-navy-700">Komposisi di atas adalah rencana dalam proposal program. Nama dan profil mentor serta juri ditampilkan setelah terkonfirmasi.</p>
                </div>
            @endif
            @foreach (['juror', 'mentor', 'speaker'] as $type)
                @if (isset($groups[$type]))
                    <div>
                        <div class="border-b border-navy-950 pb-3"><h2 class="subhead">{{ $labels[$type] }}</h2></div>
                        <div class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-4">
                            @foreach ($groups[$type] as $person)
                                <article>
                                    @if ($person->photo_path)
                                        <img src="{{ route('media.person-photo', $person->slug) }}" alt="Foto {{ $person->name }}" class="aspect-[4/5] w-full object-cover grayscale" loading="lazy">
                                    @else
                                        <span class="flex aspect-[4/5] w-full items-end bg-navy-100 p-5 font-serif text-5xl leading-none text-navy-400" aria-hidden="true">{{ $person->initials() }}</span>
                                    @endif
                                    <h3 class="mt-4 font-serif text-[1.45rem] leading-snug text-navy-950">{{ $person->name }}</h3>
                                    <p class="mt-1 text-sm text-navy-500">{{ trim($person->position.($person->position && $person->affiliation ? ', ' : '').$person->affiliation) }}</p>
                                    @if ($person->category || $person->theme)
                                        <div class="mt-3 flex flex-wrap gap-1.5">
                                            @if ($person->category)<x-badge>{{ $person->categoryLabel() }}</x-badge>@endif
                                            @if ($person->theme)<x-theme-badge :theme="$person->theme" />@endif
                                        </div>
                                    @endif
                                    @if ($person->expertise)<p class="mt-3 text-sm leading-relaxed text-navy-600">{{ $person->expertise }}</p>@endif
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endforeach
        </div>
    </section>
</x-layouts.public>
