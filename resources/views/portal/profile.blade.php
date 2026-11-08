<x-layouts.portal title="Profil Saya">
    <x-page-header title="Profil Saya" subtitle="Data ini dipakai panitia untuk administrasi. Nomor telepon dan surel tidak pernah ditampilkan publik." />
    <form method="POST" action="{{ route('portal.profile.update') }}" class="card card-body max-w-3xl space-y-4">
        @csrf
        @method('PUT')
        <x-form.input name="name" label="Nama lengkap" :value="$user->name" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="email" type="email" label="Surel" :value="$user->email" required />
            <x-form.input name="phone" type="tel" label="Telepon/WhatsApp" :value="$user->phone" required />
        </div>
        <x-form.input name="institution" label="Institusi/organisasi" :value="$user->institution" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="city" label="Kota/kabupaten" :value="$user->city" required />
            <x-form.input name="province" label="Provinsi" :value="$user->province" required />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.input name="discipline" label="Bidang keilmuan/keahlian" :value="$user->discipline" required />
            <x-form.select name="participant_type" label="Jenis peserta" :options="$participantTypes" :value="$user->participant_type" required />
        </div>
        <x-form.textarea name="expertise" label="Ringkasan keahlian (opsional)" :value="$user->expertise" rows="3" />
        <p class="text-xs text-navy-500">Persetujuan ketentuan dan pemrosesan data: {{ $user->consented_at ? fmt_date($user->consented_at, true) : '—' }}</p>
        <div><button type="submit" class="btn btn-dark">Simpan Profil</button></div>
    </form>
</x-layouts.portal>
