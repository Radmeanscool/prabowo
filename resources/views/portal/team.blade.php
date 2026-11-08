<x-layouts.portal title="Tim Saya">
    @if (! $team)
        <x-page-header title="Tim Saya" subtitle="Satu peserta tergabung dalam satu tim. Buat tim baru sebagai ketua, atau gabung memakai kode tim." />

        @if ($pending)
            <div class="card card-body mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-bold">Menunggu persetujuan ketua tim</p>
                    <p class="text-sm text-navy-600">Permintaan Anda untuk bergabung dengan <strong>{{ $pending->team->name }}</strong> belum disetujui.</p>
                </div>
                <form method="POST" action="{{ route('portal.team.cancel-join') }}" data-confirm="Batalkan permintaan bergabung?">
                    @csrf
                    <button class="btn btn-outline btn-sm">Batalkan permintaan</button>
                </form>
            </div>
        @else
            <div class="grid gap-6 lg:grid-cols-2">
                <form method="POST" action="{{ route('portal.team.store') }}" class="card card-body space-y-4">
                    @csrf
                    <div>
                        <h2 class="card-title">Buat tim baru</h2>
                        <p class="text-sm text-navy-500">Anda otomatis menjadi ketua tim.</p>
                    </div>
                    <x-form.input name="name" label="Nama tim" required />
                    <x-form.input name="institution" label="Institusi utama tim" :value="$user->institution" required />
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input name="city" label="Kota" :value="$user->city" />
                        <x-form.input name="province" label="Provinsi" :value="$user->province" />
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.input name="contact_email" type="email" label="Surel kontak tim" :value="$user->email" required />
                        <x-form.input name="contact_phone" type="tel" label="Telepon kontak tim" :value="$user->phone" required />
                    </div>
                    <x-form.textarea name="profile" label="Profil singkat tim" rows="3" help="Komposisi keahlian dan pengalaman tim yang relevan." />
                    <button type="submit" class="btn btn-dark">Buat Tim</button>
                </form>

                <form method="POST" action="{{ route('portal.team.join') }}" class="card card-body space-y-4 self-start">
                    @csrf
                    <div>
                        <h2 class="card-title">Gabung dengan tim</h2>
                        <p class="text-sm text-navy-500">Minta kode tim (8 karakter) kepada ketua tim Anda.</p>
                    </div>
                    <x-form.input name="join_code" label="Kode tim" class="uppercase" maxlength="16" autocomplete="off" required />
                    <button type="submit" class="btn btn-primary">Ajukan Bergabung</button>
                    <p class="text-xs text-navy-500">Maksimal {{ $maxSize }} anggota per tim{{ setting('team_leader_approval_required') ? '; ketua tim perlu menyetujui permintaan Anda' : '' }}.</p>
                </form>
            </div>
        @endif
    @else
        <x-page-header eyebrow="Tim Saya" :title="$team->name" :subtitle="$team->institution.($team->city ? ' · '.$team->city : '')">
            <x-slot:actions>
                @unless ($isLeader)
                    <form method="POST" action="{{ route('portal.team.leave') }}" data-confirm="Keluar dari tim ini?">
                        @csrf
                        <button class="btn btn-danger-outline btn-sm">Keluar dari tim</button>
                    </form>
                @endunless
            </x-slot:actions>
        </x-page-header>

        <div class="grid gap-6 xl:grid-cols-3">
            <div class="space-y-6 xl:col-span-2">
                <div class="card">
                    <div class="card-body flex items-center justify-between border-b border-navy-100">
                        <div>
                            <h2 class="card-title">Anggota tim</h2>
                            <p class="text-sm text-navy-500">{{ $team->seatCount() }} dari {{ $maxSize }} kursi terisi</p>
                        </div>
                    </div>
                    <ul class="divide-y divide-navy-100">
                        @foreach ($team->members as $member)
                            <li class="p-5 sm:px-6">
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <div class="min-w-0">
                                        <p class="font-semibold text-navy-950">{{ $member->name }}
                                            @if ($member->isLeader())<x-badge tone="dark" class="ml-1">Ketua</x-badge>@endif
                                            @if ($member->status !== 'active')<x-badge tone="warning" class="ml-1">{{ $member->statusLabel() }}</x-badge>@endif
                                            @unless ($member->user_id)<x-badge class="ml-1">Tanpa akun</x-badge>@endunless
                                        </p>
                                        <p class="mt-0.5 text-sm text-navy-600">{{ $member->discipline ?: '—' }} · {{ $member->institution ?: '—' }}</p>
                                        @if ($isLeader && $member->email)<p class="text-xs text-navy-400">{{ $member->email }}</p>@endif
                                    </div>
                                    @if ($isLeader && ! $member->isLeader())
                                        <div class="flex flex-wrap gap-2">
                                            @if ($member->status === 'pending')
                                                <form method="POST" action="{{ route('portal.team.members.approve', $member) }}">
                                                    @csrf
                                                    <button class="btn btn-forest btn-sm"><x-icon name="check" class="size-4" /> Setujui</button>
                                                </form>
                                            @endif
                                            <form method="POST" action="{{ route('portal.team.members.destroy', $member) }}" data-confirm="Hapus {{ $member->name }} dari tim?">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-danger-outline btn-sm">{{ $member->status === 'pending' ? 'Tolak' : 'Hapus' }}</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                                @if ($isLeader && $member->status === 'active')
                                    <details class="mt-3">
                                        <summary class="cursor-pointer text-xs font-semibold text-forest-700">Ubah keahlian/institusi</summary>
                                        <form method="POST" action="{{ route('portal.team.members.update', $member) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                                            @csrf
                                            @method('PUT')
                                            @unless ($member->user_id)
                                                <x-form.input name="name" label="Nama" :value="$member->name" :id="'m_name_'.$member->id" required />
                                                <x-form.input name="email" type="email" label="Surel" :value="$member->email" :id="'m_email_'.$member->id" />
                                            @endunless
                                            <x-form.input name="discipline" label="Bidang keahlian" :value="$member->discipline" :id="'m_disc_'.$member->id" required />
                                            <x-form.input name="institution" label="Institusi" :value="$member->institution" :id="'m_inst_'.$member->id" />
                                            <div class="sm:col-span-2"><button class="btn btn-outline btn-sm">Simpan</button></div>
                                        </form>
                                    </details>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>

                @if ($isLeader)
                    <form method="POST" action="{{ route('portal.team.members.store') }}" class="card card-body space-y-4">
                        @csrf
                        <div>
                            <h2 class="card-title">Tambah anggota tanpa akun</h2>
                            <p class="text-sm text-navy-500">Untuk anggota yang tidak memiliki akun portal. Anggota yang punya akun sebaiknya bergabung memakai kode tim.</p>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <x-form.input name="name" label="Nama lengkap" required />
                            <x-form.input name="discipline" label="Bidang keahlian" required />
                            <x-form.input name="institution" label="Institusi" />
                            <x-form.input name="email" type="email" label="Surel (opsional)" />
                        </div>
                        <div><button class="btn btn-dark btn-sm"><x-icon name="plus" class="size-4" /> Tambah Anggota</button></div>
                    </form>
                @endif
            </div>

            <div class="space-y-6">
                @if ($isLeader)
                    <div class="card card-body">
                        <h2 class="card-title">Kode tim</h2>
                        <p class="mt-1 text-sm text-navy-500">Bagikan kode ini kepada anggota yang sudah memiliki akun.</p>
                        <div class="mt-4 flex items-center gap-2">
                            <code class="flex-1 rounded-xl bg-navy-950 px-4 py-3 text-center text-lg font-bold tracking-[0.3em] text-signal-300">{{ $team->join_code }}</code>
                            <button type="button" class="btn btn-outline" data-copy="{{ $team->join_code }}">Salin</button>
                        </div>
                        <form method="POST" action="{{ route('portal.team.code') }}" class="mt-3" data-confirm="Buat kode baru? Kode lama tidak berlaku lagi.">
                            @csrf
                            <button class="text-xs font-semibold text-navy-500 hover:text-navy-800"><x-icon name="refresh" class="inline size-3.5" /> Buat kode baru</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('portal.team.update') }}" class="card card-body space-y-4">
                        @csrf
                        @method('PUT')
                        <h2 class="card-title">Profil tim</h2>
                        <x-form.input name="name" label="Nama tim" :value="$team->name" id="t_name" required />
                        <x-form.input name="institution" label="Institusi utama" :value="$team->institution" id="t_inst" required />
                        <x-form.input name="city" label="Kota" :value="$team->city" id="t_city" />
                        <x-form.input name="province" label="Provinsi" :value="$team->province" id="t_prov" />
                        <x-form.input name="contact_email" type="email" label="Surel kontak" :value="$team->contact_email" id="t_email" required />
                        <x-form.input name="contact_phone" type="tel" label="Telepon kontak" :value="$team->contact_phone" id="t_phone" required />
                        <x-form.textarea name="profile" label="Profil singkat" :value="$team->profile" rows="4" id="t_profile" />
                        <button class="btn btn-dark btn-sm">Simpan Profil Tim</button>
                    </form>
                @else
                    <div class="card card-body">
                        <h2 class="card-title">Profil tim</h2>
                        <dl class="data-list mt-2">
                            <div><dt>Ketua</dt><dd>{{ $team->leader?->name }}</dd></div>
                            <div><dt>Kontak</dt><dd>{{ $team->contact_email }}</dd></div>
                            <div><dt>Profil</dt><dd class="whitespace-pre-line">{{ $team->profile ?: '—' }}</dd></div>
                        </dl>
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-layouts.portal>
