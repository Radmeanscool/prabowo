<x-layouts.portal title="Ubah kata sandi">
    <x-page-header title="Ubah kata sandi" subtitle="Gunakan kata sandi yang kuat dan tidak dipakai di layanan lain." />
    <div class="card card-body max-w-xl">
        <form method="POST" action="{{ route('account.password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <x-form.input name="current_password" type="password" label="Kata sandi saat ini" autocomplete="current-password" required />
            <x-form.input name="password" type="password" label="Kata sandi baru" autocomplete="new-password" required help="Minimal 8 karakter, berisi huruf dan angka." />
            <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi baru" autocomplete="new-password" required />
            <button type="submit" class="btn btn-dark">Simpan</button>
        </form>
    </div>
</x-layouts.portal>
