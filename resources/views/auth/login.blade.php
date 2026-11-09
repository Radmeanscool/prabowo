<x-layouts.auth title="Masuk">
    <h1 class="font-serif text-[2.6rem] font-normal leading-[1.05]">Masuk ke portal DEEPNEST</h1>
    <p class="mt-2 text-sm text-navy-500">Untuk peserta, reviewer/juri, dan admin.</p>

    <div class="mt-6"><x-flash /></div>

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf
        <x-form.input name="email" type="email" label="Surel" autocomplete="email" required autofocus />
        <x-form.input name="password" type="password" label="Kata sandi" autocomplete="current-password" required />
        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm text-navy-700">
                <input type="checkbox" name="remember" value="1" class="form-check"> Ingat saya
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-600">Lupa kata sandi?</a>
        </div>
        <button type="submit" class="btn btn-dark btn-lg w-full">Masuk</button>
    </form>

    <p class="mt-8 text-center text-sm text-navy-600">
        Belum punya akun peserta? <a href="{{ route('register') }}" class="font-semibold text-forest-700 hover:text-forest-600">Daftar sekarang</a>
    </p>
</x-layouts.auth>
