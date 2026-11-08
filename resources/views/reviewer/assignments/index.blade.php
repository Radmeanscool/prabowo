<x-layouts.portal title="Proposal Ditugaskan">
    <x-page-header title="Proposal ditugaskan" subtitle="Daftar proposal yang ditugaskan admin kepada Anda.">
        <x-slot:actions>
            <form method="GET" class="flex items-center gap-2">
                <x-form.select name="status" :options="$statuses" :value="$status" placeholder="Semua status" data-autosubmit class="w-52" />
            </form>
        </x-slot:actions>
    </x-page-header>

    @if ($assignments->isEmpty())
        <x-empty icon="clipboard" title="Belum ada penugasan" text="Admin akan menugaskan proposal kepada Anda. Penugasan baru langsung muncul di halaman ini." />
    @else
        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Proposal</th><th>Tema</th><th>Fase</th><th>Status</th><th>Skor</th><th>Batas</th><th class="sr-only">Aksi</th></tr></thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <tr>
                            <td class="min-w-56"><p class="font-semibold text-navy-950">{{ $assignment->proposal->product_name }}</p><p class="text-xs text-navy-500">{{ $assignment->proposal->challenge?->title }}</p></td>
                            <td><x-theme-badge :theme="$assignment->proposal->theme" /></td>
                            <td class="whitespace-nowrap">{{ $assignment->phase->name }}</td>
                            <td><x-badge :tone="match ($assignment->status) { 'submitted' => 'success', 'conflict_declared' => 'danger', 'in_progress' => 'warning', default => 'info' }">{{ $assignment->statusLabel() }}</x-badge></td>
                            <td class="tabular-nums">{{ $assignment->review?->weighted_score !== null ? number_format((float) $assignment->review->weighted_score, 2, ',', '.') : '—' }}</td>
                            <td class="whitespace-nowrap">{{ $assignment->due_at ? fmt_date($assignment->due_at) : '—' }}</td>
                            <td class="text-right">
                                @if ($assignment->grantsAccess())
                                    <a href="{{ route('reviewer.assignments.show', $assignment) }}" class="btn btn-outline btn-sm">{{ $assignment->status === 'submitted' ? 'Lihat' : 'Nilai' }}</a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $assignments->links() }}
    @endif
</x-layouts.portal>
