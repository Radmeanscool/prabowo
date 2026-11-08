<x-layouts.portal title="Nilai Proposal">
    <x-page-header :eyebrow="$assignment->phase->name" :title="$proposal->product_name">
        <x-slot:actions>
            <x-theme-badge :theme="$proposal->theme" />
            @if ($locked)
                <x-badge tone="success"><x-icon name="lock" class="size-3" /> Penilaian final terkunci</x-badge>
            @elseif ($review)
                <x-badge tone="warning">Draf tersimpan</x-badge>
            @endif
        </x-slot:actions>
    </x-page-header>

    @if ($assignment->due_at)
        <p class="mb-4 text-sm {{ $assignment->due_at->isPast() ? 'text-red-700' : 'text-navy-600' }}"><x-icon name="clock" class="inline size-4" /> Batas penilaian: {{ fmt_date($assignment->due_at, true) }}</p>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_440px]">
        <div class="min-w-0">
            @if ($proposal->challenge)
                <div class="card card-body mb-6">
                    <p class="eyebrow text-navy-400">Tantangan yang dijawab</p>
                    <p class="mt-1 font-bold text-navy-950">{{ $proposal->challenge->title }}</p>
                    @if ($proposal->challenge->summary)<p class="mt-1 text-sm text-navy-600">{{ $proposal->challenge->summary }}</p>@endif
                </div>
            @endif
            @include('proposals.content', ['proposal' => $proposal, 'viewer' => 'reviewer', 'blind' => $blind])
        </div>

        <div class="space-y-6 xl:sticky xl:top-24 xl:max-h-[calc(100vh-7rem)] xl:self-start xl:overflow-y-auto">
            <form method="POST" action="{{ route('reviewer.assignments.review', $assignment) }}" class="card">
                @csrf
                @method('PUT')
                <div class="card-body border-b border-navy-100">
                    <h2 class="card-title">Formulir penilaian</h2>
                    <p class="mt-1 text-xs leading-relaxed text-navy-500">Nilai setiap kriteria 1 sampai skor maksimum. Skor tertimbang dihitung otomatis oleh sistem saat disimpan.</p>
                    @if ($review?->weighted_score !== null)
                        <p class="mt-3 text-sm">Skor tertimbang saat ini: <span class="text-xl font-extrabold tabular-nums">{{ number_format((float) $review->weighted_score, 2, ',', '.') }}</span> / 100</p>
                    @endif
                </div>

                <fieldset @disabled($locked) class="divide-y divide-navy-100">
                    @forelse ($criteria as $criterion)
                        @php $current = old('scores.'.$criterion->id, $scores[$criterion->id]->score ?? null); @endphp
                        <div class="p-5 sm:px-6">
                            <div class="flex items-start justify-between gap-3">
                                <p class="font-semibold text-navy-950">{{ $loop->iteration }}. {{ $criterion->name }}</p>
                                <span class="shrink-0 text-xs font-semibold text-navy-500">Bobot {{ rtrim(rtrim(number_format((float) $criterion->weight, 2, ',', '.'), '0'), ',') }}%</span>
                            </div>
                            @if ($criterion->description)<p class="mt-1 text-xs leading-relaxed text-navy-500">{{ $criterion->description }}</p>@endif
                            <div class="mt-3 flex flex-wrap gap-2" role="radiogroup" aria-label="Nilai {{ $criterion->name }}">
                                @for ($n = 1; $n <= $criterion->max_score; $n++)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="scores[{{ $criterion->id }}]" value="{{ $n }}" class="peer sr-only" @checked((string) $current === (string) $n)>
                                        <span class="flex size-10 items-center justify-center rounded-xl border border-navy-200 text-sm font-bold text-navy-700 transition peer-checked:border-navy-900 peer-checked:bg-navy-900 peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-signal-500 hover:border-navy-400">{{ $n }}</span>
                                    </label>
                                @endfor
                            </div>
                            @error('scores.'.$criterion->id)<p class="form-error">{{ $message }}</p>@enderror
                            <x-form.textarea :name="'comments['.$criterion->id.']'" :label="$criterion->requires_comment ? 'Komentar/bukti (wajib)' : 'Komentar/bukti (opsional)'" :value="$scores[$criterion->id]->comment ?? null" rows="2" class="mt-3" />
                        </div>
                    @empty
                        <p class="p-6 text-sm text-navy-500">Belum ada kriteria aktif untuk fase ini. Hubungi admin.</p>
                    @endforelse

                    <div class="space-y-4 p-5 sm:px-6">
                        <x-form.select name="recommendation" label="Rekomendasi" :options="$recommendations" :value="$review?->recommendation" help="Rekomendasi bukan keputusan akhir; keputusan ditetapkan admin/panel." />
                        <x-form.textarea name="overall_comment" label="Komentar umum" :value="$review?->overall_comment" rows="4" help="Tidak ditampilkan kepada peserta kecuali panitia mengaktifkannya." />
                        <x-form.textarea name="private_notes" label="Catatan pribadi untuk admin" :value="$review?->private_notes" rows="3" help="Hanya terlihat oleh admin." />
                    </div>
                </fieldset>

                @unless ($locked)
                    <div class="flex flex-wrap gap-2 border-t border-navy-100 p-5 sm:px-6">
                        <button type="submit" name="action" value="draft" class="btn btn-outline">Simpan Penilaian</button>
                        <button type="submit" name="action" value="submit" class="btn btn-primary" data-confirm-click="Kirim penilaian final? Penilaian akan terkunci dan tidak dapat diubah.">Kirim Penilaian Final</button>
                    </div>
                @endunless
            </form>

            @unless ($locked)
                <form id="konflik" method="POST" action="{{ route('reviewer.assignments.conflict', $assignment) }}" class="card card-body space-y-3" data-confirm="Nyatakan konflik kepentingan? Anda tidak akan dapat mengakses proposal ini lagi.">
                    @csrf
                    <h2 class="card-title">Konflik kepentingan</h2>
                    <p class="text-sm text-navy-500">Jika Anda memiliki hubungan dengan tim pengusul (rekan kerja, keluarga, mahasiswa bimbingan, mitra usaha, atau mentor tim), nyatakan di sini. Admin akan menugaskan reviewer lain.</p>
                    <x-form.textarea name="reason" label="Alasan" rows="2" required />
                    <button class="btn btn-danger-outline">Nyatakan Konflik Kepentingan</button>
                </form>
            @endunless
        </div>
    </div>
</x-layouts.portal>
