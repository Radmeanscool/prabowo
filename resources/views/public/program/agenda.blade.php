<x-layouts.public title="Agenda">
    <x-page-hero title="Agenda kegiatan" eyebrow="Agenda" lead="Kegiatan dikelompokkan per tahapan, tanggal, dan sesi. Jadwal berstatus rencana dapat berubah." />
    <section class="section">
        <div class="container-page space-y-12">
            @forelse ($groups as $stageName => $items)
                @php $stage = $stages[$stageName] ?? null; @endphp
                <div class="{{ $stage?->phaseTone() ?? 'phase-0' }}">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="h-8 w-1.5 rounded-full" style="background: var(--phase)"></span>
                        <h2 class="text-2xl font-extrabold">{{ $stageName }}</h2>
                        @if ($stage?->month_label)<span class="text-sm font-semibold text-navy-500">{{ $stage->month_label }}</span>@endif
                    </div>
                    <div class="mt-5 space-y-4">
                        @foreach ($items->groupBy(fn ($i) => $i->dateText()) as $date => $sessions)
                            <div class="card overflow-hidden">
                                <div class="flex items-center gap-3 border-b border-navy-100 bg-navy-50/60 px-6 py-3">
                                    <x-icon name="calendar" class="size-4 text-navy-500" />
                                    <p class="font-bold">{{ $date }}</p>
                                </div>
                                <ul class="divide-y divide-navy-100">
                                    @foreach ($sessions as $session)
                                        <li class="grid gap-3 px-6 py-5 md:grid-cols-[180px_1fr_auto]">
                                            <p class="text-sm font-semibold text-navy-500">{{ $session->timeText() ?? 'Waktu menyusul' }}</p>
                                            <div>
                                                <p class="font-bold text-navy-950">{{ $session->title }}</p>
                                                @if ($session->description)<p class="mt-1 text-sm leading-relaxed text-navy-600">{{ $session->description }}</p>@endif
                                                @if ($session->speakers)<p class="mt-1 text-sm text-navy-500"><span class="font-semibold">Narasumber:</span> {{ $session->speakers }}</p>@endif
                                                @if ($session->location)<p class="mt-1 flex items-center gap-1.5 text-sm text-navy-500"><x-icon name="map-pin" class="size-4" /> {{ $session->location }}</p>@endif
                                            </div>
                                            <div><x-badge :tone="$session->status === 'completed' ? 'success' : ($session->status === 'confirmed' ? 'warning' : 'neutral')">{{ $session->statusLabel() }}</x-badge></div>
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
