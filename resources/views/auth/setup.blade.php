<x-layouts.auth title="Setup admin">
    <p class="eyebrow text-forest-600">Instalasi awal</p>
    <h1 class="mt-1 text-2xl font-extrabold">Buat admin pertama</h1>
    <p class="mt-2 text-sm leading-relaxed text-navy-500">Halaman ini hanya tersedia sebelum ada akun admin dan bila <code class="rounded bg-navy-100 px-1">SETUP_TOKEN</code> diisi pada berkas <code class="rounded bg-navy-100 px-1">.env</code>. Setelah selesai, hapus nilai token tersebut.</p>
    <div class="mt-6"><x-flash /></div>
    <form method="POST" action="{{ route('setup') }}" class="space-y-4">
        @csrf
        <x-form.input name="token" type="password" label="Token setup" required autocomplete="off" />
        <x-form.input name="name" label="Nama admin" required />
        <x-form.input name="email" type="email" label="Surel admin" required />
        <x-form.input name="password" type="password" label="Kata sandi" required help="Minimal 10 karakter, berisi huruf dan angka." autocomplete="new-password" />
        <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi" required autocomplete="new-password" />
        <button type="submit" class="btn btn-dark btn-lg w-full">Buat Admin</button>
    </form>
</x-layouts.auth>
