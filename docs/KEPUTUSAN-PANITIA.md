# Keputusan Panitia, Asumsi, dan Fitur Lanjutan

## 1. Keputusan yang masih perlu dikonfirmasi panitia

Semua butir di bawah sudah memiliki **pengaturan atau data yang dapat diubah admin**, sehingga cukup
diisi setelah diputuskan — tidak perlu mengubah kode.

| # | Keputusan | Nilai bawaan di sistem | Tempat mengubah |
| --- | --- | --- | --- |
| 1 | Jumlah maksimal anggota tim | 6 orang (sesuai RAB) | Admin → Pengaturan |
| 2 | Wajib memilih tantangan spesifik atau cukup tema | Wajib memilih tantangan | Admin → Pengaturan |
| 3 | Daftar final dan rumusan tantangan | 9 **draf** dari notulensi FGD 1 (tidak publik) | Admin → Tantangan |
| 4 | Boleh lebih dari satu proposal per tim | 1 proposal per tim | Admin → Pengaturan |
| 5 | Isian, batas kata, dan lampiran wajib | Bagian A–H; batas 300 kata; wajib gambar + PDF | Admin → Pengaturan, Pertanyaan khusus tema |
| 6 | Kartu produk publik menampilkan nama tim/institusi | Tidak ditampilkan (dapat diaktifkan per produk) | Admin → Pengaturan / Produk |
| 7 | PDF asli boleh publik | Privat; dibuka per produk oleh admin | Admin → Produk Disetujui |
| 8 | Fase penilaian dan rubrik final | 2 fase, 9+9 kriteria contoh (bobot 100%) | Admin → Rubrik & Kriteria |
| 9 | Review buta (identitas tim disembunyikan dari reviewer) | Tidak | Admin → Pengaturan |
| 10 | Mentor memakai portal | Mentor sebagai direktori + penugasan ke tim; juri memakai akun reviewer | Admin → Mentor & Juri, Tim |
| 11 | Tanggal program dan tenggat Open Call | Belum diisi; Open Call tertutup | Admin → Pengaturan, Tahapan, Agenda |
| 12 | Nama/logo mitra boleh tampil publik | Semua mitra tersembunyi | Admin → Mitra Challenge Owner |
| 13 | Skor dan komentar reviewer terlihat peserta | Tidak | Admin → Pengaturan |
| 14 | Format ekspor | CSV (UTF-8, pemisah koma atau titik koma untuk Excel) | — (XLSX/PDF: fitur lanjutan) |
| 15 | Notifikasi surel di rilis pertama | Tersedia, nonaktif | Admin → Pengaturan + `MAIL_*` di `.env` |
| 16 | Konfirmasi HIPELKI sebagai mitra | "Menunggu konfirmasi", tidak publik | Admin → Mitra Challenge Owner |
| 17 | Identitas mitra penyedia teknologi drone (FGD 1) | Draf tantangan drone tanpa mitra | Admin → Tantangan |
| 18 | Kontak resmi di situs | Kontak kop surat Direktorat Inovasi dan KI Unhas | Admin → Pengaturan |

## 2. Asumsi yang dipakai

- Sumber kebenaran: proposal DEEPNEST (11 September 2026), KAK dan undangan FGD Challenge Discovery,
  berita acara, notulensi dan hasil FGD 1 (1–2 November 2026), RAB, infografik alur program, format usulan
  produk, dan materi/daftar periksa aspek legal.
- Laravel 13 memerlukan **PHP 8.3+** (PHP 8.2 berakhir masa dukungannya akhir 2026).
- Domain rencana `deepnest.unhas.ac.id` (tercantum di RAB/KAK) dipakai sebagai contoh `APP_URL`.
- Hanya ketua tim yang mengirim proposal; semua anggota aktif dapat menyunting draf.
- Pengiriman pertama memerlukan Open Call terbuka; pengajuan revisi yang diminta admin tetap dapat dikirim
  setelah Open Call ditutup.
- Proposal Hybrid dapat memilih tantangan pangan maupun kesehatan (diatur per tantangan lewat
  "Tampilkan untuk proposal bertema").
- Mentor tidak wajib memiliki akun; juri yang menilai lewat portal memakai akun reviewer dan dapat
  dihubungkan ke profil direktori agar konflik "juri membimbing tim" terdeteksi.
- Statistik beranda bersumber dari dokumen resmi dan diberi keterangan *target*/*rencana*.
- Konten daftar periksa visitasi legal bersifat RAHASIA: tidak dimasukkan ke situs; sistem menyediakan
  unggahan "telaah internal" khusus admin per proposal.

## 3. Fitur lanjutan (opsional)

- Ekspor XLSX/PDF dan laporan rekap otomatis untuk Kemdiktisaintek.
- Login mentor dengan logbook pendampingan dan evaluasi target mingguan per stage gate.
- Portal mitra (challenge owner) untuk mengusulkan dan memvalidasi tantangan secara daring.
- Versi bahasa Inggris situs publik.
- Verifikasi surel saat pendaftaran dan autentikasi dua faktor untuk admin.
- Tanda tangan elektronik untuk pernyataan/pakta integritas.
- Pencadangan otomatis terjadwal (memerlukan cron).
