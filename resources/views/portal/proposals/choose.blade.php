<x-layouts.portal title="Pilih Tema & Tantangan">
    <x-page-header eyebrow="Langkah 1 dari 3" title="Pilih tema dan tantangan" subtitle="Pilih tema → lihat tantangan terkait → pilih tantangan → lengkapi proposal. Tema tidak dapat diubah setelah proposal dikirim." />

    <form method="POST" action="{{ $action }}" class="space-y-8">
        @csrf
        @if ($method === 'PUT') @method('PUT') @endif

        @if (! $proposal)
            <div class="card card-body max-w-2xl">
                <x-form.input name="product_name" label="Nama produk/teknologi (sementara)" required help="Dapat diubah selama proposal masih berupa draf." />
            </div>
        @endif

        <section>
            <h2 class="text-lg font-extrabold">1. Tema proposal</h2>
            @error('theme_id')<p class="form-error">{{ $message }}</p>@enderror
            <div class="mt-3 grid gap-4 md:grid-cols-3">
                @foreach ($themes as $theme)
                    <label class="group card card-link relative block cursor-pointer p-5 has-[:checked]:border-forest-500 has-[:checked]:ring-2 has-[:checked]:ring-forest-500/30">
                        <input type="radio" name="theme_id" value="{{ $theme->id }}" class="sr-only" data-theme-filter required
                            @checked((string) old('theme_id', $proposal?->theme_id) === (string) $theme->id)>
                        <span class="flex items-center justify-between">
                            <x-theme-badge :theme="$theme" />
                            <span class="flex size-5 items-center justify-center rounded-full border-2 border-navy-200 group-has-[:checked]:border-forest-600 group-has-[:checked]:bg-forest-600">
                                <x-icon name="check" class="size-3 text-white" stroke="3" />
                            </span>
                        </span>
                        <span class="mt-3 block text-lg font-bold text-navy-950">{{ $theme->name }} <span class="text-sm font-medium text-navy-400">· {{ $theme->name_en }}</span></span>
                        <span class="mt-1 block text-sm leading-relaxed text-navy-600">{{ $theme->description }}</span>
                    </label>
                @endforeach
            </div>
        </section>

        <section>
            <h2 class="text-lg font-extrabold">2. Tantangan terkait</h2>
            <p class="text-sm text-navy-500">{{ $requireChallenge ? 'Wajib memilih satu tantangan.' : 'Memilih tantangan bersifat opsional.' }}</p>
            @error('challenge_id')<p class="form-error">{{ $message }}</p>@enderror

            @if ($challenges->isEmpty())
                <x-empty class="mt-4" icon="target" title="Tantangan final belum diterbitkan"
                    text="Tantangan sedang difinalisasi bersama mitra. Anda akan dapat memilih tantangan setelah panitia menerbitkannya." />
            @else
                <p class="mt-4 rounded-xl bg-navy-50 px-4 py-3 text-sm text-navy-600" data-challenge-prompt>Pilih tema terlebih dahulu untuk melihat tantangan yang relevan.</p>
                <p class="is-hidden mt-4 rounded-xl bg-signal-50 px-4 py-3 text-sm text-signal-700" data-challenge-empty>Belum ada tantangan terbit untuk tema ini.</p>
                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    @foreach ($challenges as $challenge)
                        <label class="card card-link block cursor-pointer p-5 has-[:checked]:border-forest-500 has-[:checked]:ring-2 has-[:checked]:ring-forest-500/30"
                            data-challenge-themes="{{ $challenge->themes->pluck('id')->implode(',') }}">
                            <span class="flex items-start gap-3">
                                <input type="radio" name="challenge_id" value="{{ $challenge->id }}" class="form-check mt-1 rounded-full"
                                    @checked((string) old('challenge_id', $proposal?->challenge_id) === (string) $challenge->id)>
                                <span class="min-w-0">
                                    <span class="flex flex-wrap gap-2"><x-theme-badge :theme="$challenge->theme" />
                                        @if ($challenge->publicOwner())<x-badge>{{ $challenge->publicOwner()->displayName() }}</x-badge>@endif
                                    </span>
                                    <span class="mt-2 block font-bold text-navy-950">{{ $challenge->title }}</span>
                                    <span class="mt-1 block text-sm leading-relaxed text-navy-600">{{ $challenge->summary }}</span>
                                    <a href="{{ route('challenges.show', $challenge->slug) }}" target="_blank" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-forest-700">Baca detail tantangan <x-icon name="arrow-up-right" class="size-3.5" /></a>
                                </span>
                            </span>
                        </label>
                    @endforeach
                </div>
            @endif
        </section>

        <div class="flex flex-wrap items-center gap-3 border-t border-navy-100 pt-6">
            <button type="submit" class="btn btn-primary btn-lg">{{ $proposal ? 'Simpan Tema & Tantangan' : 'Lanjut Lengkapi Proposal' }} <x-icon name="arrow-right" class="size-4" /></button>
            <a href="{{ $proposal ? route('portal.proposals.edit', $proposal) : route('portal.dashboard') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</x-layouts.portal>
