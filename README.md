# DEEPNEST — Portal Hackathon Deeptech Universitas Hasanuddin

**Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies**
*Dari Tantangan Nyata Menuju Inovasi Berdampak · Dari Indonesia Timur untuk Nusantara*

Situs publik dan portal pendaftaran DEEPNEST: hackathon deep technology 12 bulan bertema
**Ketahanan Pangan dan Kesehatan** (tema proposal: Ketahanan Pangan, Kesehatan, Hybrid).
Dibangun dengan **Laravel 13 + MySQL/MariaDB** dan siap dipasang di shared hosting
(cPanel, misalnya Biz Corporate) tanpa Supabase, Docker, Redis, queue worker, atau Node.js di server.

| Dokumen | Isi |
| --- | --- |
| [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) | Panduan pemasangan di cPanel (dengan/tanpa SSH), impor phpMyAdmin, keamanan, cache, cadangan, pembaruan |
| [docs/ARSITEKTUR.md](docs/ARSITEKTUR.md) | Diagram relasi data, matriks hak akses, transisi status, aturan publikasi, data publik vs privat |
| [docs/QA-CHECKLIST.md](docs/QA-CHECKLIST.md) | Daftar uji manual per peran dan pemetaan ke uji otomatis |
| [docs/KEPUTUSAN-PANITIA.md](docs/KEPUTUSAN-PANITIA.md) | Keputusan yang masih perlu dikonfirmasi panitia, asumsi, dan fitur lanjutan |

---

## Fitur yang sudah diimplementasikan

**Situs publik** (bahasa Indonesia, responsif): Beranda editorial (hero, tema, tantangan, mitra,
4 fase program, target luaran, mentor & juri, etalase produk, pengumuman, agenda, ajakan daftar),
Tentang, Tantangan (+ detail), Mitra Challenge Owner, Tahapan Program, Linimasa, Agenda,
Mentor & Juri, Produk Disetujui (+ detail dan penyaring), Pengumuman, Sumber Daya, Ketentuan, FAQ,
Privasi, Syarat, Daftar, Masuk, Lupa kata sandi. Semua konten dapat dikelola admin dan
**hanya informasi terkonfirmasi** yang tampil.

**Tiga dashboard utama**

1. **Dashboard Peserta** — profil, tim (buat/gabung dengan kode, persetujuan ketua, anggota tanpa akun),
   alur *pilih tema → lihat tantangan terkait → pilih tantangan → lengkapi proposal*, formulir proposal
   terstruktur (A–H: identitas, tim, riset, teknis, pasar & dampak, kesesuaian tantangan,
   kesiapan legal/keselamatan/etik/data, berkas & tautan) dengan penghitung kata, simpan draf,
   daftar periksa kelengkapan, unggah berkas privat, layar konfirmasi akhir, status & riwayat versi.
2. **Dashboard Reviewer/Juri** — hanya proposal yang **ditugaskan admin**, rubrik per fase dengan
   panduan, nilai per kriteria + komentar, simpan draf, kirim final (terkunci), skor tertimbang dihitung
   server, rekomendasi, pernyataan konflik kepentingan. Pemilihan mandiri reviewer **nonaktif** secara bawaan.
3. **Dashboard Admin** — metrik program, pengguna & peran, tim & mentor, tema, tantangan, mitra,
   proposal (penyaring tema/tantangan/mitra/status/kelengkapan/penugasan), penugasan reviewer
   (tunggal, massal, beban kerja, cabut/ganti, deteksi konflik), penilaian (buka kembali), keputusan
   status, produk disetujui (ringkasan publik, sembunyikan/terbitkan, PDF publik), rubrik & kriteria,
   tahapan, agenda, pengumuman, dokumen, halaman & FAQ, ekspor CSV, log audit, pengaturan.

**Aturan inti**

- Keputusan admin **Setujui** → ringkasan produk **otomatis tampil** di katalog publik (`is_public`,
  `published_at`). Tidak ada langkah "terbitkan" kedua.
- PDF proposal **tetap privat** kecuali admin mengaktifkan `public_pdf_enabled`.
- Admin dapat **Cabut Publikasi** tanpa menghapus proposal, penilaian, atau log audit; URL publik menjadi 404.
- Reviewer tidak pernah mengubah status/publikasi. Juri yang membimbing tim otomatis diblokir menilai tim itu.
- Semua berkas disimpan di `storage/app/private` dengan nama acak dan disajikan lewat controller
  berotorisasi; jenis berkas diperiksa dari **isi** berkas; akses berkas sensitif tercatat.
- Pengiriman, pembukaan kembali, revisi, persetujuan, penolakan, publikasi, penugasan, penilaian,
  perubahan peran, dan perubahan pengaturan **tercatat di log audit**.

---

## Menjalankan di komputer lokal

Prasyarat: PHP 8.3+, Composer, Node.js 20+, MySQL/MariaDB (atau SQLite untuk uji cepat).

```bash
composer install
```

```bash
cp .env.example .env
```

Ubah `.env` untuk lokal: `APP_ENV=local`, `APP_DEBUG=true`, `APP_URL=http://127.0.0.1:8000`,
`SESSION_SECURE_COOKIE=false`, `DEEPNEST_CSP=false` (bila memakai `npm run dev`), dan isi kredensial database.

```bash
php artisan key:generate
```

```bash
php artisan migrate --seed
```

```bash
npm install && npm run build
```

```bash
php artisan serve
```

Data contoh untuk mencoba semua peran (**jangan dijalankan di produksi**; seeder menolak berjalan bila `APP_ENV=production`):

```bash
php artisan migrate:fresh && php artisan db:seed --class=DemoSeeder
```

Akun demo (kata sandi `Deepnest2026`): `admin@deepnest.test`, `reviewer1@deepnest.test`…`reviewer3@deepnest.test`,
`peserta1@deepnest.test`…`peserta8@deepnest.test`.

## Pengujian

```bash
php artisan test
```

83 uji otomatis (564 asersi) mencakup otorisasi per peran, eskalasi peran, alur tim, alur proposal,
privasi berkas (jenis berkas palsu, ukuran berlebih, URL tanpa hak), penugasan & konflik kepentingan,
perhitungan skor, keputusan, publikasi otomatis, PDF publik, cabut publikasi, keamanan Markdown,
header keamanan, serta uji asap yang merender setiap halaman setiap peran. Seluruh uji lulus pada
SQLite dan MariaDB 10.11.

## Paket rilis untuk hosting tanpa Composer

```bash
bash scripts/build-release.sh
```

Menghasilkan `deepnest-release.zip` (kode + `vendor` produksi + aset terbangun, tanpa `.env`, tes, dan `node_modules`).
Lihat [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md).

## Struktur singkat

```text
app/Enums            Peran pengguna dan status proposal (transisi yang diizinkan)
app/Services         ProposalWorkflow (status + publikasi otomatis), ScoreCalculator, ConflictChecker,
                     SecureFileStore, ProposalChecklist, Audit, Notifier
app/Policies         Otorisasi proposal, berkas, tim
app/Http/Controllers PublicSite/, Portal/ (peserta), Reviewer/, Admin/, Auth/
database/            Migrasi, seeder data rujukan, DemoSeeder, deepnest-install.sql (impor phpMyAdmin)
resources/views      public/, portal/, reviewer/, admin/, proposals/ (tampilan bersama), components/
docs/                Dokumentasi deployment, arsitektur, QA, dan keputusan panitia
```
