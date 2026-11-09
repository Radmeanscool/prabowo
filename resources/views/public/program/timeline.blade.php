<x-layouts.public title="Linimasa">
    <x-page-hero title="Linimasa program" eyebrow="Linimasa" lead="Waktu tahapan dinyatakan dalam bulan program. Tanggal pasti ditampilkan setelah ditetapkan panitia." />

    <section class="section pt-12 sm:pt-14">
        <div class="container-page space-y-20">
            <div class="grid gap-3 border-y border-navy-950 py-4 sm:grid-cols-[10rem_1fr_auto] sm:items-center sm:gap-6">
                <p class="eyebrow text-navy-500">Open Call</p>
                <p class="font-medium {{ $openCall['open'] ? 'text-forest-700' : 'text-navy-950' }}">{{ $openCall['label'] }}@if ($openCall['start'] && $openCall['end']) · {{ fmt_date($openCall['start'], true) }} – {{ fmt_date($openCall['end'], true) }}@endif</p>
                @if (setting('registration_enabled', true))
                    <a href="{{ route('register') }}" class="btn btn-primary btn-sm justify-self-start">Daftar Sekarang</a>
                @endif
            </div>

            <div>
                <div class="border-b border-navy-950 pb-3"><h2 class="subhead">Jadwal per bulan program</h2></div>
                <div class="overflow-x-auto">
                    <div class="min-w-[760px] py-6">
                        <div class="grid grid-cols-[220px_repeat(12,minmax(0,1fr))] gap-y-2.5 text-xs">
                            <div></div>
                            @for ($m = 1; $m <= 12; $m++)
                                <div class="pb-1 text-center tabular-nums text-navy-500">{{ $m }}</div>
                            @endfor
                            @foreach ($stages as $stage)
                                @php
                                    $start = max(1, (int) ($stage->start_month ?: 1));
                                    $end = min(12, max($start, (int) ($stage->end_month ?: $start)));
                                @endphp
                                <div class="pr-3 text-sm text-navy-800">{{ $stage->name }}</div>
                                <div class="{{ $stage->phaseTone() }} relative col-span-12 grid grid-cols-12 bg-navy-100/70">
                                    <span class="h-6" style="grid-column: {{ $start }} / {{ $end + 1 }}; background: var(--phase); opacity: {{ $stage->status === 'upcoming' ? '0.45' : '1' }}" title="{{ $stage->month_label }}"></span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-5 text-xs text-navy-500">Bootcamp, Mentoring Sprint, dan Demo Day direncanakan berlangsung tiga hari pada bulan ke-5. Bilah pudar menandai tahap yang akan datang.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-12 lg:gap-10">
                <h2 class="eyebrow pt-4 text-navy-500 lg:col-span-3">Tanggal penting</h2>
                <ol class="border-t border-navy-950 lg:col-span-9">
                    @forelse ($milestones as $item)
                        <li class="grid gap-2 border-b border-navy-950/15 py-5 md:grid-cols-[11rem_1fr] md:gap-8">
                            <p class="text-sm text-navy-500">{{ $item->dateText() }}@if ($item->timeText())<span class="block">{{ $item->timeText() }}</span>@endif</p>
                            <div>
                                <p class="font-serif text-[1.45rem] leading-snug text-navy-950">{{ $item->title }}</p>
                                <p class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-navy-600">
                                    <x-badge :tone="$item->status === 'completed' ? 'success' : ($item->status === 'confirmed' ? 'warning' : 'neutral')">{{ $item->statusLabel() }}</x-badge>
                                    @if ($item->stage)<span>{{ $item->stage->name }}</span>@endif
                                    @if ($item->location)<span>· {{ $item->location }}</span>@endif
                                </p>
                            </div>
                        </li>
                    @empty
                        <li class="py-5 text-navy-500">Tanggal penting akan diumumkan.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </section>
</x-layouts.public>
