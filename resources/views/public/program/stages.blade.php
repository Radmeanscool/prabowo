<x-layouts.public title="Tahapan Program">
    <x-page-hero title="Tahapan program DEEPNEST" eyebrow="Program 12 bulan · 4 fase" lead="Pendekatan problem-driven, experiential, dan stage-gated learning. Setiap tahap menghasilkan luaran yang menjadi dasar untuk melanjutkan ke tahap berikutnya.">
        <a href="{{ route('timeline') }}" class="btn btn-primary">Lihat Linimasa</a>
        <a href="{{ route('agenda') }}" class="btn btn-outline">Agenda</a>
    </x-page-hero>

    <section class="section pt-14 sm:pt-16">
        <div class="container-page space-y-20">
            @foreach ($phases as $number => $stages)
                <div class="phase-{{ $number }} grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-3">
                        <p class="flex items-center gap-2 pt-3 text-sm text-navy-500"><span class="size-2" style="background: var(--phase)" aria-hidden="true"></span>Fase {{ $number }}</p>
                        <h2 class="mt-3 font-serif text-[2rem] leading-[1.05] text-navy-950">{{ $stages->first()->phase_name }}</h2>
                    </div>
                    <div class="border-t border-navy-950 lg:col-span-9">
                        @foreach ($stages as $stage)
                            <article class="grid gap-4 border-b border-navy-950/15 py-7 md:grid-cols-[11rem_1fr] md:gap-8">
                                <div class="flex items-center gap-3 md:block">
                                    <p class="text-sm text-navy-500">{{ $stage->month_label }}</p>
                                    <p class="md:mt-2"><x-badge :tone="$stage->status === 'completed' ? 'success' : ($stage->status === 'ongoing' ? 'warning' : 'neutral')">{{ $stage->statusLabel() }}</x-badge></p>
                                </div>
                                <div>
                                    <h3 class="font-serif text-[1.65rem] leading-snug text-navy-950">{{ $stage->name }}</h3>
                                    @if ($stage->name_id && $stage->name_id !== $stage->name)<p class="text-sm text-navy-500">{{ $stage->name_id }}</p>@endif
                                    <p class="mt-3 max-w-2xl leading-relaxed text-navy-700">{{ $stage->description }}</p>
                                    @if ($stage->activityList())
                                        <ul class="mt-4 space-y-1.5 text-[0.95rem] text-navy-700">
                                            @foreach ($stage->activityList() as $activity)
                                                <li class="flex gap-3"><span class="text-navy-400" aria-hidden="true">—</span>{{ $activity }}</li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    @if ($stage->output)
                                        <p class="mt-5 text-sm"><span class="font-semibold text-navy-950">Luaran yang dituju:</span> <span class="text-navy-700">{{ $stage->output }}</span></p>
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @foreach ($crossCutting as $stage)
                <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                    <p class="pt-3 text-sm text-navy-500 lg:col-span-3">Lintas fase · {{ $stage->month_label }}</p>
                    <div class="border-t border-navy-950 pt-6 lg:col-span-9">
                        <h2 class="font-serif text-[1.65rem] leading-snug text-navy-950">{{ $stage->name }}</h2>
                        <p class="mt-2 max-w-2xl leading-relaxed text-navy-700">{{ $stage->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
