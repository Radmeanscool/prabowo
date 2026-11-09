<x-layouts.public title="Agenda">
    <x-page-hero title="Agenda kegiatan" eyebrow="Agenda" lead="Kegiatan dikelompokkan per tahapan dan tanggal. Jadwal berstatus rencana dapat berubah." />
    <section class="section pt-14 sm:pt-16">
        <div class="container-page space-y-16">
            @forelse ($groups as $stageName => $items)
                @php $stage = $stages[$stageName] ?? null; @endphp
                <div class="{{ $stage?->phaseTone() ?? 'phase-0' }} grid gap-4 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-3">
                        <h2 class="flex items-center gap-2 pt-3 font-semibold text-navy-950"><span class="size-2 shrink-0" style="background: var(--phase)" aria-hidden="true"></span>{{ $stageName }}</h2>
                        @if ($stage?->month_label)<p class="mt-1 text-sm text-navy-500">{{ $stage->month_label }}</p>@endif
                    </div>
                    <div class="border-t border-navy-950 lg:col-span-9">
                        @foreach ($items->groupBy(fn ($i) => $i->dateText()) as $date => $sessions)
                            <div class="grid gap-4 border-b border-navy-950/15 py-6 md:grid-cols-[11rem_1fr] md:gap-8">
                                <p class="font-serif text-2xl leading-tight text-navy-950">{{ $date }}</p>
                                <ul class="space-y-6">
                                    @foreach ($sessions as $session)
                                        <li>
                                            <p class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-navy-500">
                                                <span>{{ $session->timeText() ?? 'Waktu menyusul' }}</span>
                                                <x-badge :tone="$session->status === 'completed' ? 'success' : ($session->status === 'confirmed' ? 'warning' : 'neutral')">{{ $session->statusLabel() }}</x-badge>
                                            </p>
                                            <p class="mt-1.5 text-lg font-semibold leading-snug text-navy-950">{{ $session->title }}</p>
                                            @if ($session->description)<p class="mt-1 max-w-2xl text-[0.95rem] leading-relaxed text-navy-600">{{ $session->description }}</p>@endif
                                            @if ($session->speakers)<p class="mt-1 text-sm text-navy-500">Narasumber: {{ $session->speakers }}</p>@endif
                                            @if ($session->location)<p class="mt-1 text-sm text-navy-500">{{ $session->location }}</p>@endif
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <x-empty icon="calendar" title="Agenda akan diumumkan" />
            @endforelse
        </div>
    </section>
</x-layouts.public>
