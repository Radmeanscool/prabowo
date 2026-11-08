<x-layouts.portal :title="$user->exists ? 'Ubah akun' : 'Tambah akun'">
    <x-page-header :title="$user->exists ? 'Ubah akun '.$user->name : 'Tambah akun'" subtitle="Untuk reviewer/juri: isi institusi dan keahlian agar pemeriksaan konflik kepentingan berjalan.">
        <x-slot:actions><a href="{{ route('admin.users.index') }}" class="btn btn-ghost"><x-icon name="arrow-left" class="size-4" /> Kembali</a></x-slot:actions>
    </x-page-header>
    <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="card card-body max-w-3xl space-y-4">
        @csrf
        @if ($user->exists) @method('PUT') @endif
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="name" label="Nama lengkap" :value="$user->name" required />
            <x-form.input name="email" type="email" label="Surel" :value="$user->email" required />
            <x-form.select name="role" label="Peran" :options="$roles" :value="$user->role" required placeholder="Pilih peran" />
            <x-form.input name="phone" label="Telepon" :value="$user->phone" />
            <x-form.input name="institution" label="Institusi" :value="$user->institution" />
            <x-form.input name="discipline" label="Bidang keahlian" :value="$user->discipline" />
            <x-form.input name="city" label="Kota" :value="$user->city" />
            <x-form.input name="province" label="Provinsi" :value="$user->province" />
            <x-form.select name="participant_type" label="Jenis peserta (khusus peserta)" :options="$participantTypes" :value="$user->participant_type" placeholder="—" />
        </div>
        <x-form.textarea name="expertise" label="Ringkasan keahlian" :value="$user->expertise" rows="2" />
        <div class="grid gap-4 border-t border-navy-100 pt-4 sm:grid-cols-2">
            <x-form.input name="password" type="password" :label="$user->exists ? 'Kata sandi baru (opsional)' : 'Kata sandi sementara'" autocomplete="new-password" :required="! $user->exists" help="Minimal 8 karakter, huruf dan angka." />
            <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi" autocomplete="new-password" :required="! $user->exists" />
        </div>
        <button class="btn btn-dark">Simpan</button>
    </form>
</x-layouts.portal>
