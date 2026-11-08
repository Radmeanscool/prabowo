<?php

namespace Database\Seeders;

use App\Models\ProgramStage;
use Illuminate\Database\Seeder;

/**
 * Tahapan program sesuai proposal DEEPNEST dan infografik alur program (4 fase).
 * Tahap 7–11 sudah ada di model data agar program dapat berlanjut tanpa desain ulang.
 */
class ProgramStageSeeder extends Seeder
{
    public function run(): void
    {
        $phase1 = 'Formulasi Tantangan';
        $phase2 = 'Penjaringan Inovator';
        $phase3 = 'Hackathon & Pengembangan Purwarupa';
        $phase4 = 'Pilot, Hilirisasi & Dampak';

        $stages = [
            ['challenge_discovery', 1, $phase1, 'Challenge Discovery', 'Identifikasi Tantangan', 'Bulan 1–2', 1, 2, 'ongoing',
                'Tantangan dirumuskan bersama mitra sebagai pemilik masalah (challenge owner) melalui FGD, validasi lapangan, dan penyusunan challenge brief.',
                "Identifikasi masalah bersama challenge owner\nPemetaan kebutuhan pengguna\nPerumusan challenge statement",
                'Daftar tantangan prioritas dan kebutuhan teknologi'],
            ['open_call', 2, $phase2, 'Open Call & Team Formation', 'Open Call dan Pembentukan Tim', 'Bulan 2–4', 2, 4, 'upcoming',
                'Sosialisasi, pendaftaran, dan seleksi tim lintas disiplin dari perguruan tinggi, startup, lembaga riset, dan masyarakat inovator.',
                "Sosialisasi dan pendaftaran\nSeleksi peserta\nPembentukan dan penyempurnaan tim lintas disiplin",
                '15 tim terpilih'],
            ['bootcamp', 3, $phase3, 'Bootcamp', 'Bootcamp', 'Bulan 5 · Hari 1', 5, 5, 'upcoming',
                'Penyamaan pemahaman masalah, validasi kebutuhan pengguna, dan perancangan solusi bersama mentor.',
                "Pemahaman masalah\nValidasi kebutuhan pengguna\nPerancangan solusi dan spesifikasi teknis",
                'Desain solusi tervalidasi'],
            ['mentoring_sprint', 3, $phase3, 'Mentoring Sprint', 'Mentoring Sprint', 'Bulan 5 · Hari 2', 5, 5, 'upcoming',
                'Pendampingan skema mentor ganda: mentor internal untuk ketepatan ilmiah dan mentor mitra untuk kesesuaian operasional.',
                "Pendampingan oleh mentor ahli\nPengembangan purwarupa\nPengujian awal dan iterasi",
                'Purwarupa fungsional (awal)'],
            ['prototype_development_sprint', 3, $phase3, 'Prototype Development Sprint', 'Sprint Pengembangan Purwarupa', 'Bulan 5', 5, 5, 'upcoming',
                'Pembuatan proof of concept, integrasi komponen, dan pengujian awal kinerja, akurasi, keamanan, keterulangan, serta kemudahan penggunaan.',
                "Proof of concept dan integrasi komponen\nPengujian awal dan peer review\nDokumentasi perubahan dan mitigasi risiko",
                'Purwarupa fungsional dan data uji awal'],
            ['demo_day', 3, $phase3, 'Demo Day & Technical Review', 'Demo Day dan Telaah Teknis', 'Bulan 5 · Hari 3', 5, 5, 'upcoming',
                'Presentasi dan demonstrasi purwarupa di hadapan juri dan mitra, dilanjutkan seleksi inovasi prioritas.',
                "Presentasi dan demonstrasi purwarupa\nEvaluasi teknis, pengguna, dan potensi hilirisasi\nSeleksi inovasi prioritas",
                '3 purwarupa prioritas'],
            ['prototype_funding', 4, $phase4, 'Prototype Funding & Pilot Preparation', 'Pendanaan Purwarupa dan Persiapan Pilot', 'Bulan 6–7', 6, 7, 'upcoming',
                'Bantuan pengembangan purwarupa terpilih serta penyusunan lokasi, protokol, indikator, dan pembagian tanggung jawab pilot bersama mitra.',
                "Penyempurnaan purwarupa\nPendanaan pengembangan\nPenyusunan protokol pilot\nAnalisis risiko dan kesiapan",
                'Purwarupa siap pilot'],
            ['pilot_project', 4, $phase4, 'Pilot Project', 'Uji Coba Lapangan', 'Bulan 7–10', 7, 10, 'upcoming',
                'Uji coba di lingkungan operasional mitra untuk menilai kinerja teknis, penerimaan pengguna, dan kesiapan integrasi.',
                "Implementasi di lingkungan nyata\nPengujian kinerja\nEvaluasi bersama mitra pengguna",
                'Hasil uji pilot dan rekomendasi adopsi'],
            ['incubation_downstreaming', 4, $phase4, 'Incubation & Downstreaming', 'Inkubasi dan Hilirisasi', 'Bulan 6–12', 6, 12, 'upcoming',
                'Pendampingan perlindungan KI, standardisasi, model bisnis, business matching, dan akses pendanaan lanjutan.',
                "Inkubasi teknologi\nPerlindungan kekayaan intelektual\nBusiness matching dan investasi\nPersiapan komersialisasi",
                'Model bisnis, kemitraan, dan rencana hilirisasi'],
            ['adoption_impact', 4, $phase4, 'Adoption & Impact', 'Adopsi dan Dampak', 'Bulan 12+', 12, 12, 'upcoming',
                'Adopsi produk oleh mitra melalui penggunaan langsung, pengadaan, lisensi, atau produksi bersama.',
                "Adopsi oleh industri dan pengguna\nLisensi atau produksi bersama\nSkalasi dan keberlanjutan",
                'Pemanfaatan teknologi dan dampak nyata'],
            ['monitoring_evaluation', null, 'Lintas fase', 'Monitoring, Evaluation & Reporting', 'Monitoring, Evaluasi, dan Pelaporan', 'Bulan 1–12', 1, 12, 'ongoing',
                'Berjalan di setiap tahap untuk memastikan kualitas, akuntabilitas, dan pencapaian luaran.',
                "Pemantauan jadwal dan capaian\nEvaluasi per tahap\nPelaporan program",
                'Laporan kemajuan dan evaluasi program'],
        ];

        foreach ($stages as $i => [$code, $phase, $phaseName, $name, $nameId, $month, $start, $end, $status, $description, $activities, $output]) {
            ProgramStage::query()->updateOrCreate(['code' => $code], [
                'phase_number' => $phase,
                'phase_name' => $phaseName,
                'name' => $name,
                'name_id' => $nameId,
                'month_label' => $month,
                'start_month' => $start,
                'end_month' => $end,
                'status' => $status,
                'description' => $description,
                'activities' => $activities,
                'output' => $output,
                'is_public' => true,
                'sort_order' => $i + 1,
            ]);
        }
    }
}
