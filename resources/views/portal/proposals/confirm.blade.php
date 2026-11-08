<x-layouts.portal title="Konfirmasi Pengiriman">
    @php $isRevision = $proposal->status->value === 'revision_requested'; @endphp
    <x-page-header eyebrow="Langkah 3 dari 3" :title="$isRevision ? 'Ajukan revisi proposal' : 'Kirim proposal'" subtitle="Periksa kembali proposal Anda. Setelah dikirim, proposal terkunci dan tidak dapat diubah kecuali panitia membukanya kembali." />

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="card card-body">
                <h2 class="card-title">Ringkasan</h2>
                <dl class="data-list mt-2">
                    <div><dt>Produk</dt><dd class="font-semibold">{{ $proposal->product_name }}</dd></div>
                    <div><dt>Tema</dt><dd><x-theme-badge :theme="$proposal->theme" /></dd></div>
                    <div><dt>Tantangan</dt><dd>{{ $proposal->challenge?->title ?? '—' }}</dd></div>
                    <div><dt>Tim</dt><dd>{{ $proposal->team->name }} · {{ $proposal->team->members->where('status', 'active')->count() }} anggota aktif</dd></div>
                    <div><dt>Berkas</dt><dd>{{ $proposal->files->count() }} berkas terunggah</dd></div>
                    <div><dt>Versi</dt><dd>Akan menjadi versi {{ $proposal->version + 1 }}</dd></div>
                </dl>
            </div>

            <div class="card card-body">
                <h2 class="card-title">Daftar periksa</h2>
                <ul class="mt-3 space-y-2">
                    @foreach ($checklist as $item)
                        <li class="flex items-start gap-2.5 text-sm">
                            <x-icon :name="$item['done'] ? 'check-circle' : 'x-circle'" class="mt-0.5 size-4 {{ $item['done'] ? 'text-forest-600' : 'text-red-500' }}" />
                            <span class="{{ $item['done'] ? 'text-navy-800' : 'font-semibold text-red-700' }}">{{ $item['label'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card card-body self-start">
            @if (! $complete)
                <p class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-800">Proposal belum lengkap. Lengkapi butir bertanda merah.</p>
                <a href="{{ route('portal.proposals.edit', $proposal) }}" class="btn btn-dark mt-4 w-full">Lengkapi Proposal</a>
            @elseif (! $isLeader)
                <p class="rounded-xl bg-signal-50 px-4 py-3 text-sm text-signal-700">Proposal lengkap. Hanya ketua tim yang dapat mengirim proposal.</p>
            @elseif ($needsOpenCall && ! $openCall['open'])
                <p class="rounded-xl bg-signal-50 px-4 py-3 text-sm text-signal-700"><strong>{{ $openCall['label'] }}.</strong> Proposal dapat dikirim saat Open Call dibuka. Draf Anda tetap tersimpan.</p>
            @else
                <form method="POST" action="{{ route('portal.proposals.submit', $proposal) }}" class="space-y-4" data-confirm="{{ $isRevision ? 'Ajukan revisi sekarang?' : 'Kirim proposal sekarang? Proposal akan terkunci setelah dikirim.' }}">
                    @csrf
                    <label class="flex items-start gap-3 text-sm leading-relaxed">
                        <input type="checkbox" name="confirm" value="1" class="form-check mt-0.5" required>
                        <span>Kami menyatakan isian dan berkas proposal benar, merupakan karya tim, dan status KI, perizinan, serta ketergantungan pihak ketiga telah diungkapkan dengan jujur.</span>
                    </label>
                    @error('confirm')<p class="form-error">{{ $message }}</p>@enderror
                    <button type="submit" class="btn btn-primary btn-lg w-full"><x-icon name="upload" class="size-4" /> {{ $isRevision ? 'Ajukan Revisi' : 'Kirim Proposal' }}</button>
                </form>
            @endif
            <a href="{{ route('portal.proposals.show', $proposal) }}" class="btn btn-ghost mt-3 w-full">Pratinjau proposal</a>
        </div>
    </div>
</x-layouts.portal>
