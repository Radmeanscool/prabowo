<x-layouts.portal title="Tim">
    <x-page-header title="Tim" subtitle="Tim lintas disiplin peserta. Admin dapat menambah/menghapus anggota, memindahkan ketua, dan menugaskan mentor." />
    <form method="GET" class="mb-4 flex gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari tim atau institusi…" class="form-input w-72">
        <button class="btn btn-outline">Cari</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Tim</th><th>Ketua</th><th>Anggota</th><th>Proposal</th><th>Dibuat</th><th class="sr-only">Aksi</th></tr></thead>
            <tbody>
                @forelse ($teams as $team)
                    <tr>
                        <td><p class="font-semibold text-navy-950">{{ $team->name }}</p><p class="text-xs text-navy-500">{{ $team->institution }}</p></td>
                        <td>{{ $team->leader?->name }}</td>
                        <td class="tabular-nums">{{ $team->active_members }} @if ($team->pending_members)<x-badge tone="warning">{{ $team->pending_members }} menunggu</x-badge>@endif</td>
                        <td>
                            @forelse ($team->proposals as $proposal)
                                <a href="{{ route('admin.proposals.show', $proposal) }}" class="block text-sm font-medium text-forest-700 hover:underline">{{ $proposal->product_name }}</a>
                            @empty
                                <span class="text-navy-400">—</span>
                            @endforelse
                        </td>
                        <td class="whitespace-nowrap">{{ fmt_date($team->created_at) }}</td>
                        <td class="text-right"><a href="{{ route('admin.teams.show', $team) }}" class="btn btn-outline btn-sm">Kelola</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-navy-500">Belum ada tim.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $teams->links() }}
</x-layouts.portal>
