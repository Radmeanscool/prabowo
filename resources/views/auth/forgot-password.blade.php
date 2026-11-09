<x-layouts.auth title="Lupa kata sandi">
    <h1 class="font-serif text-[2.6rem] font-normal leading-[1.05]">Lupa kata sandi</h1>
    <p class="mt-2 text-sm text-navy-500">Masukkan surel akun Anda. Kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>
    <div class="mt-6"><x-flash /></div>
    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Surel" autocomplete="email" required autofocus />
        <button type="submit" class="btn btn-dark btn-lg w-full">Kirim Tautan</button>
    </form>
    <p class="mt-8 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-forest-700">Kembali ke halaman masuk</a></p>
</x-layouts.auth>
