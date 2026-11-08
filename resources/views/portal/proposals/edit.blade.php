<x-layouts.portal title="Lengkapi Proposal">
    @php
        $sections = [
            'identitas' => 'A. Identitas produk',
            'tim' => 'B. Tim dan institusi',
            'riset' => 'C. Latar belakang riset',
            'teknis' => 'D. Aspek teknis',
            'pasar' => 'E. Pasar, pengguna, dampak',
            'kesesuaian' => 'F. Kesesuaian tantangan',
            'kesiapan' => 'G. Kesiapan legal, keselamatan, etik, data',
            'tautan' => 'H. Berkas dan tautan',
        ];
        if ($questions->isNotEmpty()) {
            $sections['tema-khusus'] = 'Pertanyaan khusus tema';
        }
        $isRevision = $proposal->status->value === 'revision_requested';
    @endphp

    <x-page-header eyebrow="Langkah 2 dari 3 · {{ $proposal->status->label() }}" :title="$proposal->product_name" subtitle="Isi setiap bagian secara ringkas dan berbasis data. Simpan sebagai draf kapan saja; proposal baru terkunci setelah dikirim.">
        <x-slot:actions>
            <a href="{{ route('portal.proposals.show', $proposal) }}" class="btn btn-outline btn-sm"><x-icon name="eye" class="size-4" /> Pratinjau</a>
            <a href="{{ route('portal.proposals.confirm', $proposal) }}" class="btn btn-primary btn-sm">{{ $isRevision ? 'Ajukan Revisi' : 'Kirim Proposal' }} <x-icon name="arrow-right" class="size-4" /></a>
        </x-slot:actions>
    </x-page-header>

    @if ($isRevision && $proposal->decision_note)
        <div class="mb-6 rounded-2xl border border-signal-200 bg-signal-50 p-5 text-sm">
            <p class="font-bold text-signal-700">Catatan revisi dari panitia</p>
            <p class="mt-1 whitespace-pre-line text-navy-800">{{ $proposal->decision_note }}</p>
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-[1fr_300px]">
        <form method="POST" action="{{ route('portal.proposals.update', $proposal) }}" id="proposal-form" class="min-w-0 space-y-6">
            @csrf
            @method('PUT')

            {{-- A. Identitas produk --}}
            <section id="identitas" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">A. Identitas produk/teknologi</h2>
                <x-form.input name="product_name" label="Nama produk/teknologi" :value="$proposal->product_name" required />
                <x-form.input name="short_summary" label="Ringkasan satu kalimat" :value="$proposal->short_summary" maxlength="300" help="Maksimal 300 karakter. Jelaskan apa produknya dan masalah apa yang diselesaikan." />
                <div class="rounded-xl border border-navy-100 bg-navy-50/60 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-semibold text-navy-700">Tema:</span> <x-theme-badge :theme="$proposal->theme" />
                            <span class="ml-2 font-semibold text-navy-700">Tantangan:</span> <span>{{ $proposal->challenge?->title ?? '—' }}</span>
                        </div>
                        <a href="{{ route('portal.proposals.challenge', $proposal) }}" class="btn btn-outline btn-sm">Ubah tema/tantangan</a>
                    </div>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="tkt_level" label="Tingkat Kesiapan Teknologi (TKT)" :options="collect(range(1, 9))->mapWithKeys(fn ($n) => [$n => 'TKT '.$n])->all()" :value="$proposal->tkt_level" placeholder="Belum diketahui" help="Isi sesuai kondisi saat ini, bila sudah diukur." />
                    <x-form.select name="prototype_status" label="Status produk/purwarupa saat ini" :options="\App\Support\Options::prototypeStatuses()" :value="$proposal->prototype_status" />
                </div>
            </section>

            {{-- B. Tim --}}
            <section id="tim" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">B. Tim dan institusi</h2>
                <div class="rounded-xl border border-navy-100 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold text-navy-700">Anggota tim ({{ $proposal->team->members->where('status', 'active')->count() }})</p>
                        <a href="{{ route('portal.team') }}" class="text-xs font-semibold text-forest-700">Kelola tim</a>
                    </div>
                    <ul class="mt-2 grid gap-1.5 text-sm sm:grid-cols-2">
                        @foreach ($proposal->team->members->where('status', 'active') as $member)
                            <li><span class="font-semibold">{{ $member->name }}</span> <span class="text-navy-500">· {{ $member->discipline }} · {{ $member->institution }}</span></li>
                        @endforeach
                    </ul>
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-form.input name="contact_name" label="Narahubung" :value="$proposal->contact_name" />
                    <x-form.input name="contact_email" type="email" label="Surel narahubung" :value="$proposal->contact_email" />
                    <x-form.input name="contact_phone" type="tel" label="Telepon narahubung" :value="$proposal->contact_phone" />
                </div>
                <x-form.textarea name="inventors" label="Peneliti/inventor dan peran" :value="$proposal->inventors" :words="$limit" help="Nama, institusi, dan kontribusi utama setiap peneliti/inventor." />
                <x-form.textarea name="previous_work" label="Karya atau pengalaman relevan sebelumnya" :value="$proposal->previous_work" :words="$limit" />
            </section>

            {{-- C. Riset --}}
            <section id="riset" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">C. Latar belakang riset dan pengembangan</h2>
                <div>
                    <p class="form-label">Penelitian terkait yang telah dilakukan</p>
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Tahun</th><th>Judul dan peneliti</th><th>Dana (Rp)</th><th>Sumber dana</th><th class="sr-only">Aksi</th></tr></thead>
                            <tbody>
                                @forelse ($proposal->researchHistories as $research)
                                    <tr>
                                        <td class="whitespace-nowrap">{{ $research->year }}</td>
                                        <td><p class="font-medium">{{ $research->title }}</p><p class="text-xs text-navy-500">{{ $research->researchers }}</p></td>
                                        <td class="whitespace-nowrap tabular-nums">{{ $research->funding_amount !== null ? number_format((float) $research->funding_amount, 0, ',', '.') : '—' }}</td>
                                        <td>{{ $research->funding_source ?: '—' }}</td>
                                        <td><button type="submit" form="del-research-{{ $research->id }}" class="text-xs font-semibold text-red-700" data-confirm-click="Hapus riwayat penelitian ini?">Hapus</button></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-navy-500">Belum ada riwayat penelitian.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <details class="mt-3 rounded-xl border border-dashed border-navy-200 p-4">
                        <summary class="cursor-pointer text-sm font-semibold text-forest-700">+ Tambah riwayat penelitian</summary>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2">
                            <x-form.input name="year" label="Tahun" form="research-form" placeholder="2025 atau 2023–2025" />
                            <x-form.input name="funding_source" label="Sumber dana" form="research-form" />
                            <x-form.input name="title" label="Judul penelitian" form="research-form" class="sm:col-span-2" />
                            <x-form.input name="researchers" label="Peneliti" form="research-form" class="sm:col-span-2" />
                            <x-form.input name="funding_amount" type="number" min="0" step="1000" label="Jumlah dana (Rp)" form="research-form" />
                            <div class="flex items-end"><button type="submit" form="research-form" class="btn btn-outline btn-sm">Tambah ke tabel</button></div>
                        </div>
                    </details>
                </div>
                <x-form.textarea name="existing_results" label="Hasil riset atau bukti purwarupa yang sudah ada" :value="$proposal->existing_results" :words="$limit" />
                <x-form.textarea name="ip_publications" label="Kekayaan intelektual dan publikasi terkait" :value="$proposal->ip_publications" :words="$limit" help="Paten, hak cipta, merek, publikasi, beserta nomor dan tanggalnya bila ada." />
            </section>

            {{-- D. Teknis --}}
            <section id="teknis" class="card card-body scroll-mt-24 space-y-4">
                <div>
                    <h2 class="text-lg font-extrabold">D. Aspek teknis</h2>
                    <p class="text-sm text-navy-500">Maksimal {{ $limit }} kata per isian.</p>
                </div>
                <x-form.textarea name="technical_description" label="Deskripsi teknis" :value="$proposal->technical_description" rows="8" :words="$limit"
                    help="Uraikan proses produksi atau cara kerja, bahan baku/komponen, peralatan, dan kebutuhan tenaga kerja." />
                <x-form.textarea name="technical_validation" label="Validasi yang sudah dilakukan" :value="$proposal->technical_validation" :words="$limit" help="Pengujian fungsi, kinerja, akurasi, atau keamanan yang sudah dilakukan beserta hasilnya." />
                <x-form.textarea name="technical_limitations" label="Keterbatasan teknis utama" :value="$proposal->technical_limitations" :words="$limit" />
            </section>

            {{-- E. Pasar --}}
            <section id="pasar" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">E. Pasar, pengguna, dan dampak</h2>
                <x-form.textarea name="target_users" label="Pengguna sasaran dan penerima manfaat" :value="$proposal->target_users" :words="$limit" />
                <x-form.textarea name="market_description" label="Kebutuhan pasar atau operasional" :value="$proposal->market_description" rows="6" :words="$limit" help="Segmentasi dan ukuran pasar/pengguna, tren, serta strategi memasuki pasar atau adopsi." />
                <x-form.textarea name="competitors" label="Alternatif yang ada dan kompetitor" :value="$proposal->competitors" :words="$limit" />
                <x-form.textarea name="value_proposition" label="Nilai tambah (value proposition)" :value="$proposal->value_proposition" :words="$limit" />
                <x-form.textarea name="market_validation" label="Metode validasi pasar/pengguna" :value="$proposal->market_validation" :words="$limit" help="Contoh: desain survei (responden, jumlah sampel, metode sampling), analisis kompetitor, studi kesediaan membayar, FGD." />
                <x-form.textarea name="impact_description" label="Dampak yang diharapkan" :value="$proposal->impact_description" :words="$limit" help="Nyatakan secara terukur: efisiensi, produktivitas, mutu, akurasi, kecepatan layanan, keselamatan, atau akses." />
                <x-form.textarea name="adoption_plan" label="Rencana adopsi atau implementasi" :value="$proposal->adoption_plan" :words="$limit" />
            </section>

            {{-- F. Kesesuaian tantangan --}}
            <section id="kesesuaian" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">F. Kesesuaian dengan tantangan</h2>
                <x-form.textarea name="problem_statement" label="Masalah yang dijawab" :value="$proposal->problem_statement" :words="$limit" />
                <x-form.textarea name="challenge_importance" label="Mengapa masalah ini penting" :value="$proposal->challenge_importance" :words="$limit" />
                <x-form.textarea name="solution_description" label="Usulan solusi" :value="$proposal->solution_description" rows="6" :words="$limit" />
                <x-form.textarea name="minimum_functions" label="Fungsi minimum yang akan dipenuhi" :value="$proposal->minimum_functions" :words="$limit" />
                <x-form.textarea name="success_indicators" label="Indikator keberhasilan" :value="$proposal->success_indicators" :words="$limit" help="Target terukur, misalnya waktu uji turun dari 3 hari menjadi 1 jam, atau akurasi deteksi ≥ 90%." />
                <x-form.textarea name="data_facility_needs" label="Kebutuhan data dan fasilitas" :value="$proposal->data_facility_needs" :words="$limit" />
                <x-form.textarea name="pilot_location" label="Lokasi pilot atau calon pengguna" :value="$proposal->pilot_location" :words="$limit" />
            </section>

            {{-- G. Kesiapan --}}
            <section id="kesiapan" class="card card-body scroll-mt-24 space-y-4">
                <div>
                    <h2 class="text-lg font-extrabold">G. Kesiapan legal, keselamatan, etik, dan data</h2>
                    <p class="mt-1 rounded-xl bg-navy-50 px-4 py-3 text-sm text-navy-600">Bagian ini mencatat kesiapan dan isu untuk ditelaah pakar. Isian ini <strong>bukan</strong> pernyataan bahwa produk telah patuh hukum.</p>
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="ip_status" label="Status kekayaan intelektual" :options="\App\Support\Options::ipStatuses()" :value="$proposal->ip_status" />
                    <x-form.select name="regulatory_status" label="Status izin/registrasi/standar" :options="\App\Support\Options::regulatoryStatuses()" :value="$proposal->regulatory_status" />
                </div>
                <x-form.textarea name="ip_notes" label="Potensi isu KI dan kepemilikan" :value="$proposal->ip_notes" :words="$limit" help="Kepemilikan KI, kontributor (termasuk mahasiswa), dan potensi bersinggungan dengan KI pihak lain." />
                <x-form.textarea name="regulatory_notes" label="Izin, registrasi, standar, atau sertifikasi yang diperlukan" :value="$proposal->regulatory_notes" :words="$limit" />
                <x-form.textarea name="third_party_dependencies" label="Ketergantungan pada hak atau kontrak pihak ketiga" :value="$proposal->third_party_dependencies" :words="$limit" help="Misalnya lisensi, komponen bermerek, kultur/bahan dari pihak lain, atau hibah sebelumnya." />
                <x-form.textarea name="safety_risks" label="Risiko keselamatan pengguna" :value="$proposal->safety_risks" :words="$limit" />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select name="data_risk_status" label="Keterlibatan data pribadi/kesehatan" :options="\App\Support\Options::dataRiskStatuses()" :value="$proposal->data_risk_status" />
                    <x-form.select name="ethics_status" label="Kebutuhan persetujuan etik" :options="\App\Support\Options::ethicsStatuses()" :value="$proposal->ethics_status" />
                </div>
                <x-form.textarea name="data_notes" label="Pengelolaan dan perlindungan data" :value="$proposal->data_notes" :words="$limit" />
                <x-form.textarea name="ethics_notes" label="Catatan etik" :value="$proposal->ethics_notes" :words="$limit" />
                <x-form.textarea name="environmental_risks" label="Risiko lingkungan atau operasional" :value="$proposal->environmental_risks" :words="$limit" />
                <x-form.textarea name="mitigation_plan" label="Rencana mitigasi" :value="$proposal->mitigation_plan" :words="$limit" />
            </section>

            {{-- H. Berkas dan tautan --}}
            <section id="tautan" class="card card-body scroll-mt-24 space-y-4">
                <h2 class="text-lg font-extrabold">H. Berkas dan tautan pendukung</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.input name="demo_video_url" type="url" label="Tautan video demo (opsional)" :value="$proposal->demo_video_url" placeholder="https://" />
                    <x-form.input name="repository_url" type="url" label="Tautan repositori (opsional)" :value="$proposal->repository_url" placeholder="https://" />
                </div>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-navy-100 bg-navy-50/60 p-4 text-sm">
                    <p>Gambar produk, proposal resmi (PDF), lampiran teknis, dan bukti pendukung diunggah di halaman Berkas. Semua berkas bersifat privat.</p>
                    <button type="submit" name="next" value="files" class="btn btn-outline btn-sm">Simpan & Unggah Berkas</button>
                </div>
            </section>

            @if ($questions->isNotEmpty())
                <section id="tema-khusus" class="card card-body scroll-mt-24 space-y-4">
                    <h2 class="text-lg font-extrabold">Pertanyaan khusus tema {{ $proposal->theme?->name }}</h2>
                    @foreach ($questions as $question)
                        <x-form.textarea :name="'answers['.$question->id.']'" :label="$question->question" :value="$answers[$question->id] ?? null" :help="$question->help_text" :required="false" :words="$question->word_limit ?: $limit" />
                        @if ($question->is_required)<p class="-mt-3 text-xs font-semibold text-signal-700">Wajib diisi sebelum dikirim.</p>@endif
                    @endforeach
                </section>
            @endif

            <div class="sticky bottom-0 z-10 -mx-4 flex flex-wrap items-center justify-between gap-3 border-t border-navy-100 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
                <p class="text-xs text-navy-500">Perubahan belum tersimpan sampai Anda menekan tombol simpan.</p>
                <div class="flex gap-2">
                    <button type="submit" class="btn btn-dark"><x-icon name="check" class="size-4" /> Simpan sebagai Draft</button>
                </div>
            </div>
        </form>

        <aside class="space-y-4 xl:sticky xl:top-24 xl:self-start">
            <div class="card card-body">
                <div class="flex items-center justify-between text-sm"><span class="font-bold">Kelengkapan</span><span class="font-bold tabular-nums">{{ $percent }}%</span></div>
                <div class="progress mt-2"><span style="width: {{ $percent }}%"></span></div>
                <ul class="mt-4 space-y-2">
                    @foreach ($checklist as $item)
                        <li>
                            <a href="#{{ $item['section'] }}" class="flex items-start gap-2 text-sm hover:text-forest-700">
                                <x-icon :name="$item['done'] ? 'check-circle' : 'circle'" class="mt-0.5 size-4 {{ $item['done'] ? 'text-forest-600' : 'text-navy-300' }}" />
                                <span class="{{ $item['done'] ? 'text-navy-700' : 'text-navy-500' }}">{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <nav class="card card-body hidden xl:block" aria-label="Bagian proposal">
                <p class="eyebrow text-navy-400">Bagian</p>
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($sections as $anchor => $label)
                        <li><a href="#{{ $anchor }}" class="block rounded-lg px-2 py-1.5 text-navy-600 hover:bg-navy-50 hover:text-navy-950">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </nav>
        </aside>
    </div>

    {{-- Formulir terpisah (tidak bersarang) untuk riwayat penelitian. --}}
    <form method="POST" action="{{ route('portal.proposals.research.store', $proposal) }}" id="research-form" class="hidden">@csrf</form>
    @foreach ($proposal->researchHistories as $research)
        <form method="POST" action="{{ route('portal.proposals.research.destroy', [$proposal, $research]) }}" id="del-research-{{ $research->id }}" class="hidden">@csrf @method('DELETE')</form>
    @endforeach
</x-layouts.portal>
