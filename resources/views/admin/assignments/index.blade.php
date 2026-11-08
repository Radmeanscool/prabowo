<x-layouts.portal title="Penugasan Reviewer">
    <x-page-header title="Penugasan reviewer" subtitle="Admin yang menghubungkan proposal dengan reviewer. Reviewer hanya melihat proposal yang ditugaskan.">
        <x-slot:actions>
            <x-badge :tone="$selfSelection ? 'warning' : 'neutral'">Pemilihan mandiri reviewer: {{ $selfSelection ? 'aktif' : 'nonaktif' }}</x-badge>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-5 flex flex-wrap gap-2">
        <x-form.select name="phase_id" :options="$phases->pluck('name', 'id')->all()" :value="$phase?->id" placeholder="Pilih fase" data-autosubmit class="w-64" aria-label="Fase" />
        <x-form.select name="theme_id" :options="$themes" :value="request('theme_id')" placeholder="Semua tema" data-autosubmit class="w-48" aria-label="Tema" />
        <x-form.select name="state" :options="['unassigned' => 'Belum ada reviewer di fase ini', 'conflict' => 'Ada konflik dinyatakan']" :value="request('state')" placeholder="Semua proposal" data-autosubmit class="w-72" aria-label="Keadaan" />
    </form>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_340px]">
        <form method="POST" action="{{ route('admin.assignments.bulk') }}" id="bulk-form" class="min-w-0 space-y-4">
            @csrf
            <input type="hidden" name="review_phase_id" value="{{ $phase?->id }}">
            <div class="table-wrap">
                <table class="table">
                    <thead><tr>
                        <th><input type="checkbox" class="form-check" data-check-all="proposal_ids[]" aria-label="Pilih semua"></th>
                        <th>Proposal</th><th>Tema</th><th>Status</th><th>Reviewer fase ini</th>
                    </tr></thead>
                    <tbody>
                        @forelse ($proposals as $proposal)
                            <tr>
                                <td><input type="checkbox" name="proposal_ids[]" value="{{ $proposal->id }}" class="form-check" aria-label="Pilih {{ $proposal->product_name }}"></td>
                                <td class="min-w-56"><a href="{{ route('admin.proposals.show', $proposal) }}" class="font-semibold text-navy-950 hover:text-forest-700">{{ $proposal->product_name }}</a><p class="text-xs text-navy-500">{{ $proposal->team?->name }} · {{ $proposal->challenge?->title }}</p></td>
                                <td><x-theme-badge :theme="$proposal->theme" /></td>
                                <td><x-status-badge :status="$proposal->status" /></td>
                                <td class="min-w-56">
                                    @forelse ($proposal->assignments as $assignment)
                                        <p class="text-sm">
                                            <span class="font-medium">{{ $assignment->reviewer?->name }}</span>
                                            <x-badge :tone="match ($assignment->status) { 'submitted' => 'success', 'conflict_declared' => 'danger', 'in_progress' => 'warning', default => 'info' }">{{ $assignment->statusLabel() }}</x-badge>
                                        </p>
                                    @empty
                                        <span class="text-sm text-red-600">Belum ada</span>
                                    @endforelse
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-8 text-center text-navy-500">Tidak ada proposal yang dapat ditugaskan.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $proposals->links() }}
        </form>

        <div class="space-y-6">
            <div class="card card-body space-y-4">
                <h2 class="card-title">Tugaskan massal</h2>
                <p class="text-sm text-navy-500">Centang proposal di tabel, pilih reviewer, lalu tugaskan. Pasangan dengan konflik kepentingan berat otomatis dilewati.</p>
                <fieldset>
                    <legend class="form-label">Reviewer</legend>
                    <div class="max-h-72 space-y-2 overflow-y-auto rounded-xl border border-navy-100 p-3">
                        @forelse ($reviewers as $reviewer)
                            <label class="flex items-start gap-2 text-sm">
                                <input type="checkbox" name="reviewer_ids[]" value="{{ $reviewer->id }}" form="bulk-form" class="form-check mt-0.5">
                                <span><span class="font-semibold">{{ $reviewer->name }}</span><span class="block text-xs text-navy-500">{{ $reviewer->institution ?: '—' }} · {{ $reviewer->open_count }} terbuka · {{ $reviewer->phase_count }} di fase ini{{ $reviewer->person ? ' · profil '.$reviewer->person->typeLabel() : '' }}</span></span>
                            </label>
                        @empty
                            <p class="text-sm text-navy-500">Belum ada reviewer aktif. <a href="{{ route('admin.users.create') }}" class="font-semibold text-forest-700">Buat akun reviewer</a>.</p>
                        @endforelse
                    </div>
                </fieldset>
                <x-form.input name="due_at" type="datetime-local" label="Batas penilaian" form="bulk-form" :help="$phase?->ends_at ? 'Kosongkan untuk memakai batas fase: '.fmt_date($phase->ends_at, true) : null" />
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirm_warnings" value="1" form="bulk-form" class="form-check mt-0.5"> Saya sudah meninjau peringatan (mis. institusi sama) dan tetap menugaskan</label>
                <button type="submit" form="bulk-form" class="btn btn-dark w-full" @disabled(! $phase)>Tugaskan ke {{ $phase?->name ?? 'fase' }}</button>
            </div>

            <div class="card">
                <div class="card-body border-b border-navy-100"><h2 class="card-title">Beban kerja</h2></div>
                <ul class="divide-y divide-navy-100">
                    @foreach ($reviewers->sortByDesc('open_count') as $reviewer)
                        <li class="flex items-center justify-between gap-3 px-5 py-2.5 text-sm">
                            <span class="truncate">{{ $reviewer->name }}</span>
                            <span class="tabular-nums text-navy-600"><strong class="text-navy-950">{{ $reviewer->open_count }}</strong> terbuka · {{ $reviewer->done_count }} selesai</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</x-layouts.portal>
