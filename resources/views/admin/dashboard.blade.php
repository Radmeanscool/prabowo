<x-layouts.portal title="Dashboard">
    <x-page-header eyebrow="Dashboard Admin" title="Ringkasan program" subtitle="Pantau pendaftaran, proposal per tema, beban kerja reviewer, dan kemajuan tahapan.">
        <x-slot:actions>
            <span class="badge {{ $openCall['open'] ? 'badge-success' : 'badge-warning' }} py-1.5">{{ $openCall['label'] }}</span>
            <a href="{{ route('admin.settings.edit') }}" class="btn btn-outline btn-sm">Atur Open Call</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat label="Peserta terdaftar" :value="$metrics['users']" icon="user" :href="route('admin.users.index', ['role' => 'participant'])" />
        <x-stat label="Tim" :value="$metrics['teams']" icon="users" :href="route('admin.teams.index')" />
        <x-stat label="Draf proposal" :value="$metrics['draft']" icon="pencil" :href="route('admin.proposals.index', ['status' => 'draft'])" />
        <x-stat label="Proposal terkirim" :value="$metrics['submitted']" icon="file" :href="route('admin.proposals.index')" />
        <x-stat label="Dalam penilaian" :value="$metrics['under_review']" icon="clipboard" :href="route('admin.proposals.index', ['status' => 'under_review'])" />
        <x-stat label="Disetujui" :value="$metrics['approved']" icon="check-circle" :href="route('admin.products.index')" />
        <x-stat label="Tampil publik" :value="$metrics['public']" icon="globe" :href="route('admin.products.index', ['visibility' => 'public'])" />
        <x-stat label="Belum ditugaskan" :value="$metrics['unassigned']" icon="alert" :href="route('admin.proposals.index', ['assignment' => 'none'])" />
        <x-stat label="Konflik dinyatakan" :value="$metrics['conflicts']" icon="shield" :href="route('admin.assignments.index', ['state' => 'conflict'])" />
        <x-stat label="Berkas wajib kurang" :value="$metrics['missing_docs']" icon="upload" :href="route('admin.proposals.index', ['completeness' => 'missing_files'])" />
    </div>

    <div class="mt-8 grid gap-6 xl:grid-cols-3">
        <div class="card card-body">
            <h2 class="card-title">Proposal per tema</h2>
            <ul class="mt-4 space-y-4">
                @php $maxTheme = max(1, $themes->max('submitted_count')); @endphp
                @foreach ($themes as $theme)
                    <li>
                        <div class="flex items-center justify-between text-sm">
                            <x-theme-badge :theme="$theme" />
                            <span class="tabular-nums text-navy-600"><strong class="text-navy-950">{{ $theme->submitted_count }}</strong> terkirim · {{ $theme->draft_count }} draf</span>
                        </div>
                        <div class="progress mt-2"><span style="width: {{ round($theme->submitted_count / $maxTheme * 100) }}%; background: {{ $theme->color }}"></span></div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card card-body">
            <h2 class="card-title">Proposal per mitra</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($byOwner as $row)
                    <li class="flex justify-between gap-3"><span class="truncate">{{ $row->name }}</span><span class="font-bold tabular-nums">{{ $row->total }}</span></li>
                @empty
                    <li class="text-navy-500">Belum ada proposal terkirim pada tantangan bermitra.</li>
                @endforelse
            </ul>
        </div>

        <div class="card card-body">
            <h2 class="card-title">Rata-rata skor per fase</h2>
            <ul class="mt-4 space-y-3 text-sm">
                @foreach ($averages as $row)
                    <li class="flex items-center justify-between gap-3">
                        <span>{{ $row['phase']->name }}</span>
                        <span class="text-right"><strong class="text-lg tabular-nums">{{ $row['avg'] !== null ? number_format((float) $row['avg'], 2, ',', '.') : '—' }}</strong> <span class="text-xs text-navy-500">({{ $row['count'] }} penilaian)</span></span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="card-body flex items-center justify-between border-b border-navy-100">
                <h2 class="card-title">Beban kerja reviewer</h2>
                <a href="{{ route('admin.assignments.index') }}" class="text-sm font-semibold text-forest-700">Kelola penugasan</a>
            </div>
            <div class="table-wrap rounded-none border-0">
                <table class="table">
                    <thead><tr><th>Reviewer</th><th>Terbuka</th><th>Selesai</th><th>Konflik</th></tr></thead>
                    <tbody>
                        @forelse ($workload as $reviewer)
                            <tr><td class="font-medium">{{ $reviewer->name }}<p class="text-xs text-navy-500">{{ $reviewer->institution }}</p></td><td class="tabular-nums">{{ $reviewer->open_count }}</td><td class="tabular-nums">{{ $reviewer->done_count }}</td><td class="tabular-nums">{{ $reviewer->conflict_count }}</td></tr>
                        @empty
                            <tr><td colspan="4" class="text-navy-500">Belum ada akun reviewer. <a class="font-semibold text-forest-700" href="{{ route('admin.users.create') }}">Buat akun reviewer</a>.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-body border-b border-navy-100"><h2 class="card-title">Kemajuan tahapan (stage-gate)</h2></div>
            <ul class="divide-y divide-navy-100">
                @foreach ($stages as $stage)
                    <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm sm:px-6">
                        <span class="flex items-center gap-2"><span class="size-2.5 rounded-full {{ $stage->status === 'completed' ? 'bg-forest-500' : ($stage->status === 'ongoing' ? 'bg-signal-500' : 'bg-navy-200') }}"></span>{{ $stage->name }} <span class="text-xs text-navy-400">{{ $stage->month_label }}</span></span>
                        <span class="tabular-nums text-navy-600">{{ $stage->proposals_count }} proposal</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="card mt-6">
        <div class="card-body flex items-center justify-between border-b border-navy-100">
            <h2 class="card-title">Pengiriman terbaru</h2>
            <a href="{{ route('admin.proposals.index') }}" class="text-sm font-semibold text-forest-700">Semua proposal</a>
        </div>
        <ul class="divide-y divide-navy-100">
            @forelse ($recent as $proposal)
                <li class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 sm:px-6">
                    <div class="min-w-0">
                        <a href="{{ route('admin.proposals.show', $proposal) }}" class="font-semibold text-navy-950 hover:text-forest-700">{{ $proposal->product_name }}</a>
                        <p class="text-xs text-navy-500">{{ $proposal->team?->name }} · {{ fmt_date($proposal->submitted_at, true) }}</p>
                    </div>
                    <div class="flex gap-2"><x-theme-badge :theme="$proposal->theme" /><x-status-badge :status="$proposal->status" /></div>
                </li>
            @empty
                <li class="px-6 py-6 text-sm text-navy-500">Belum ada proposal yang dikirim.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.portal>
