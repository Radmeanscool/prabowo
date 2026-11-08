<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        if (Faq::query()->exists()) {
            return;
        }

        $faqs = [
            ['Umum', 'Apa itu DEEPNEST?', 'DEEPNEST (Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies) adalah hackathon deep technology Universitas Hasanuddin bertema Ketahanan Pangan dan Kesehatan. Program berlangsung 12 bulan, dari perumusan tantangan bersama mitra hingga pilot, inkubasi, dan hilirisasi.'],
            ['Pendaftaran', 'Siapa yang dapat mendaftar?', 'Tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan/atau masyarakat inovator dari berbagai wilayah Indonesia yang memiliki gagasan, hasil riset, atau teknologi awal di bidang ketahanan pangan dan kesehatan.'],
            ['Pendaftaran', 'Kapan Open Call dibuka?', 'Tantangan final sedang difinalisasi bersama mitra. Jadwal Open Call akan diumumkan di halaman Pengumuman dan Linimasa. Anda sudah dapat membuat akun dan menyiapkan tim.'],
            ['Proposal', 'Tema apa saja yang tersedia?', "Tiga tema: **Ketahanan Pangan**, **Kesehatan**, dan **Hybrid**. Hybrid hanya untuk solusi yang secara sengaja menjawab kedua bidang atau memadukan kapabilitas keduanya, bukan pilihan cadangan."],
            ['Proposal', 'Apakah saya harus memilih tantangan dari mitra?', 'Ya. Proposal harus disesuaikan dengan kebutuhan mitra pemilik masalah. Setelah memilih tema, portal menampilkan tantangan yang relevan untuk dipilih.'],
            ['Proposal', 'Bisakah proposal diubah setelah dikirim?', 'Tidak. Setelah dikirim, proposal terkunci. Panitia dapat membukanya kembali untuk revisi; perubahan tersebut tercatat dalam riwayat.'],
            ['Kerahasiaan', 'Apakah proposal saya akan dipublikasikan?', 'Tidak. Proposal dan berkas bersifat rahasia. Jika produk disetujui, panitia hanya menampilkan ringkasan publik (nama, gambar, deskripsi singkat, tema, dan tantangan). PDF proposal tetap privat kecuali panitia secara eksplisit membukanya.'],
            ['Penilaian', 'Bagaimana proposal dinilai?', 'Proposal dinilai oleh reviewer/juri yang ditugaskan panitia menggunakan rubrik tertimbang per fase (Seleksi Open Call dan Demo Day). Reviewer wajib menyatakan konflik kepentingan, dan juri tidak menilai tim yang dibimbingnya.'],
            ['Program', 'Apa yang diperoleh tim terpilih?', 'Tim terpilih mengikuti Bootcamp, Mentoring Sprint, dan Demo Day dengan pendampingan mentor internal dan mentor mitra. Purwarupa prioritas diarahkan ke pengembangan lanjutan, pilot di lingkungan mitra, perlindungan KI, inkubasi, dan hilirisasi.'],
        ];

        foreach ($faqs as $i => [$category, $question, $answer]) {
            Faq::query()->create([
                'category' => $category,
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $i + 1,
                'is_published' => true,
            ]);
        }
    }
}
