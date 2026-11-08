<x-layouts.public title="Linimasa">
    <x-page-hero title="Linimasa program" eyebrow="Linimasa" lead="Waktu tahapan dinyatakan dalam bulan program. Tanggal pasti ditampilkan setelah ditetapkan panitia." />

    <section class="section">
        <div class="container-page space-y-12">
            <div class="flex flex-col gap-3 rounded-2xl border px-5 py-4 sm:flex-row sm:items-center sm:justify-between {{ $openCall['open'] ? 'border-forest-200 bg-forest-50' : 'border-signal-200 bg-signal-50' }}">
                <p class="font-bold">{{ $openCall['label'] }}@if ($openCall['start'] && $openCall['end']) · {{ fmt_date($openCall['start'], true) }} – {{ fmt_date($openCall['end'], true) }}@endif</p>
                <a href="{{ route('register') }}" class="btn btn-dark btn-sm">Daftar Sekarang</a>
            </div>

            <div class="card overflow-hidden">
                <div class="border-b border-navy-100 px-6 py-4"><h2 class="text-lg font-extrabold">Jadwal per bulan program</h2></div>
                <div class="overflow-x-auto">
                    <div class="min-w-[760px] p-6">
                        <div class="grid grid-cols-[220px_repeat(12,minmax(0,1fr))] gap-y-3 text-xs">
                            <div></div>
                            @for ($m = 1; $m <= 12; $m++)
                                <div class="text-center font-bold text-navy-400">{{ $m }}</div>
                            @endfor
                            @foreach ($stages as $stage)
                                @php
                                    $start = max(1, (int) ($stage->start_month ?: 1));
                                    $end = min(12, max($start, (int) ($stage->end_month ?: $start)));
                                @endphp
                                <div class="pr-3 text-sm font-semibold text-navy-800">{{ $stage->name }}</div>
                                <div class="{{ $stage->phaseTone() }} relative col-span-12 grid grid-cols-12 rounded-lg bg-navy-50">
                                    <span class="h-7 rounded-lg" style="grid-column: {{ $start }} / {{ $end + 1 }}; background: var(--phase); opacity: {{ $stage->status === 'upcoming' ? '0.55' : '1' }}" title="{{ $stage->month_label }}"></span>
                                </div>
                            @endforeach
                        </div>
                        <p class="mt-4 text-xs text-navy-500">Bootcamp, Mentoring Sprint, dan Demo Day berlangsung tiga hari pada bulan ke-5. Bilah pudar = tahap yang akan datang.</p>
                    </div>
                </div>
            </div>

            <div>
                <h2 class="text-2xl font-extrabold">Tanggal penting</h2>
                <ol class="relative mt-6 space-y-6 border-l-2 border-navy-100 pl-6">
                    @forelse ($milestones as $item)
                        <li class="relative">
                            <span class="absolute -left-[1.95rem] top-1.5 size-4 rounded-full border-4 border-white {{ $item->status === 'completed' ? 'bg-forest-500' : ($item->status === 'confirmed' ? 'bg-signal-500' : 'bg-navy-300') }} ring-2 ring-navy-100"></span>
                            <p class="text-sm font-bold text-navy-500">{{ $item->dateText() }}@if ($item->timeText()) · {{ $item->timeText() }}@endif</p>
                            <p class="mt-0.5 text-lg font-bold">{{ $item->title }}</p>
                            <p class="mt-1 flex flex-wrap items-center gap-2 text-sm text-navy-600">
                                <x-badge :tone="$item->status === 'completed' ? 'success' : ($item->status === 'confirmed' ? 'warning' : 'neutral')">{{ $item->statusLabel() }}</x-badge>
                                @if ($item->stage)<span>{{ $item->stage->name }}</span>@endif
                                @if ($item->location)<span>· {{ $item->location }}</span>@endif
                            </p>
                        </li>
                    @empty
                        <li class="text-sm text-navy-500">Tanggal penting akan diumumkan.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </section>
</x-layouts.public>
