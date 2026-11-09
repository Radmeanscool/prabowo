<x-layouts.portal title="Dashboard">
    <x-page-header eyebrow="Dashboard Peserta" :title="'Halo, '.$user->name" subtitle="Pantau langkah pendaftaran, kelengkapan proposal, dan status pengajuan tim Anda." />

    <div class="mb-6 flex flex-col gap-3 rounded-2xl border px-5 py-4 sm:flex-row sm:items-center sm:justify-between {{ $openCall['open'] ? 'border-forest-200 bg-forest-50' : 'border-signal-200 bg-signal-50' }}">
        <div class="flex items-start gap-3">
            <x-icon :name="$openCall['open'] ? 'check-circle' : 'clock'" class="mt-0.5 size-5 {{ $openCall['open'] ? 'text-forest-600' : 'text-signal-600' }}" />
            <div>
                <p class="font-bold text-navy-950">{{ $openCall['label'] }}</p>
                <p class="text-sm text-navy-600">
                    @if ($openCall['start'] && $openCall['end'])
                        {{ fmt_date($openCall['start'], true) }} – {{ fmt_date($openCall['end'], true) }}
                    @else
                        Anda tetap dapat menyiapkan tim dan menyimpan draf proposal.
                    @endif
                </p>
            </div>
        </div>
        <a href="{{ route('timeline') }}" class="btn btn-outline btn-sm shrink-0">Lihat linimasa</a>
    </div>

    @php
        $steps = [
            ['Profil lengkap', filled($user->institution) && filled($user->discipline), route('portal.profile')],
            ['Tim terbentuk', (bool) $team, route('portal.team')],
            ['Tema & tantangan dipilih', $proposal && $proposal->theme_id, $proposal ? route('portal.shortcut.challenge') : route('portal.proposals.create')],
            ['Proposal lengkap', $proposal && $percent === 100, $proposal ? route('portal.proposals.edit', $proposal) : route('portal.proposals.create')],
            ['Proposal dikirim', $proposal && $proposal->version > 0, $proposal ? route('portal.proposals.status', $proposal) : route('portal.proposals.create')],
        ];
    @endphp
    <ol class="mb-8 grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
        @foreach ($steps as $i => [$label, $done, $href])
            <li>
                <a href="{{ $href }}" class="card card-link flex items-center gap-3 p-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold {{ $done ? 'bg-forest-600 text-white' : 'bg-navy-100 text-navy-600' }}">
                        @if ($done)<x-icon name="check" class="size-4" stroke="2.5" />@else{{ $i + 1 }}@endif
                    </span>
                    <span class="text-sm font-semibold {{ $done ? 'text-navy-900' : 'text-navy-600' }}">{{ $label }}</span>
                </a>
            </li>
        @endforeach
    </ol>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            @if (! $team)
                <div class="card card-body">
                    <h2 class="card-title">Mulai dengan tim</h2>
                    <p class="mt-1 text-sm text-navy-600">DEEPNEST diikuti tim lintas disiplin. Buat tim baru sebagai ketua, atau masukkan kode tim dari ketua Anda.</p>
                    @if ($pending)
                        <p class="mt-4 rounded-xl bg-signal-50 px-4 py-3 text-sm text-signal-700">Permintaan bergabung ke <strong>{{ $pending->team->name }}</strong> sedang menunggu persetujuan ketua tim.</p>
                    @endif
                    <a href="{{ route('portal.team') }}" class="btn btn-dark mt-4">Kelola Tim</a>
                </div>
            @elseif (! $proposal)
                <div class="card card-body">
                    <h2 class="card-title">Susun proposal tim {{ $team->name }}</h2>
                    <p class="mt-1 text-sm text-navy-600">Pilih tema, lihat tantangan terkait, lalu pilih tantangan yang ingin dijawab tim Anda.</p>
                    <a href="{{ route('portal.proposals.create') }}" class="btn btn-primary mt-4">Pilih Tema & Tantangan <x-icon name="arrow-right" class="size-4" /></a>
                </div>
            @else
                <div class="card">
                    <div class="card-body border-b border-navy-100">
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="eyebrow text-navy-400">Proposal tim</p>
                                <h2 class="mt-1 text-xl font-extrabold">{{ $proposal->product_name }}</h2>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <x-status-badge :status="$proposal->status" />
                                    <x-theme-badge :theme="$proposal->theme" />
                                    @if ($proposal->challenge)<x-badge>{{ $proposal->challenge->title }}</x-badge>@endif
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                @if ($proposal->isEditableByParticipant())
                                    <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-dark btn-sm"><x-icon name="pencil" class="size-4" /> Lanjutkan Mengisi</a>
                                @else
                                    <a href="{{ route('portal.proposals.show', $proposal) }}" class="btn btn-outline btn-sm"><x-icon name="eye" class="size-4" /> Lihat Proposal</a>
                                @endif
                                <a href="{{ route('portal.proposals.status', $proposal) }}" class="btn btn-outline btn-sm">Status</a>
                            </div>
                        </div>
                        <div class="mt-5">
                            <div class="flex items-center justify-between text-sm"><span class="font-semibold text-navy-700">Kelengkapan</span><span class="font-bold tabular-nums">{{ $percent }}%</span></div>
                            <div class="progress mt-2"><span style="width: {{ $percent }}%"></span></div>
                        </div>
                    </div>
                    <ul class="grid gap-x-6 gap-y-2.5 p-5 sm:grid-cols-2 sm:p-6">
                        @foreach ($checklist as $item)
                            <li class="flex items-start gap-2.5 text-sm">
                                <x-icon :name="$item['done'] ? 'check-circle' : 'circle'" class="mt-0.5 size-4 {{ $item['done'] ? 'text-forest-600' : 'text-navy-300' }}" />
                                <span class="{{ $item['done'] ? 'text-navy-800' : 'text-navy-500' }}">{{ $item['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if ($proposal->status->value === 'revision_requested' && $proposal->decision_note)
                        <div class="mx-5 mb-5 rounded-xl border border-signal-200 bg-signal-50 p-4 text-sm text-signal-700 sm:mx-6 sm:mb-6">
                            <p class="font-bold">Catatan revisi dari panitia</p>
                            <p class="mt-1 whitespace-pre-line text-navy-800">{{ $proposal->decision_note }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if ($team)
                <div class="card card-body">
                    <div class="flex items-center justify-between">
                        <h2 class="card-title">{{ $team->name }}</h2>
                        <a href="{{ route('portal.team') }}" class="text-sm font-semibold text-forest-700">Kelola</a>
                    </div>
                    <p class="mt-1 text-sm text-navy-500">{{ $team->institution }}</p>
                    <ul class="mt-4 space-y-2">
                        @foreach ($team->members->where('status', 'active') as $member)
                            <li class="flex items-center gap-3 text-sm">
                                <span class="flex size-8 items-center justify-center rounded-full bg-navy-100 text-xs font-bold text-navy-700">{{ mb_strtoupper(mb_substr($member->name, 0, 1)) }}</span>
                                <span class="min-w-0 flex-1 truncate"><span class="font-semibold">{{ $member->name }}</span> <span class="text-navy-400">· {{ $member->discipline }}</span></span>
                                @if ($member->isLeader())<x-badge tone="dark">Ketua</x-badge>@endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card card-body">
                <div class="flex items-center justify-between">
                    <h2 class="card-title">Pengumuman</h2>
                    <a href="{{ route('portal.announcements') }}" class="text-sm font-semibold text-forest-700">Semua</a>
                </div>
                @forelse ($announcements as $announcement)
                    <div class="mt-4 border-t border-navy-100 pt-4 first-of-type:border-0 first-of-type:pt-0">
                        <p class="text-xs text-navy-400">{{ fmt_date($announcement->published_at ?? $announcement->created_at) }}</p>
                        <a href="{{ route('announcements.show', $announcement->slug) }}" class="mt-0.5 block font-semibold text-navy-900 hover:text-forest-700">{{ $announcement->title }}</a>
                    </div>
                @empty
                    <p class="mt-3 text-sm text-navy-500">Belum ada pengumuman.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.portal>
