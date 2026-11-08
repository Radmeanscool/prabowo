<x-layouts.public title="Tahapan Program">
    <x-page-hero title="Tahapan program DEEPNEST" eyebrow="Program 12 bulan · 4 fase" lead="Pendekatan problem-driven, experiential, dan stage-gated learning. Setiap tahap menghasilkan luaran yang menjadi dasar untuk melanjutkan ke tahap berikutnya.">
        <a href="{{ route('timeline') }}" class="btn btn-primary">Lihat Linimasa</a>
        <a href="{{ route('agenda') }}" class="btn btn-outline-light">Agenda</a>
    </x-page-hero>

    <section class="section">
        <div class="container-page space-y-14">
            @foreach ($phases as $number => $stages)
                <div class="phase-{{ $number }}">
                    <div class="flex items-center gap-4">
                        <span class="flex size-12 items-center justify-center rounded-2xl text-lg font-extrabold text-white" style="background: var(--phase)">{{ $number }}</span>
                        <div>
                            <p class="eyebrow" style="color: var(--phase)">Fase {{ $number }}</p>
                            <h2 class="text-2xl font-extrabold">{{ $stages->first()->phase_name }}</h2>
                        </div>
                    </div>
                    <div class="mt-6 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        @foreach ($stages as $stage)
                            <article class="card relative overflow-hidden p-6">
                                <span class="absolute inset-y-0 left-0 w-1.5" style="background: var(--phase)"></span>
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="text-lg font-bold">{{ $stage->name }}</h3>
                                        @if ($stage->name_id && $stage->name_id !== $stage->name)<p class="text-sm text-navy-500">{{ $stage->name_id }}</p>@endif
                                    </div>
                                    <x-badge :tone="$stage->status === 'completed' ? 'success' : ($stage->status === 'ongoing' ? 'warning' : 'neutral')">{{ $stage->statusLabel() }}</x-badge>
                                </div>
                                <p class="mt-1 text-sm font-semibold" style="color: var(--phase)">{{ $stage->month_label }}</p>
                                <p class="mt-3 text-sm leading-relaxed text-navy-600">{{ $stage->description }}</p>
                                @if ($stage->activityList())
                                    <ul class="mt-4 space-y-1.5 text-sm text-navy-700">
                                        @foreach ($stage->activityList() as $activity)
                                            <li class="flex gap-2"><x-icon name="check" class="mt-0.5 size-4 shrink-0" style="color: var(--phase)" stroke="2.25" />{{ $activity }}</li>
                                        @endforeach
                                    </ul>
                                @endif
                                @if ($stage->output)
                                    <p class="mt-5 rounded-xl bg-navy-50 px-4 py-3 text-sm"><span class="font-bold">Luaran:</span> {{ $stage->output }}</p>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </div>
            @endforeach

            @foreach ($crossCutting as $stage)
                <div class="rounded-3xl border border-navy-100 bg-white p-6 sm:p-8">
                    <p class="eyebrow text-forest-600">Lintas fase · {{ $stage->month_label }}</p>
                    <h2 class="mt-2 text-xl font-extrabold">{{ $stage->name }}</h2>
                    <p class="mt-2 text-navy-600">{{ $stage->description }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
