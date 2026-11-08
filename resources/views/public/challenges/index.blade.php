<x-layouts.public title="Tantangan" description="Tantangan nyata dari mitra pemilik masalah DEEPNEST di bidang ketahanan pangan dan kesehatan.">
    <x-page-hero title="Tantangan nyata dari pemilik masalah" eyebrow="Tantangan" lead="Setiap tantangan dirumuskan bersama mitra: masalah, pengguna terdampak, fungsi minimum, indikator keberhasilan, batasan, dan dukungan yang tersedia." />

    <section class="section">
        <div class="container-page">
            <nav class="flex flex-wrap gap-2" aria-label="Saring berdasarkan tema">
                <a href="{{ route('challenges.index') }}" class="btn btn-sm {{ ! $activeTheme ? 'btn-dark' : 'btn-outline' }}">Semua tema</a>
                @foreach ($themes as $theme)
                    <a href="{{ route('challenges.index', ['tema' => $theme->code]) }}" class="btn btn-sm {{ $activeTheme?->id === $theme->id ? 'btn-dark' : 'btn-outline' }}"><x-icon :name="$theme->icon()" class="size-4" /> {{ $theme->name }}</a>
                @endforeach
            </nav>
            @if ($owners->isNotEmpty())
                <form method="GET" class="mt-4 flex flex-wrap items-center gap-2">
                    @if ($activeTheme)<input type="hidden" name="tema" value="{{ $activeTheme->code }}">@endif
                    <label for="mitra" class="text-sm font-semibold text-navy-600">Mitra:</label>
                    <select id="mitra" name="mitra" class="form-select w-auto py-2" data-autosubmit>
                        <option value="">Semua mitra</option>
                        @foreach ($owners as $owner)
                            <option value="{{ $owner->slug }}" @selected(request('mitra') === $owner->slug)>{{ $owner->displayName() }}</option>
                        @endforeach
                    </select>
                </form>
            @endif

            @if ($challenges->isEmpty())
                <div class="mt-10 grid gap-6 rounded-3xl border border-navy-100 bg-white p-6 sm:p-10 lg:grid-cols-[1fr_auto] lg:items-center">
                    <div>
                        <p class="text-xl font-extrabold">Tantangan final segera diumumkan</p>
                        <p class="mt-2 max-w-3xl leading-relaxed text-navy-600">Tahap Challenge Discovery sedang berlangsung: identifikasi masalah bersama mitra, validasi lapangan, dan finalisasi tantangan. Tantangan final dipublikasikan di halaman ini bersama pembukaan Open Call. Sementara itu, Anda dapat membuat akun dan membentuk tim.</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang</a>
                        <a href="{{ route('stages') }}" class="btn btn-outline">Tahapan Program</a>
                    </div>
                </div>
            @else
                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($challenges as $challenge)
                        <x-challenge-card :challenge="$challenge" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
