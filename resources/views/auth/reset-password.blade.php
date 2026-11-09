<x-layouts.auth title="Atur ulang kata sandi">
    <h1 class="font-serif text-[2.6rem] font-normal leading-[1.05]">Atur ulang kata sandi</h1>
    <div class="mt-6"><x-flash /></div>
    <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.input name="email" type="email" label="Surel" :value="$email" autocomplete="email" required />
        <x-form.input name="password" type="password" label="Kata sandi baru" autocomplete="new-password" required help="Minimal 8 karakter, berisi huruf dan angka." />
        <x-form.input name="password_confirmation" type="password" label="Ulangi kata sandi baru" autocomplete="new-password" required />
        <button type="submit" class="btn btn-dark btn-lg w-full">Simpan Kata Sandi</button>
    </form>
</x-layouts.auth>
