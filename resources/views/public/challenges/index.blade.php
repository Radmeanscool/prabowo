<x-layouts.public title="Tantangan" description="Tantangan nyata dari mitra pemilik masalah DEEPNEST di bidang ketahanan pangan dan kesehatan.">
    <x-page-hero title="Tantangan nyata dari pemilik masalah" eyebrow="Tantangan" lead="Setiap tantangan dirumuskan bersama mitra: masalah, pengguna terdampak, fungsi minimum, indikator keberhasilan, batasan, dan dukungan yang tersedia." />

    <section class="section pt-10 sm:pt-12">
        <div class="container-page">
            <div class="flex flex-col gap-4 border-b border-navy-950/15 lg:flex-row lg:items-end lg:justify-between">
                <nav class="flex flex-wrap gap-x-7 gap-y-2" aria-label="Saring berdasarkan tema">
                    <a href="{{ route('challenges.index') }}" class="tab-link {{ ! $activeTheme ? 'is-active' : '' }}">Semua tema</a>
                    @foreach ($themes as $theme)
                        <a href="{{ route('challenges.index', ['tema' => $theme->code]) }}" class="tab-link {{ $activeTheme?->id === $theme->id ? 'is-active' : '' }}"><span class="size-2" style="background: {{ $theme->color }}" aria-hidden="true"></span>{{ $theme->name }}</a>
                    @endforeach
                </nav>
                @if ($owners->isNotEmpty())
                    <form method="GET" class="flex items-center gap-2 pb-3">
                        @if ($activeTheme)<input type="hidden" name="tema" value="{{ $activeTheme->code }}">@endif
                        <label for="mitra" class="text-sm text-navy-600">Mitra</label>
                        <select id="mitra" name="mitra" class="form-select w-auto py-1.5" data-autosubmit>
                            <option value="">Semua mitra</option>
                            @foreach ($owners as $owner)
                                <option value="{{ $owner->slug }}" @selected(request('mitra') === $owner->slug)>{{ $owner->displayName() }}</option>
                            @endforeach
                        </select>
                    </form>
                @endif
            </div>

            @if ($challenges->isEmpty() && $publishedTotal === 0)
                <div class="mt-12 grid gap-8 lg:grid-cols-12 lg:gap-10">
                    <div class="lg:col-span-7">
                        <p class="subhead">Tantangan final belum diumumkan.</p>
                        <p class="mt-3 leading-relaxed text-navy-600">Tantangan dirumuskan bersama mitra melalui identifikasi masalah, validasi lapangan, dan finalisasi, lalu dipublikasikan di halaman ini bersama pembukaan Open Call. Sementara itu, Anda sudah dapat membuat akun dan membentuk tim.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 lg:col-span-5 lg:justify-end lg:self-end">
                        @if (setting('registration_enabled', true))
                            <a href="{{ route('register') }}" class="btn btn-primary">Daftar Sekarang</a>
                        @endif
                        <a href="{{ route('stages') }}" class="btn btn-outline">Tahapan Program</a>
                    </div>
                </div>
            @elseif ($challenges->isEmpty())
                <x-empty class="mt-12" icon="search" title="Tidak ada tantangan yang cocok" text="Belum ada tantangan untuk penyaring ini. Coba pilih tema atau mitra lain.">
                    <a href="{{ route('challenges.index') }}" class="btn btn-outline btn-sm">Tampilkan semua</a>
                </x-empty>
            @else
                <div class="mt-12 grid gap-x-8 gap-y-14 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($challenges as $challenge)
                        <x-challenge-card :challenge="$challenge" />
                    @endforeach
                </div>
            @endif
        </div>
    </section>
</x-layouts.public>
