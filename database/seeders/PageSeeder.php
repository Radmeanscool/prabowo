<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

/**
 * Halaman statis yang dapat disunting admin (Markdown). Isi awal hanya memuat
 * informasi yang tercantum di dokumen resmi DEEPNEST.
 */
class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            'tentang' => [
                'title' => 'Tentang DEEPNEST',
                'summary' => 'Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies: hackathon deep technology Universitas Hasanuddin bertema Ketahanan Pangan dan Kesehatan.',
                'body' => <<<'MD'
## Mengapa DEEPNEST

Permasalahan ketahanan pangan dan kesehatan di Indonesia Timur semakin kompleks akibat kondisi geografis kepulauan, panjangnya rantai distribusi, ketimpangan kapasitas produksi dan layanan, serta terbatasnya pemanfaatan teknologi pada budidaya, pengolahan, pengendalian mutu, pemasaran, dan pelayanan kesehatan.

Berbagai solusi telah diterapkan, namun masih cenderung sektoral, terutama pada aspek pemantauan waktu nyata, otomatisasi, pengujian cepat, ketertelusuran, interoperabilitas data, dan validasi lapangan. Kebutuhan ini sejalan dengan Program Makan Bergizi Gratis dan Program Cek Kesehatan Gratis.

## Dari tantangan nyata menuju inovasi berdampak

DEEPNEST dirancang sebagai akselerator yang mempertemukan pemilik masalah, inovator, perguruan tinggi, industri, pemerintah, fasilitas pelayanan kesehatan, dan calon pengguna. Setiap tantangan berasal dari kebutuhan nyata mitra sebagai pemilik masalah (*challenge owner*).

DEEPNEST tidak berhenti pada kompetisi. Program 12 bulan ini membangun jalur berkelanjutan dari validasi masalah, pengembangan purwarupa, pilot, perlindungan kekayaan intelektual, inkubasi, *business matching*, hingga adopsi dan hilirisasi teknologi oleh mitra.

## Tujuan

- Memvalidasi permasalahan berdasarkan kebutuhan pengguna.
- Membentuk kolaborasi lintas disiplin.
- Mengembangkan solusi menjadi purwarupa dengan indikator kinerja terukur.
- Menguji kesesuaian solusi terhadap kondisi operasional, standar teknis, regulasi, dan kemampuan pengguna.
- Menyiapkan produk menuju pilot dan hilirisasi.

## Target luaran

1. Terselenggaranya satu kegiatan Hackathon Deeptech DEEPNEST yang mengintegrasikan Challenge Discovery, Bootcamp, Mentoring Sprint, Prototype Development Sprint, Demo Day & Technical Review, serta pendampingan menuju pilot project dan hilirisasi.
2. Sekurang-kurangnya 15 tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator.
3. Sekurang-kurangnya 3 purwarupa fungsional yang tervalidasi awal, menjawab kebutuhan challenge owner, dan siap memasuki tahap pilot project serta hilirisasi teknologi.

## Metode pembinaan

Bimbingan teknis berlangsung selama 38 jam pelajaran (1 JP setara 45 menit) dengan pendekatan *problem-driven*, *experiential*, dan *stage-gated learning*. Pendampingan memakai skema mentor ganda: mentor internal memastikan ketepatan ilmiah, desain teknologi, pengujian, dan dokumentasi; mentor mitra menilai kesesuaian solusi dengan kebutuhan operasional, standar, biaya penerapan, kemampuan pengguna, dan peluang adopsi.

Pengembangan purwarupa melalui empat *stage gate*: validasi masalah dan spesifikasi minimum, *proof of concept*, integrasi dan pengujian fungsi, serta demonstrasi dan penyempurnaan berdasarkan umpan balik pengguna.

## Penyelenggara

DEEPNEST diselenggarakan oleh Universitas Hasanuddin melalui Direktorat Inovasi dan Kekayaan Intelektual, dalam rangka Program Hilirisasi Riset – Bantuan Pendanaan Penyelenggaraan Hackathon Deeptech dari Direktorat Hilirisasi dan Kemitraan, Direktorat Jenderal Riset dan Pengembangan, Kementerian Pendidikan Tinggi, Sains, dan Teknologi.

Keberlanjutan hasil dikawal oleh Direktorat Inovasi dan Kekayaan Intelektual, Direktorat Hilirisasi dan Komersialisasi, serta Science Techno Park Universitas Hasanuddin.
MD,
            ],
            'ketentuan' => [
                'title' => 'Ketentuan dan Kelayakan',
                'summary' => 'Siapa yang dapat mengikuti DEEPNEST, prinsip proposal, kerahasiaan, dan penilaian.',
                'body' => <<<'MD'
## Siapa yang dapat mengikuti

Tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator dari berbagai wilayah Indonesia yang memiliki gagasan, hasil riset, atau teknologi awal di bidang ketahanan pangan dan kesehatan.

## Prinsip proposal

- Proposal berorientasi pada penyelesaian masalah nyata dan disesuaikan dengan kebutuhan mitra (*challenge owner*) yang telah berkomitmen.
- Setiap proposal memilih satu tema utama: **Ketahanan Pangan**, **Kesehatan**, atau **Hybrid**. Hybrid hanya untuk solusi yang secara sengaja menjawab kedua bidang, bukan kategori cadangan.
- Alur pengisian: pilih tema → lihat tantangan terkait → pilih tantangan → lengkapi proposal.
- Proposal diisi secara terstruktur (identitas produk, tim, latar riset, aspek teknis, pasar dan dampak, kesesuaian tantangan, serta kesiapan legal, keselamatan, etik, dan data) dan dilengkapi gambar produk serta proposal resmi dalam format PDF.
- Proposal yang telah dikirim tidak dapat disunting kecuali panitia membukanya kembali untuk revisi. Tema tidak dapat diganti setelah pengiriman.

## Seleksi

Seleksi dilakukan melalui Open Call dengan target 15 tim terpilih. Penilaian dilakukan oleh reviewer/juri yang ditugaskan panitia menggunakan rubrik tertimbang. Juri tidak menilai tim yang dibimbingnya dan wajib menyatakan konflik kepentingan.

## Kerahasiaan

Isian proposal, berkas PDF, dokumen legal, dan bukti pendukung bersifat rahasia dan hanya dapat diakses oleh tim pengusul, reviewer yang ditugaskan, dan admin. Untuk produk yang disetujui, panitia hanya menampilkan ringkasan publik yang telah ditinjau. PDF proposal tidak ditampilkan kecuali panitia secara eksplisit membukanya.

## Ketentuan yang akan diumumkan

Jumlah maksimal anggota tim, jadwal dan tenggat Open Call, batas kata, format berkas, serta rubrik final akan diumumkan bersama pembukaan Open Call.
MD,
            ],
            'privasi' => [
                'title' => 'Kebijakan Privasi',
                'summary' => 'Bagaimana DEEPNEST mengumpulkan, menggunakan, dan melindungi data Anda.',
                'body' => <<<'MD'
## Data yang dikumpulkan

Saat mendaftar dan mengajukan proposal, kami mengumpulkan nama, alamat surel, nomor telepon, institusi, kota/provinsi, bidang keahlian, data tim, isian proposal, serta berkas yang Anda unggah.

## Tujuan penggunaan

Data digunakan untuk administrasi pendaftaran, seleksi dan penilaian, pendampingan, pelaporan program kepada pemberi dana, dan komunikasi terkait DEEPNEST.

## Akses dan kerahasiaan

- Isian proposal dan berkas hanya dapat diakses oleh tim Anda, reviewer yang ditugaskan, dan admin.
- Nomor telepon dan surel peserta tidak pernah ditampilkan publik.
- Nilai dan catatan reviewer tidak ditampilkan kepada peserta kecuali panitia mengaktifkannya.
- Setiap akses ke berkas sensitif dicatat dalam log audit.
- Ringkasan publik hanya ditampilkan untuk produk yang disetujui dan telah ditinjau panitia.

## Penyimpanan

Berkas disimpan di luar direktori publik server dan hanya disajikan melalui pemeriksaan hak akses. Data disimpan selama program berjalan dan selama diperlukan untuk pelaporan.

## Hak Anda

Anda dapat memperbarui profil melalui portal dan menghubungi panitia untuk pertanyaan atau permintaan terkait data pribadi.
MD,
            ],
            'syarat' => [
                'title' => 'Syarat Penggunaan',
                'summary' => 'Ketentuan penggunaan portal DEEPNEST.',
                'body' => <<<'MD'
## Akun

Setiap pengguna bertanggung jawab atas kerahasiaan kata sandi dan kebenaran data yang diisikan. Satu akun digunakan oleh satu orang.

## Isi proposal

Peserta menjamin bahwa isi proposal adalah karya tim, tidak melanggar hak pihak lain, dan menyebutkan dengan jujur status kekayaan intelektual, perizinan, serta ketergantungan pada pihak ketiga.

## Penggunaan yang dilarang

Dilarang mengunggah berkas berbahaya, mencoba mengakses data pengguna lain, atau menggunakan portal untuk tujuan di luar program DEEPNEST.

## Keputusan panitia

Keputusan seleksi dan publikasi ditetapkan oleh panitia sesuai ketentuan program. Hal-hal yang belum diatur akan ditetapkan kemudian oleh Ketua Tim Pelaksana.
MD,
            ],
        ];

        foreach ($pages as $slug => $page) {
            Page::query()->firstOrCreate(['slug' => $slug], $page + ['is_published' => true]);
        }
    }
}
