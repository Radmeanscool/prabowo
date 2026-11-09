<x-layouts.auth title="Daftar">
    <h1 class="font-serif text-[2.6rem] font-normal leading-[1.05]">Daftar sebagai peserta</h1>
    <p class="mt-2 text-sm leading-relaxed text-navy-500">Buat akun pribadi, lalu buat tim atau bergabung dengan tim lintas disiplin. Akun reviewer/juri dibuat oleh panitia.</p>

    <div class="mt-6"><x-flash /></div>

    @if (! $enabled)
        <x-empty icon="lock" title="Pendaftaran sedang ditutup" text="Pantau halaman Pengumuman untuk jadwal Open Call berikutnya.">
            <a href="{{ route('announcements.index') }}" class="btn btn-outline">Lihat Pengumuman</a>
        </x-empty>
    @else
        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <x-form.input name="name" label="Nama lengkap" autocomplete="name" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="email" type="email" label="Surel" autocomplete="email" required />
                <x-form.input name="phone" type="tel" label="Nomor telepon/WhatsApp" autocomplete="tel" required help="Tidak ditampilkan publik." />
            </div>
            <x-form.input name="institution" label="Institusi/organisasi" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="city" label="Kota/kabupaten" required />
                <x-form.input name="province" label="Provinsi" required />
            </div>
            <x-form.input name="discipline" label="Bidang keilmuan/keahlian" required help="Contoh: teknik elektro, gizi, peternakan, informatika." />
            <x-form.select name="participant_type" label="Jenis peserta" :options="$participantTypes" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.input name="password" type="password" label="Kata sandi" autocomplete="new-password" required help="Minimal 8 karakter, berisi huruf dan angka." />
                <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi" autocomplete="new-password" required />
            </div>
            <label class="flex items-start gap-3 rounded-xl border border-navy-100 bg-navy-50/60 p-3">
                <input type="checkbox" name="consent" value="1" class="form-check mt-0.5" @checked(old('consent')) required>
                <span class="text-sm leading-relaxed text-navy-700">
                    Saya menyetujui <a href="{{ route('rules') }}" target="_blank" class="font-semibold text-forest-700 underline">ketentuan program</a>,
                    <a href="{{ route('terms') }}" target="_blank" class="font-semibold text-forest-700 underline">syarat penggunaan</a>, dan pemrosesan data sesuai
                    <a href="{{ route('privacy') }}" target="_blank" class="font-semibold text-forest-700 underline">kebijakan privasi</a>.
                </span>
            </label>
            @error('consent')<p class="form-error">{{ $message }}</p>@enderror
            <button type="submit" class="btn btn-primary btn-lg w-full">Daftar Sekarang</button>
        </form>
    @endif

    <p class="mt-8 text-center text-sm text-navy-600">
        Sudah punya akun? <a href="{{ route('login') }}" class="font-semibold text-forest-700 hover:text-forest-600">Masuk</a>
    </p>
</x-layouts.auth>
