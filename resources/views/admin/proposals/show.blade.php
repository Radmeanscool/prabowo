<x-layouts.portal :title="$proposal->product_name">
    @php
        $canApprove = in_array(\App\Enums\ProposalStatus::Approved, $transitions, true);
        $otherTransitions = collect($transitions)->reject(fn ($s) => $s === \App\Enums\ProposalStatus::Approved);
    @endphp

    <x-page-header eyebrow="Proposal #{{ $proposal->id }}" :title="$proposal->product_name">
        <x-slot:actions>
            <x-status-badge :status="$proposal->status" class="text-sm" />
            @if ($proposal->is_public)<a href="{{ route('products.show', $proposal->public_slug) }}" target="_blank" class="btn btn-outline btn-sm"><x-icon name="globe" class="size-4" /> Halaman publik</a>@endif
            <a href="{{ route('admin.proposals.index') }}" class="btn btn-ghost btn-sm"><x-icon name="arrow-left" class="size-4" /> Kembali</a>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 flex flex-wrap gap-2 text-sm">
        <x-theme-badge :theme="$proposal->theme" />
        @if ($proposal->challenge)<x-badge>{{ $proposal->challenge->title }}</x-badge>@endif
        <x-badge>Tim: {{ $proposal->team->name }}</x-badge>
        <x-badge>Versi {{ $proposal->version }}</x-badge>
        @if ($proposal->submitted_at)<x-badge>Dikirim {{ fmt_date($proposal->submitted_at, true) }}</x-badge>@endif
        @if ($proposal->programStage)<x-badge tone="teal">Tahap: {{ $proposal->programStage->name }}</x-badge>@endif
    </div>

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_420px]">
        <div class="order-2 min-w-0 space-y-6 xl:order-1">
            {{-- Penilaian --}}
            <section class="card">
                <div class="card-body border-b border-navy-100">
                    <h2 class="card-title">Ringkasan penilaian</h2>
                    <p class="text-xs text-navy-500">Skor dan catatan reviewer hanya untuk admin; tidak ditampilkan ke peserta kecuali diaktifkan di Pengaturan.</p>
                </div>
                @forelse ($reviewSummary as $row)
                    <div class="border-b border-navy-100 p-5 last:border-0 sm:px-6">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="font-bold">{{ $row['phase']->name }}</p>
                            <p class="text-sm">Rata-rata <span class="text-xl font-extrabold tabular-nums">{{ number_format((float) $row['reviews']->avg('weighted_score'), 2, ',', '.') }}</span> dari {{ $row['reviews']->count() }} penilaian</p>
                        </div>
                        <ul class="mt-3 space-y-2">
                            @foreach ($row['reviews'] as $review)
                                <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-navy-100 px-4 py-2.5 text-sm">
                                    <span><span class="font-semibold">{{ $review->reviewer?->name }}</span> · {{ $review->recommendationLabel() }}</span>
                                    <span class="flex items-center gap-3"><span class="font-bold tabular-nums">{{ number_format((float) $review->weighted_score, 2, ',', '.') }}</span><a href="{{ route('admin.reviews.show', $review) }}" class="text-xs font-semibold text-forest-700">Detail</a></span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <p class="p-6 text-sm text-navy-500">Belum ada penilaian final.</p>
                @endforelse
            </section>

            {{-- Berkas --}}
            <section class="card card-body">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="card-title">Berkas</h2>
                    <form method="POST" action="{{ route('admin.proposals.internal-files', $proposal) }}" enctype="multipart/form-data" class="flex flex-wrap items-center gap-2">
                        @csrf
                        <label class="sr-only" for="internal_file">Berkas internal</label>
                        <input type="file" name="file" id="internal_file" accept="application/pdf" required class="file-input-compact">
                        <button class="btn btn-outline btn-sm"><x-icon name="lock" class="size-3.5" /> Unggah telaah internal</button>
                    </form>
                </div>
                <p class="mt-1 text-xs text-navy-500">Telaah internal (mis. hasil visitasi legal) bersifat rahasia: hanya admin yang dapat membukanya, tidak pernah tampil ke peserta, reviewer, atau publik.</p>
                <ul class="mt-4 divide-y divide-navy-100 rounded-xl border border-navy-100">
                    @forelse ($proposal->files as $file)
                        <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                            <span class="min-w-0">
                                <span class="block font-semibold">{{ $file->typeLabel() }} @if ($file->is_internal)<x-badge tone="danger">Internal</x-badge>@endif</span>
                                <span class="block truncate text-xs text-navy-500">{{ $file->original_name }} · {{ format_bytes($file->size) }} · {{ $file->uploader?->name }} · {{ fmt_date($file->created_at, true) }}</span>
                            </span>
                            <a href="{{ route('files.show', $file->uuid) }}" target="_blank" class="btn btn-outline btn-sm">Buka</a>
                        </li>
                    @empty
                        <li class="px-4 py-3 text-sm text-navy-500">Belum ada berkas.</li>
                    @endforelse
                </ul>
            </section>

            <details class="card card-body" open>
                <summary class="cursor-pointer text-lg font-extrabold">Isi proposal</summary>
                <div class="mt-5">
                    @include('proposals.content', ['proposal' => $proposal, 'viewer' => 'admin'])
                </div>
            </details>

            {{-- Riwayat --}}
            <section class="grid gap-6 lg:grid-cols-2">
                <div class="card card-body">
                    <h2 class="card-title">Riwayat status</h2>
                    <ol class="mt-4 space-y-3">
                        @foreach ($proposal->statusHistories as $history)
                            <li class="text-sm">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($history->from_status)<x-status-badge :status="$history->from_status" /> <x-icon name="arrow-right" class="size-3 text-navy-400" />@endif
                                    <x-status-badge :status="$history->to_status" />
                                </div>
                                <p class="mt-1 text-xs text-navy-500">{{ fmt_date($history->created_at, true) }} · {{ $history->changer?->name ?? 'Sistem' }}</p>
                                @if ($history->note)<p class="mt-1 whitespace-pre-line text-navy-700">{{ $history->note }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                </div>
                <div class="card card-body">
                    <h2 class="card-title">Log audit proposal</h2>
                    <ul class="mt-4 max-h-96 space-y-2.5 overflow-y-auto text-sm">
                        @forelse ($auditTrail as $log)
                            <li>
                                <p><span class="font-mono text-xs text-navy-500">{{ $log->action }}</span> · {{ $log->description }}</p>
                                <p class="text-xs text-navy-400">{{ fmt_date($log->created_at, true) }} · {{ $log->user?->name ?? 'Sistem' }}</p>
                            </li>
                        @empty
                            <li class="text-navy-500">Belum ada catatan.</li>
                        @endforelse
                    </ul>
                    <p class="mt-3 text-xs text-navy-500">Versi terkirim: {{ $proposal->versions->pluck('version')->implode(', ') ?: '—' }}</p>
                </div>
            </section>
        </div>

        <div class="order-1 space-y-6 xl:order-2">
            {{-- Keputusan --}}
            <section class="card card-body">
                <h2 class="card-title">Keputusan dan status</h2>
                <p class="mt-1 text-xs text-navy-500">Hanya admin yang menetapkan status. Rekomendasi reviewer bersifat masukan.</p>

                @if ($canApprove)
                    <form method="POST" action="{{ route('admin.proposals.status', $proposal) }}" class="mt-4 space-y-3 rounded-2xl border border-forest-200 bg-forest-50/60 p-4" data-confirm="Setujui proposal ini? Ringkasan produk akan otomatis tampil di katalog publik (PDF tetap privat).">
                        @csrf
                        <input type="hidden" name="to" value="approved">
                        <p class="text-sm font-bold text-forest-800">Setujui & tampilkan otomatis di katalog</p>
                        <x-form.input name="public_title" label="Judul publik" :value="$proposal->public_title ?: $proposal->product_name" id="approve_title" />
                        <x-form.textarea name="public_short_description" label="Deskripsi singkat publik" :value="$proposal->public_short_description ?: $proposal->short_summary" rows="2" id="approve_desc" help="Tinjau agar tidak memuat informasi rahasia." />
                        <x-form.checkbox name="public_show_team" label="Tampilkan nama tim dan institusi" :checked="$proposal->published_at ? $proposal->public_show_team : (bool) setting('publication_show_team_default')" id="approve_team" />
                        <x-form.textarea name="note" label="Catatan keputusan (opsional)" rows="2" id="approve_note" />
                        <button class="btn btn-forest w-full"><x-icon name="check" class="size-4" /> Setujui</button>
                    </form>
                @endif

                @if ($otherTransitions->isNotEmpty())
                    <form method="POST" action="{{ route('admin.proposals.status', $proposal) }}" class="mt-4 space-y-3" data-confirm="Ubah status proposal?">
                        @csrf
                        <x-form.select name="to" label="Ubah status ke" :options="$otherTransitions->mapWithKeys(fn ($s) => [$s->value => $s->actionLabel()])->all()" required id="status_to" />
                        <x-form.textarea name="note" label="Catatan untuk peserta" rows="3" id="status_note" help="Wajib untuk Minta Revisi dan Tolak. Ditampilkan kepada tim pengusul." />
                        <button class="btn btn-dark w-full">Terapkan</button>
                    </form>
                @else
                    <p class="mt-4 text-sm text-navy-500">Tidak ada perubahan status yang tersedia dari status saat ini.</p>
                @endif

                @if ($proposal->decision_note)
                    <div class="mt-4 rounded-xl bg-navy-50 p-3 text-sm"><p class="font-semibold">Catatan terakhir</p><p class="mt-1 whitespace-pre-line text-navy-700">{{ $proposal->decision_note }}</p></div>
                @endif
            </section>

            {{-- Publikasi --}}
            @if ($proposal->approved_at)
                <section class="card card-body">
                    <h2 class="card-title">Publikasi produk</h2>
                    <dl class="data-list mt-2">
                        <div><dt>Katalog publik</dt><dd>@if ($proposal->is_public)<x-badge tone="success">Tampil</x-badge>@else<x-badge tone="danger">Tersembunyi</x-badge>@endif</dd></div>
                        <div><dt>PDF publik</dt><dd>{{ $proposal->public_pdf_enabled ? 'Dibuka' : 'Privat' }}</dd></div>
                        <div><dt>Disetujui</dt><dd>{{ fmt_date($proposal->approved_at, true) }} · {{ $proposal->approver?->name }}</dd></div>
                    </dl>
                    <a href="{{ route('admin.products.edit', $proposal) }}" class="btn btn-outline btn-sm mt-3 w-full">Kelola publikasi & ringkasan publik</a>
                </section>
            @endif

            {{-- Penugasan --}}
            <section class="card card-body">
                <h2 class="card-title">Penugasan reviewer</h2>
                <ul class="mt-3 space-y-3">
                    @forelse ($proposal->assignments as $assignment)
                        <li class="rounded-xl border border-navy-100 p-3 text-sm {{ $assignment->status === 'removed' ? 'opacity-60' : '' }}">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="font-semibold">{{ $assignment->reviewer?->name }}</span>
                                <x-badge :tone="match ($assignment->status) { 'submitted' => 'success', 'conflict_declared' => 'danger', 'removed' => 'neutral', 'in_progress' => 'warning', default => 'info' }">{{ $assignment->statusLabel() }}</x-badge>
                            </div>
                            <p class="mt-1 text-xs text-navy-500">{{ $assignment->phase?->name }} · ditugaskan {{ fmt_date($assignment->created_at) }} oleh {{ $assignment->assigner?->name ?? '—' }}{{ $assignment->due_at ? ' · batas '.fmt_date($assignment->due_at) : '' }}</p>
                            @if ($assignment->review?->weighted_score !== null)<p class="mt-1 text-xs">Skor: <strong>{{ number_format((float) $assignment->review->weighted_score, 2, ',', '.') }}</strong> ({{ $assignment->review->status === 'submitted' ? 'final' : 'draf' }})</p>@endif
                            @if ($assignment->conflict_reason)<p class="mt-1 text-xs text-red-700">Konflik: {{ $assignment->conflict_reason }}</p>@endif
                            @if ($assignment->removal_reason)<p class="mt-1 text-xs text-navy-500">Dicabut: {{ $assignment->removal_reason }}</p>@endif
                            @if ($assignment->override_note)<p class="mt-1 text-xs text-signal-700">{{ $assignment->override_note }}</p>@endif
                            @if (in_array($assignment->status, ['assigned', 'in_progress', 'submitted', 'conflict_declared'], true))
                                <details class="mt-2">
                                    <summary class="cursor-pointer text-xs font-semibold text-forest-700">Cabut atau ganti reviewer</summary>
                                    <form method="POST" action="{{ route('admin.assignments.replace', $assignment) }}" class="mt-2 space-y-2">
                                        @csrf
                                        <select name="reviewer_id" class="form-select py-1.5 text-xs" required aria-label="Reviewer pengganti">
                                            <option value="">Pilih reviewer pengganti…</option>
                                            @foreach ($reviewers->where('id', '!=', $assignment->reviewer_id) as $reviewer)
                                                <option value="{{ $reviewer->id }}">{{ $reviewer->name }} ({{ $reviewer->open_count }} terbuka)</option>
                                            @endforeach
                                        </select>
                                        <label class="flex items-center gap-2 text-xs"><input type="checkbox" name="confirm_warnings" value="1" class="form-check"> Saya sudah meninjau peringatan konflik</label>
                                        <button class="btn btn-outline btn-sm">Ganti reviewer</button>
                                    </form>
                                    @if ($assignment->status !== 'conflict_declared')
                                        <form method="POST" action="{{ route('admin.assignments.remove', $assignment) }}" class="mt-2 flex gap-2" data-confirm="Cabut penugasan ini?">
                                            @csrf
                                            <input name="reason" required placeholder="Alasan pencabutan" class="form-input py-1.5 text-xs" aria-label="Alasan pencabutan">
                                            <button class="btn btn-danger-outline btn-sm">Cabut</button>
                                        </form>
                                    @endif
                                </details>
                            @endif
                        </li>
                    @empty
                        <li class="text-sm text-navy-500">Belum ada reviewer ditugaskan.</li>
                    @endforelse
                </ul>

                @if (! in_array($proposal->status->value, ['draft', 'revision_requested', 'rejected', 'archived'], true))
                    <form method="POST" action="{{ route('admin.assignments.store') }}" class="mt-4 space-y-3 border-t border-navy-100 pt-4">
                        @csrf
                        <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">
                        <p class="text-sm font-bold">Tugaskan reviewer</p>
                        <x-form.select name="reviewer_id" label="Reviewer" :options="$reviewers->mapWithKeys(fn ($r) => [$r->id => $r->name.' · '.($r->institution ?: 'tanpa institusi').' · '.$r->open_count.' terbuka'])->all()" required id="assign_reviewer" />
                        <x-form.select name="review_phase_id" label="Fase" :options="$phases->pluck('name', 'id')->all()" :value="$proposal->review_phase_id ?? $phases->first()?->id" required placeholder="Pilih fase" id="assign_phase" />
                        <x-form.input name="due_at" type="datetime-local" label="Batas penilaian (opsional)" id="assign_due" />
                        <x-form.checkbox name="confirm_warnings" label="Saya sudah meninjau peringatan konflik kepentingan" id="assign_confirm" help="Konflik berat (anggota tim, mentor tim, konflik yang dinyatakan) tetap diblokir." />
                        <button class="btn btn-dark w-full"><x-icon name="plus" class="size-4" /> Tugaskan Reviewer</button>
                    </form>
                @endif
            </section>

            {{-- Kelengkapan --}}
            <section class="card card-body">
                <h2 class="card-title">Kelengkapan</h2>
                <ul class="mt-3 space-y-1.5">
                    @foreach ($checklist as $item)
                        <li class="flex items-start gap-2 text-sm"><x-icon :name="$item['done'] ? 'check-circle' : 'x-circle'" class="mt-0.5 size-4 {{ $item['done'] ? 'text-forest-600' : 'text-red-500' }}" />{{ $item['label'] }}</li>
                    @endforeach
                </ul>
                @if ($proposal->mentorAssignments->isNotEmpty())
                    <p class="mt-4 text-sm"><span class="font-semibold">Mentor tim:</span> {{ $proposal->mentorAssignments->map(fn ($m) => $m->person?->name)->filter()->implode(', ') }}</p>
                @endif
            </section>
        </div>
    </div>
</x-layouts.portal>
