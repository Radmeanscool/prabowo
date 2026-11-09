# Panduan Deployment DEEPNEST di Shared Hosting (cPanel / Biz Corporate)

Panduan ini untuk hosting cPanel biasa: PHP + MySQL/MariaDB + Apache, **tanpa** Docker, Redis,
queue worker, atau Node.js di server. Dua jalur disediakan: **dengan SSH** dan **tanpa SSH**
(hanya File Manager dan phpMyAdmin).

## 1. Persyaratan server

| Kebutuhan | Nilai |
| --- | --- |
| PHP | **8.3 atau lebih baru** (Laravel 13). Atur di cPanel → *Select PHP Version* / *MultiPHP Manager* |
| Ekstensi PHP | `pdo_mysql`, `mbstring`, `openssl`, `fileinfo`, `tokenizer`, `xml`, `ctype`, `json`, `gd` (untuk membersihkan metadata gambar), `zip` (opsional) |
| Basis data | MySQL 8.0+ atau MariaDB 10.6+ (utf8mb4) |
| Web server | Apache dengan `mod_rewrite` (standar cPanel) |
| Batas unggah | `upload_max_filesize` dan `post_max_size` ≥ 12M (atau sesuai pengaturan ukuran PDF di Admin) |
| Cron | **Tidak diperlukan** |

Setelah terpasang, jalankan pemeriksaan otomatis (bila ada SSH): `php artisan deepnest:check`.

## 2. Siapkan paket aplikasi

**Pilihan A — paket rilis (disarankan bila server tidak punya Composer).** Di komputer lokal:

```bash
bash scripts/build-release.sh
```

Hasilnya `deepnest-release.zip` berisi folder `deepnest/` lengkap dengan `vendor/` produksi dan
aset `public/build/` yang sudah dibangun.

**Pilihan B — via SSH di server** (bila tersedia `git` dan `composer`):

```bash
git clone https://github.com/Radmeanscool/prabowo.git ~/deepnest
```

```bash
cd ~/deepnest && composer install --no-dev --optimize-autoloader
```

Aset `public/build/` sudah ikut di repositori, jadi Node.js tidak diperlukan di server.

## 3. Letak folder (penting untuk keamanan)

Jangan pernah menaruh `.env`, kode sumber, atau `storage/` di dalam `public_html`.

**Cara 1 — document root ke `public/` (dianjurkan).**

1. Unggah dan ekstrak paket ke `/home/USER/deepnest` (di luar `public_html`).
2. cPanel → *Domains* (atau *Subdomains*) → ubah **Document Root** domain/subdomain
   (mis. `deepnest.unhas.ac.id`) menjadi `/home/USER/deepnest/public`.

**Cara 2 — document root tidak dapat diubah.**

1. Ekstrak paket ke `/home/USER/deepnest` (di luar `public_html`).
2. Salin **isi** folder `deepnest/public/` (termasuk `.htaccess`, `index.php`, `build/`, `favicon.svg`,
   `robots.txt`) ke `public_html/`.
3. Sunting `public_html/index.php`, ganti tiga jalur:

```php
if (file_exists($maintenance = __DIR__.'/../deepnest/storage/framework/maintenance.php')) {
require __DIR__.'/../deepnest/vendor/autoload.php';
$app = require_once __DIR__.'/../deepnest/bootstrap/app.php';
```

4. Setiap kali memperbarui aset (`public/build`), salin ulang ke `public_html/build`.

> Folder proyek juga berisi `.htaccess` di akar yang mengarahkan semua permintaan ke `public/`.
> Ini hanya **pengaman cadangan** bila folder terlanjur diunggah ke `public_html`; jangan diandalkan.

## 4. Basis data

1. cPanel → *MySQL® Databases*: buat database (mis. `USER_deepnest`), buat user, beri **ALL PRIVILEGES**.
2. Isi tabel dengan salah satu cara:
   - **Tanpa SSH:** phpMyAdmin → pilih database kosong → *Import* → unggah
     `database/deepnest-install.sql` (skema + data rujukan: tema, 11 tahapan, 2 fase & 18 kriteria
     penilaian, 11 mitra *tidak publik*, 9 draf tantangan dari FGD 1, agenda resmi — hanya FGD 1 yang publik, butir rencana tersimpan
     sebagai draf — halaman, FAQ).
     Tidak berisi akun maupun data demo.
   - **Dengan SSH:** `php artisan migrate --seed --force`.

## 5. Berkas `.env`

Salin `.env.example` menjadi `.env` (di `/home/USER/deepnest/.env`), lalu isi:

- `APP_URL` = alamat situs lengkap dengan `https://`.
- `APP_KEY`: dengan SSH jalankan `php artisan key:generate`. Tanpa SSH, jalankan perintah yang sama
  di komputer lokal dan salin nilai `APP_KEY=base64:...` ke `.env` server.
- `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` sesuai langkah 4.
- Tetap `APP_ENV=production` dan **`APP_DEBUG=false`**.
- `SESSION_SECURE_COOKIE=true` bila situs memakai HTTPS (dianjurkan; pasang SSL gratis via cPanel *SSL/TLS Status*).
- Surel (opsional): isi `MAIL_*` dengan akun surel cPanel, lalu aktifkan notifikasi di Admin → Pengaturan.

Izin berkas: `.env` 640 atau 600; folder `storage/` dan `bootstrap/cache/` 755 (atau 775 bila PHP berjalan sebagai grup lain).

## 6. Admin pertama

- **Tanpa SSH:** isi `SETUP_TOKEN=` dengan teks acak panjang di `.env`, buka `https://domain/setup`,
  masukkan token dan data admin. Setelah berhasil, **kosongkan kembali** `SETUP_TOKEN`.
  Halaman `/setup` otomatis tidak tersedia (404) begitu ada admin atau token kosong.
- **Dengan SSH:** `php artisan deepnest:create-admin admin@domain.id --name="Admin DEEPNEST"`.

Buat akun reviewer/juri dari Admin → Pengguna → Tambah akun (peran *Reviewer/Juri*). Peserta mendaftar sendiri.

## 7. Cache produksi (setiap selesai deploy/pembaruan, bila ada SSH)

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

Tanpa SSH, aplikasi tetap berjalan tanpa cache ini (hanya sedikit lebih lambat). Bila `.env` diubah
setelah `config:cache`, jalankan ulang `php artisan config:cache` (atau hapus `bootstrap/cache/config.php`).

## 8. Penyimpanan berkas privat

- Semua unggahan (proposal PDF, gambar, bukti, dokumen) disimpan di `storage/app/private/` dengan nama acak.
- Berkas **tidak** disajikan langsung oleh web server; semuanya lewat controller yang memeriksa hak akses
  (peserta → tim sendiri, reviewer → proposal yang ditugaskan, admin → semua, publik → hanya gambar produk
  publik dan PDF yang dibuka admin).
- **Tidak perlu** `php artisan storage:link`.

## 9. Pengaturan awal oleh admin

Admin → **Pengaturan**: jadwal/mode Open Call (otomatis, paksa buka, paksa tutup), aturan tim
(bawaan maks. 6 orang sesuai RAB), batas kata (bawaan 300), ukuran berkas, review buta, tampilan skor ke
peserta, notifikasi surel, statistik beranda. Kemudian:

1. Admin → **Mitra Challenge Owner**: aktifkan *Tampilkan publik* hanya setelah ada izin mitra.
2. Admin → **Tantangan**: lengkapi 9 draf hasil FGD 1, ubah status ke *Terbit* setelah FGD 2.
3. Admin → **Mentor & Juri**: isi profil terkonfirmasi; hubungkan juri ke akun reviewer.
4. Admin → **Pengumuman**: tinjau draf berita FGD lalu terbitkan.

## 10. Cadangan dan pemulihan

**Cadangan rutin** (mingguan selama Open Call, harian saat penilaian):

1. Basis data: phpMyAdmin → *Export* → *Quick* → SQL, atau `mysqldump -u USER -p USER_deepnest > deepnest-$(date +%F).sql`.
2. Berkas: unduh/arsipkan folder `storage/app/private/` (berisi semua berkas proposal) dan berkas `.env`.
3. Atau gunakan cPanel → *Backup* / *JetBackup* (full account backup) bila tersedia.

**Pemulihan:** impor SQL ke database kosong, pulihkan `storage/app/private/` dan `.env`, lalu jalankan
ulang langkah 7. Simpan cadangan di lokasi terpisah yang aksesnya terbatas karena berisi data pribadi dan rahasia.

## 11. Memperbarui aplikasi

1. Aktifkan mode pemeliharaan (SSH): `php artisan down`.
2. Ganti kode (git pull atau unggah paket rilis baru; **jangan timpa** `.env` dan `storage/`).
3. `composer install --no-dev --optimize-autoloader` (atau gunakan `vendor/` dari paket rilis).
4. `php artisan migrate --force`.
5. Jalankan cache produksi (langkah 7), lalu `php artisan up`.

## 12. Pemecahan masalah

| Gejala | Solusi |
| --- | --- |
| Halaman putih / galat 500 | Periksa `storage/logs/laravel-*.log`; pastikan PHP 8.3+, `APP_KEY` terisi, izin `storage/` dapat ditulis |
| 404 untuk semua halaman kecuali beranda | `mod_rewrite`/`.htaccess` tidak aktif; pastikan `public/.htaccess` ikut terunggah |
| Tampilan tanpa gaya | Folder `public/build/` belum terunggah (Cara 2: salin ke `public_html/build`) |
| Unggahan gagal untuk berkas besar | Naikkan `upload_max_filesize`/`post_max_size` di *MultiPHP INI Editor* |
| Login selalu kembali ke halaman masuk | `SESSION_SECURE_COOKIE=true` tetapi situs diakses via `http://`; pasang SSL atau set `false` sementara |
| Surel tidak terkirim | Periksa `MAIL_*`; kegagalan surel dicatat di log dan tidak menghentikan alur aplikasi |
