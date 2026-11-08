<x-layouts.portal title="Pengaturan">
    <x-page-header title="Pengaturan" subtitle="Aturan operasional yang dapat diubah tanpa mengubah kode. Setiap perubahan tercatat di log audit." />

    @php
        $s = $settings;
        $dt = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : null;
        $stats = array_pad($s['homepage_stats'] ?? [], 8, ['value' => '', 'label' => '', 'note' => '']);
    @endphp

    <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        <section class="card card-body">
            <h2 class="card-title">Open Call dan pendaftaran</h2>
            <p class="mt-1 text-sm text-navy-500">Mode otomatis membuka pengiriman proposal hanya di antara tanggal mulai dan tutup. Draf tetap dapat disimpan kapan saja.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-form.select name="open_call_mode" label="Mode Open Call" :options="['auto' => 'Otomatis sesuai jadwal', 'open' => 'Paksa dibuka', 'closed' => 'Paksa ditutup']" :value="$s['open_call_mode']" :placeholder="false" required />
                <x-form.input name="open_call_start_at" type="datetime-local" label="Dibuka (WITA)" :value="$dt($s['open_call_start_at'])" />
                <x-form.input name="open_call_end_at" type="datetime-local" label="Ditutup (WITA)" :value="$dt($s['open_call_end_at'])" />
            </div>
            <div class="mt-4">
                <x-form.checkbox name="registration_enabled" label="Pendaftaran akun peserta dibuka" :checked="$s['registration_enabled']" />
            </div>
        </section>

        <section class="card card-body">
            <h2 class="card-title">Aturan tim dan proposal</h2>
            <p class="mt-1 text-sm text-navy-500">Nilai bawaan mengikuti RAB (6 orang per tim). Konfirmasi ketentuan final dengan panitia.</p>
            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <x-form.input name="max_team_size" type="number" min="1" max="20" label="Maksimal anggota tim" :value="$s['max_team_size']" required />
                <x-form.input name="min_team_size" type="number" min="1" max="20" label="Minimal anggota aktif untuk mengirim" :value="$s['min_team_size']" required />
                <x-form.input name="max_proposals_per_team" type="number" min="1" max="10" label="Maksimal proposal per tim" :value="$s['max_proposals_per_team']" required />
                <x-form.input name="proposal_word_limit" type="number" min="50" max="2000" label="Batas kata per isian" :value="$s['proposal_word_limit']" required />
                <x-form.input name="max_pdf_size_mb" type="number" min="1" max="64" label="Ukuran maks. PDF (MB)" :value="$s['max_pdf_size_mb']" required help="Pastikan upload_max_filesize di hosting tidak lebih kecil." />
                <x-form.input name="max_image_size_mb" type="number" min="1" max="20" label="Ukuran maks. gambar (MB)" :value="$s['max_image_size_mb']" required />
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <x-form.checkbox name="one_team_per_participant" label="Satu peserta hanya boleh di satu tim" :checked="$s['one_team_per_participant']" />
                <x-form.checkbox name="team_leader_approval_required" label="Bergabung ke tim memerlukan persetujuan ketua" :checked="$s['team_leader_approval_required']" />
                <x-form.checkbox name="require_challenge_selection" label="Peserta wajib memilih tantangan" :checked="$s['require_challenge_selection']" help="Bila dimatikan, peserta cukup memilih tema." />
            </div>
        </section>

        <section class="card card-body">
            <h2 class="card-title">Penilaian</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <x-form.checkbox name="reviewer_self_selection_enabled" label="Reviewer boleh memilih proposal sendiri" :checked="$s['reviewer_self_selection_enabled']" help="Bawaan: nonaktif. Penugasan dilakukan admin." />
                <x-form.checkbox name="blind_review" label="Review buta (sembunyikan identitas tim dari reviewer)" :checked="$s['blind_review']" />
                <x-form.checkbox name="show_scores_to_participants" label="Tampilkan skor ke peserta" :checked="$s['show_scores_to_participants']" help="Identitas reviewer tetap disembunyikan." />
                <x-form.checkbox name="show_reviewer_comments_to_participants" label="Tampilkan komentar umum reviewer ke peserta" :checked="$s['show_reviewer_comments_to_participants']" help="Catatan pribadi reviewer tidak pernah ditampilkan." />
            </div>
        </section>

        <section class="card card-body">
            <h2 class="card-title">Publikasi dan notifikasi</h2>
            <div class="mt-4 grid gap-3 sm:grid-cols-2">
                <x-form.checkbox name="publication_show_team_default" label="Tampilkan nama tim/institusi pada produk yang baru disetujui" :checked="$s['publication_show_team_default']" help="Dapat diubah per produk." />
                <x-form.checkbox name="email_notifications_enabled" label="Kirim notifikasi surel" :checked="$s['email_notifications_enabled']" help="Butuh konfigurasi MAIL_* di .env. Dikirim langsung tanpa queue worker." />
            </div>
        </section>

        <section class="card card-body">
            <h2 class="card-title">Identitas dan kontak</h2>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <x-form.input name="site_full_name" label="Nama lengkap program" :value="$s['site_full_name']" required class="sm:col-span-2" />
                <x-form.input name="tagline" label="Tagline" :value="$s['tagline']" required />
                <x-form.input name="tagline_en" label="Tagline (Inggris)" :value="$s['tagline_en']" />
                <x-form.input name="spirit" label="Semangat" :value="$s['spirit']" />
                <x-form.input name="event_period" label="Periode (tampil di beranda)" :value="$s['event_period']" help="Isi tanggal hanya bila sudah pasti." />
                <x-form.input name="event_location" label="Lokasi" :value="$s['event_location']" />
                <x-form.input name="contact_email" type="email" label="Surel kontak" :value="$s['contact_email']" />
                <x-form.input name="contact_phone" label="Telepon kontak" :value="$s['contact_phone']" />
                <x-form.input name="contact_address" label="Alamat" :value="$s['contact_address']" />
                <x-form.textarea name="hero_intro" label="Paragraf pembuka beranda" :value="$s['hero_intro']" rows="3" class="sm:col-span-2" />
            </div>
        </section>

        <section class="card card-body">
            <h2 class="card-title">Statistik beranda</h2>
            <p class="mt-1 text-sm text-navy-500">Tampilkan hanya angka yang terkonfirmasi atau beri keterangan "target"/"rencana". Kosongkan baris untuk menghapus.</p>
            <div class="mt-4 space-y-3">
                @foreach ($stats as $i => $row)
                    <div class="grid gap-3 sm:grid-cols-[120px_1fr_2fr]">
                        <input name="stats[{{ $i }}][value]" value="{{ old('stats.'.$i.'.value', $row['value'] ?? '') }}" class="form-input" placeholder="Nilai" aria-label="Nilai statistik {{ $i + 1 }}">
                        <input name="stats[{{ $i }}][label]" value="{{ old('stats.'.$i.'.label', $row['label'] ?? '') }}" class="form-input" placeholder="Label" aria-label="Label statistik {{ $i + 1 }}">
                        <input name="stats[{{ $i }}][note]" value="{{ old('stats.'.$i.'.note', $row['note'] ?? '') }}" class="form-input" placeholder="Keterangan" aria-label="Keterangan statistik {{ $i + 1 }}">
                    </div>
                @endforeach
            </div>
        </section>

        <div class="sticky bottom-0 -mx-4 border-t border-navy-100 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8">
            <button class="btn btn-dark">Simpan Pengaturan</button>
        </div>
    </form>
</x-layouts.portal>
