<x-layouts.portal title="Log Audit">
    <x-page-header title="Log audit" subtitle="Jejak pengiriman, pembukaan kembali, keputusan, publikasi, penugasan, penilaian, perubahan peran, dan akses berkas sensitif." />
    <form method="GET" class="card card-body mb-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-6">
        <x-form.select name="action" :options="$groups" :value="request('action')" placeholder="Semua aktivitas" aria-label="Aktivitas" />
        <input type="search" name="user" value="{{ request('user') }}" placeholder="Pengguna (nama/surel)" class="form-input" aria-label="Pengguna">
        <input type="text" name="subject" value="{{ request('subject') }}" placeholder="Objek (mis. Proposal)" class="form-input" aria-label="Objek">
        <input type="date" name="from" value="{{ request('from') }}" class="form-input" aria-label="Dari tanggal">
        <input type="date" name="to" value="{{ request('to') }}" class="form-input" aria-label="Sampai tanggal">
        <button class="btn btn-dark">Saring</button>
    </form>
    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Waktu</th><th>Pengguna</th><th>Aktivitas</th><th>Objek</th><th>Perubahan</th><th>IP</th></tr></thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="whitespace-nowrap text-xs">{{ fmt_date($log->created_at, true) }}</td>
                        <td class="text-sm">{{ $log->user?->name ?? 'Sistem/tamu' }}<p class="text-xs text-navy-500">{{ $log->user?->email }}</p></td>
                        <td class="min-w-48"><p class="font-mono text-xs font-semibold">{{ $log->action }}</p><p class="text-xs text-navy-600">{{ $log->description }}</p></td>
                        <td class="whitespace-nowrap text-xs">{{ $log->subjectLabel() }}</td>
                        <td class="min-w-64 text-xs">
                            @if ($log->old_values)<p class="text-red-700"><span class="font-semibold">Lama:</span> {{ \Illuminate\Support\Str::limit(json_encode($log->old_values, JSON_UNESCAPED_UNICODE), 220) }}</p>@endif
                            @if ($log->new_values)<p class="text-forest-700"><span class="font-semibold">Baru:</span> {{ \Illuminate\Support\Str::limit(json_encode($log->new_values, JSON_UNESCAPED_UNICODE), 220) }}</p>@endif
                        </td>
                        <td class="text-xs text-navy-500">{{ $log->ip_address }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-8 text-center text-navy-500">Tidak ada catatan.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $logs->links() }}
</x-layouts.portal>
