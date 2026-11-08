<x-layouts.portal :title="$team->name">
    <x-page-header eyebrow="Tim" :title="$team->name" :subtitle="$team->institution">
        <x-slot:actions><a href="{{ route('admin.teams.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" class="size-4" /> Kembali</a></x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3">
        <div class="space-y-6 xl:col-span-2">
            <div class="card">
                <div class="card-body border-b border-navy-100 flex items-center justify-between">
                    <h2 class="card-title">Anggota ({{ $team->seatCount() }}/{{ $maxSize }})</h2>
                </div>
                <div class="table-wrap rounded-none border-0">
                    <table class="table">
                        <thead><tr><th>Nama</th><th>Keahlian & institusi</th><th>Status</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody>
                            @foreach ($team->members as $member)
                                <tr>
                                    <td><p class="font-semibold">{{ $member->name }} @if ($member->isLeader())<x-badge tone="dark">Ketua</x-badge>@endif</p><p class="text-xs text-navy-500">{{ $member->email ?: 'tanpa surel' }} {{ $member->user_id ? '' : '· tanpa akun' }}</p></td>
                                    <td colspan="2">
                                        <form method="POST" action="{{ route('admin.teams.members.update', [$team, $member]) }}" class="flex flex-wrap items-center gap-2">
                                            @csrf @method('PUT')
                                            <input name="discipline" value="{{ $member->discipline }}" class="form-input w-40 py-1.5 text-xs" aria-label="Keahlian {{ $member->name }}">
                                            <input name="institution" value="{{ $member->institution }}" class="form-input w-40 py-1.5 text-xs" aria-label="Institusi {{ $member->name }}">
                                            <select name="status" class="form-select w-36 py-1.5 text-xs" aria-label="Status {{ $member->name }}">
                                                @foreach (\App\Support\Options::memberStatuses() as $value => $label)
                                                    <option value="{{ $value }}" @selected($member->status === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                            <button class="btn btn-outline btn-sm">Simpan</button>
                                        </form>
                                    </td>
                                    <td class="text-right">
                                        @unless ($member->isLeader())
                                            <form method="POST" action="{{ route('admin.teams.members.destroy', [$team, $member]) }}" data-confirm="Hapus anggota ini?">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-danger-outline btn-sm">Hapus</button>
                                            </form>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <form method="POST" action="{{ route('admin.teams.members.store', $team) }}" class="grid gap-3 border-t border-navy-100 p-5 sm:grid-cols-5 sm:px-6">
                    @csrf
                    <x-form.input name="email" type="email" label="Surel (akun terdaftar atau baru)" class="sm:col-span-2" />
                    <x-form.input name="name" label="Nama (bila tanpa akun)" />
                    <x-form.input name="discipline" label="Keahlian" />
                    <div class="flex items-end"><button class="btn btn-dark w-full">Tambah</button></div>
                </form>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Proposal</h2>
                <ul class="mt-3 divide-y divide-navy-100">
                    @forelse ($team->proposals as $proposal)
                        <li class="flex flex-wrap items-center justify-between gap-3 py-3">
                            <a href="{{ route('admin.proposals.show', $proposal) }}" class="font-semibold text-forest-700 hover:underline">{{ $proposal->product_name }}</a>
                            <span class="flex gap-2"><x-theme-badge :theme="$proposal->theme" /><x-status-badge :status="$proposal->status" /></span>
                        </li>
                    @empty
                        <li class="py-3 text-sm text-navy-500">Belum ada proposal.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="space-y-6">
            <form method="POST" action="{{ route('admin.teams.update', $team) }}" class="card card-body space-y-4">
                @csrf @method('PUT')
                <h2 class="card-title">Profil tim</h2>
                <x-form.input name="name" label="Nama tim" :value="$team->name" id="team_name" required />
                <x-form.input name="institution" label="Institusi" :value="$team->institution" id="team_institution" required />
                <x-form.input name="city" label="Kota" :value="$team->city" id="team_city" />
                <x-form.input name="province" label="Provinsi" :value="$team->province" id="team_province" />
                <x-form.input name="contact_email" type="email" label="Surel kontak" :value="$team->contact_email" id="team_email" />
                <x-form.input name="contact_phone" label="Telepon kontak" :value="$team->contact_phone" id="team_phone" />
                <x-form.textarea name="profile" label="Profil" :value="$team->profile" rows="3" id="team_profile" />
                <x-form.select name="leader_member_id" label="Ketua tim" :options="$team->members->where('status', 'active')->whereNotNull('user_id')->pluck('name', 'id')->all()" :value="$team->members->firstWhere('role', 'leader')?->id" placeholder="—" help="Hanya anggota aktif yang memiliki akun." />
                <p class="text-xs text-navy-500">Kode tim: <code class="font-bold">{{ $team->join_code }}</code></p>
                <button class="btn btn-dark btn-sm">Simpan</button>
            </form>

            <div class="card card-body">
                <h2 class="card-title">Mentor tim</h2>
                <p class="mt-1 text-xs text-navy-500">Mentor yang terhubung ke akun reviewer otomatis diblokir menilai tim ini.</p>
                <ul class="mt-3 space-y-2">
                    @forelse ($team->mentorAssignments as $assignment)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span><span class="font-semibold">{{ $assignment->person?->name }}</span> <span class="text-navy-500">{{ $assignment->role === 'partner' ? '· mentor mitra' : ($assignment->role === 'internal' ? '· mentor internal' : '') }}</span></span>
                            <form method="POST" action="{{ route('admin.teams.mentors.destroy', [$team, $assignment]) }}" data-confirm="Cabut penugasan mentor ini?">
                                @csrf @method('DELETE')
                                <button class="text-xs font-semibold text-red-700">Cabut</button>
                            </form>
                        </li>
                    @empty
                        <li class="text-sm text-navy-500">Belum ada mentor.</li>
                    @endforelse
                </ul>
                <form method="POST" action="{{ route('admin.teams.mentors.store', $team) }}" class="mt-4 space-y-3 border-t border-navy-100 pt-4">
                    @csrf
                    <x-form.select name="person_id" label="Mentor" :options="$mentors->pluck('name', 'id')->all()" required />
                    <x-form.select name="role" label="Jenis mentor" :options="['internal' => 'Mentor internal', 'partner' => 'Mentor mitra']" placeholder="—" />
                    <button class="btn btn-outline btn-sm">Tugaskan Mentor</button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.portal>
