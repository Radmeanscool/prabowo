<x-layouts.portal title="Pengguna">
    <x-page-header title="Pengguna" subtitle="Peserta mendaftar sendiri. Akun reviewer/juri dan admin dibuat di sini. Peran dicatat di log audit.">
        <x-slot:actions>
            <a href="{{ route('admin.users.create') }}" class="btn btn-primary"><x-icon name="plus" class="size-4" /> Tambah akun</a>
        </x-slot:actions>
    </x-page-header>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama, surel, institusi…" class="form-input w-64">
        <x-form.select name="role" :options="$roles" :value="request('role')" placeholder="Semua peran" data-autosubmit class="w-44" />
        <button class="btn btn-outline">Cari</button>
    </form>

    <div class="table-wrap">
        <table class="table">
            <thead><tr><th>Nama</th><th>Peran</th><th>Institusi</th><th>Penugasan terbuka</th><th>Login terakhir</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
            <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td><p class="font-semibold text-navy-950">{{ $user->name }}</p><p class="text-xs text-navy-500">{{ $user->email }}</p></td>
                        <td><x-badge :tone="$user->isAdmin() ? 'dark' : ($user->isReviewer() ? 'indigo' : 'neutral')">{{ $user->role->label() }}</x-badge></td>
                        <td>{{ $user->institution ?: '—' }}</td>
                        <td class="tabular-nums">{{ $user->isReviewer() ? $user->open_assignments : '—' }}</td>
                        <td class="whitespace-nowrap">{{ $user->last_login_at ? fmt_date($user->last_login_at, true) : '—' }}</td>
                        <td>@if ($user->is_active)<x-badge tone="success">Aktif</x-badge>@else<x-badge tone="danger">Nonaktif</x-badge>@endif</td>
                        <td class="whitespace-nowrap text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-outline btn-sm">Ubah</a>
                            @unless ($user->is(auth()->user()))
                                <form method="POST" action="{{ route('admin.users.toggle', $user) }}" class="inline" data-confirm="{{ $user->is_active ? 'Nonaktifkan akun ini?' : 'Aktifkan akun ini?' }}">
                                    @csrf
                                    <button class="btn btn-sm {{ $user->is_active ? 'btn-danger-outline' : 'btn-forest' }}">{{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-navy-500">Tidak ada pengguna.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</x-layouts.portal>
