<?php

namespace Database\Seeders;

use App\Models\ReviewCriterion;
use App\Models\ReviewPhase;
use Illuminate\Database\Seeder;

/**
 * Dua fase penilaian dengan kriteria AWAL (seed). Seluruh kriteria, bobot, dan
 * skor maksimum dapat diubah admin melalui menu Rubrik dan Kriteria.
 */
class ReviewPhaseSeeder extends Seeder
{
    public function run(): void
    {
        $openCall = ReviewPhase::query()->updateOrCreate(['code' => 'open_call'], [
            'name' => 'Seleksi Open Call',
            'description' => 'Penyaringan proposal: relevansi, kesesuaian tantangan, kesiapan tim, dan kelengkapan proposal.',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $demoDay = ReviewPhase::query()->updateOrCreate(['code' => 'demo_day'], [
            'name' => 'Demo Day & Technical Review',
            'description' => 'Penilaian purwarupa: fungsi, kelayakan teknis, keselamatan, dampak, kesiapan implementasi, dan potensi hilirisasi.',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        if (! $openCall->criteria()->exists()) {
            $this->criteria($openCall, [
                ['Relevansi terhadap masalah nyata challenge owner', 'Seberapa tepat proposal menjawab tantangan mitra yang dipilih dan prioritas daerah/nasional.', 15],
                ['Kejelasan masalah dan pengguna', 'Akar masalah, pengguna terdampak, dan kondisi saat ini diuraikan jelas dan didukung data.', 10],
                ['Kebaruan dan potensi deeptech', 'Tingkat kebaruan teknologi dan kedalaman ilmiah/rekayasa yang dibawa.', 10],
                ['Kesiapan tim lintas disiplin', 'Komposisi keahlian tim sesuai kebutuhan solusi dan pengalaman relevan.', 10],
                ['Kelayakan teknis', 'Solusi realistis untuk menghasilkan purwarupa fungsional dalam siklus program.', 15],
                ['Bukti atau kesiapan purwarupa awal', 'Hasil riset, data uji, atau purwarupa awal yang sudah ada (TKT).', 10],
                ['Potensi dampak dan adopsi', 'Manfaat terukur bagi pengguna dan peluang diadopsi oleh mitra.', 15],
                ['Kesiapan keselamatan, legal, etik, dan data', 'Identifikasi isu KI, perizinan, keselamatan, etik, dan perlindungan data beserta mitigasinya. Ini menilai kesiapan, bukan menyatakan kepatuhan hukum final.', 10],
                ['Kualitas dan kelengkapan proposal', 'Kelengkapan isian, lampiran, dan kejelasan penyajian.', 5],
            ]);
        }

        if (! $demoDay->criteria()->exists()) {
            $this->criteria($demoDay, [
                ['Relevansi masalah', 'Purwarupa tetap menjawab masalah dan kebutuhan pengguna challenge owner.', 10],
                ['Bukti fungsi', 'Fungsi utama terdemonstrasi secara nyata, bukan sekadar simulasi.', 15],
                ['Kinerja teknis', 'Akurasi, keandalan, keterulangan, dan kinerja terhadap indikator keberhasilan.', 15],
                ['Validasi pengguna', 'Umpan balik dan uji awal bersama calon pengguna.', 10],
                ['Kesiapan keselamatan dan regulasi', 'Aspek keselamatan, standar teknis, dan jalur regulasi yang realistis.', 10],
                ['Kelayakan operasional', 'Kemudahan operasi, biaya, pemeliharaan, dan kesesuaian dengan proses kerja mitra.', 10],
                ['Dampak dan skalabilitas', 'Potensi manfaat luas dan kemampuan direplikasi di wilayah lain.', 10],
                ['Rencana implementasi dan hilirisasi', 'Rencana pilot, KI, model bisnis, dan jalur adopsi yang jelas.', 10],
                ['Kualitas demonstrasi dan pitch', 'Kejelasan penyampaian, demonstrasi, dan jawaban atas pertanyaan juri.', 10],
            ]);
        }
    }

    private function criteria(ReviewPhase $phase, array $rows): void
    {
        foreach ($rows as $i => [$name, $description, $weight]) {
            ReviewCriterion::query()->create([
                'review_phase_id' => $phase->id,
                'name' => $name,
                'description' => $description,
                'max_score' => 5,
                'weight' => $weight,
                'requires_comment' => false,
                'sort_order' => $i + 1,
                'is_active' => true,
            ]);
        }
    }
}
