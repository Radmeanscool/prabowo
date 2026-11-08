<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * DRAF tantangan yang disusun dari notulensi FGD 1 Challenge Discovery (1–2 November 2026).
 * Semuanya berstatus "draft" (tidak tampil publik) sampai divalidasi dan difinalisasi
 * pada FGD 2, lalu diterbitkan admin.
 */
class ChallengeSeeder extends Seeder
{
    public function run(): void
    {
        if (Challenge::withTrashed()->exists()) {
            return;
        }

        $themes = Theme::query()->pluck('id', 'code');
        $owner = fn (string $slug) => ChallengeOwner::query()->where('slug', $slug)->value('id');
        $note = 'Draf dari notulensi FGD 1 (1–2 November 2026). Perlu validasi lapangan dan finalisasi pada FGD 2 sebelum diterbitkan. Lengkapi indikator keberhasilan terukur dan batasan bersama mitra.';

        $rows = [
            [
                'title' => 'Produksi pangan stabil di tengah penyusutan lahan produktif',
                'owner' => 'pemkab-maros', 'theme' => Theme::FOOD_SECURITY, 'eligible' => [Theme::FOOD_SECURITY, Theme::HYBRID],
                'summary' => 'Inovasi agar produksi pangan tetap stabil saat lahan pertanian produktif menyusut akibat alih fungsi lahan dan kerusakan kawasan hulu.',
                'problem' => "Kedekatan dengan Kota Makassar memicu migrasi penduduk dan alih fungsi lahan pertanian menjadi perumahan, terutama di kecamatan yang berbatasan dengan Makassar. Di kawasan hulu/pegunungan, pembukaan lahan merusak unsur tanah sehingga memicu banjir saat musim hujan dan kekeringan saat kemarau, yang berdampak pada penurunan produksi.\n\nPemerintah daerah telah menetapkan perlindungan lahan pangan pertanian berkelanjutan, namun krisis lahan tetap menjadi ancaman jangka panjang.",
                'outcome' => 'Produksi pangan tetap stabil di tengah penyusutan lahan produktif, termasuk dukungan penerapan pertanian perkotaan (urban farming) dan transisi mata pencaharian warga dalam 5–10 tahun.',
                'users' => 'Petani, kelompok tani, dan perangkat daerah terkait.',
            ],
            [
                'title' => 'Pemantauan kesehatan ibu hamil dan pencegahan stunting',
                'owner' => 'pemkab-maros', 'theme' => Theme::HEALTH, 'eligible' => [Theme::HEALTH, Theme::HYBRID],
                'summary' => 'Solusi untuk meningkatkan kepatuhan pemeriksaan kehamilan dan mendukung penurunan stunting serta kematian ibu dan bayi.',
                'problem' => "Angka kematian ibu melahirkan dan bayi dilaporkan meningkat, dan stunting masih menjadi fokus penanganan. Masalah utama adalah rendahnya kesadaran ibu hamil untuk rutin berkonsultasi ke fasilitas kesehatan meskipun penyuluhan telah diberikan.\n\nFasilitas rumah sakit tipe C di wilayah pegunungan juga terkendala kekurangan tenaga medis dan alat kesehatan.",
                'outcome' => 'Peningkatan pemantauan dan tindak lanjut kesehatan ibu hamil serta balita berisiko stunting.',
                'users' => 'Ibu hamil, kader dan tenaga kesehatan, puskesmas, serta rumah sakit daerah.',
            ],
            [
                'title' => 'Akses layanan kesehatan dari wilayah kepulauan',
                'owner' => 'pemkab-pangkep', 'theme' => Theme::HEALTH, 'eligible' => [Theme::HEALTH, Theme::HYBRID],
                'summary' => 'Teknologi untuk menjembatani keterbatasan distribusi dan aksesibilitas layanan kesehatan antara pulau dan daratan.',
                'problem' => 'Kendala utama berada pada distribusi dan aksesibilitas menuju fasilitas kesehatan, terutama dari wilayah kepulauan ke daratan yang sulit dijangkau. Sebaran tenaga medis dan fasilitas pendukung di pulau sangat terbatas, sejalan dengan tingginya angka stunting dan tuberkulosis (TBC).',
                'outcome' => 'Deteksi dini, pemantauan, dan rujukan yang lebih cepat bagi warga kepulauan.',
                'users' => 'Warga kepulauan, tenaga kesehatan di pulau, dan dinas kesehatan.',
            ],
            [
                'title' => 'Perlindungan lahan pertanian produktif berkelanjutan',
                'owner' => 'pemkab-pangkep', 'theme' => Theme::FOOD_SECURITY, 'eligible' => [Theme::FOOD_SECURITY, Theme::HYBRID],
                'summary' => 'Solusi untuk melindungi dan memantau lahan pertanian produktif secara berkelanjutan.',
                'problem' => 'Pemerintah daerah membutuhkan solusi untuk perlindungan lahan produktif secara berkelanjutan. Isu air bersih juga dicatat sebagai kebutuhan daerah dalam FGD.',
                'outcome' => 'Data dan alat bantu keputusan untuk perlindungan lahan pertanian produktif.',
                'users' => 'Perangkat daerah bidang pertanian dan tata ruang, serta petani.',
            ],
            [
                'title' => 'Intensifikasi reproduksi dan peningkatan populasi sapi',
                'owner' => 'disnakkeswan-sulsel', 'theme' => Theme::FOOD_SECURITY, 'eligible' => [Theme::FOOD_SECURITY, Theme::HYBRID],
                'summary' => 'Teknologi pendukung inseminasi buatan, layanan betina produktif, kesehatan, dan nutrisi ternak.',
                'problem' => 'Tantangan utama berfokus pada peningkatan produksi ternak, intensifikasi alat produksi, dan program peningkatan populasi sapi, meliputi reproduksi ternak (inseminasi buatan dan layanan betina produktif), kesehatan ternak, dan nutrisi ternak. Dinas menyampaikan tersedianya dana pendamping.',
                'outcome' => 'Peningkatan keberhasilan reproduksi dan populasi sapi di Sulawesi Selatan.',
                'users' => 'Peternak, inseminator, dan petugas kesehatan hewan.',
            ],
            [
                'title' => 'Ketertelusuran dan mutu rumput laut pascapanen',
                'owner' => 'kospermindo', 'theme' => Theme::FOOD_SECURITY, 'eligible' => [Theme::FOOD_SECURITY, Theme::HYBRID],
                'summary' => 'Solusi ketertelusuran dan peningkatan mutu rumput laut pada tahap penanganan pascapanen.',
                'problem' => 'Koperasi telah mengembangkan platform aplikasi untuk komoditas rumput laut dengan fokus pada tahap penanganan pascapanen, dan membutuhkan solusi ketertelusuran serta peningkatan mutu rumput laut.',
                'outcome' => 'Rumput laut anggota koperasi tertelusur dan memenuhi standar mutu pembeli.',
                'users' => 'Pembudidaya rumput laut anggota koperasi dan pengelola koperasi.',
            ],
            [
                'title' => 'Distribusi benih jagung unggul hingga ke petani',
                'owner' => 'hadin-metavisi', 'theme' => Theme::FOOD_SECURITY, 'eligible' => [Theme::FOOD_SECURITY, Theme::HYBRID],
                'summary' => 'Model dan teknologi untuk menjamin ketersediaan dan distribusi benih jagung unggul serta konsistensi mutu bagi petani.',
                'problem' => 'Pengembangan varietas jagung unggul hasil inovasi Unhas menargetkan produktivitas 5–7 ton per hektare dengan mutu yang konsisten untuk kebutuhan industri pakan dan bioetanol. Tantangan mencakup keunggulan varietas, proses litbang, jaminan ketersediaan benih, regulasi sertifikasi, manajemen sektor hulu, serta model bisnis yang efisien untuk mendistribusikan benih hingga ke petani.',
                'outcome' => 'Benih unggul tersedia tepat waktu di tingkat petani dengan mutu terjaga.',
                'users' => 'Petani jagung, penangkar benih, dan industri pakan.',
            ],
            [
                'title' => 'Drone untuk pertanian, pemantauan laut, dan distribusi alat kesehatan',
                'owner' => null, 'theme' => Theme::HYBRID, 'eligible' => [Theme::HYBRID, Theme::FOOD_SECURITY, Theme::HEALTH],
                'summary' => 'Pemanfaatan drone untuk penaburan benih, pemetaan/pemantauan bawah laut, dan pengantaran alat kesehatan ke wilayah terpencil.',
                'problem' => 'Mitra penyedia teknologi drone menawarkan perakitan dan operasional drone, termasuk drone penabur benih, pemetaan/pemantauan bawah laut, serta pengantaran alat kesehatan ke tempat terpencil.',
                'outcome' => 'Purwarupa layanan drone yang tervalidasi untuk kebutuhan pangan dan kesehatan di wilayah kepulauan.',
                'users' => 'Petani, nelayan, dan fasilitas kesehatan di wilayah terpencil.',
            ],
            [
                'title' => 'AI berbasis data untuk kebutuhan medis dan operasional rumah sakit',
                'owner' => 'rsp-unhas', 'theme' => Theme::HEALTH, 'eligible' => [Theme::HEALTH, Theme::HYBRID],
                'summary' => 'Purwarupa kecerdasan buatan berbasis data untuk menunjang layanan medis dan operasional rumah sakit.',
                'problem' => 'Rumah sakit membutuhkan purwarupa kecerdasan buatan (AI) berbasis data untuk menunjang kebutuhan medis dan operasional, dengan tetap memenuhi aspek etik, keselamatan, validasi, dan perlindungan data pasien.',
                'outcome' => 'Layanan yang lebih cepat, akurat, aman, dan efisien.',
                'users' => 'Tenaga kesehatan, manajemen rumah sakit, dan pasien.',
            ],
        ];

        foreach ($rows as $i => $row) {
            $challenge = Challenge::query()->create([
                'theme_id' => $themes[$row['theme']],
                'challenge_owner_id' => $row['owner'] ? $owner($row['owner']) : null,
                'title' => $row['title'],
                'slug' => Str::slug($row['title']),
                'summary' => $row['summary'],
                'problem_statement' => $row['problem'],
                'desired_outcome' => $row['outcome'],
                'affected_users' => $row['users'],
                'internal_notes' => $note.($row['owner'] ? '' : ' Konfirmasi nama mitra penyedia teknologi drone.'),
                'status' => 'draft',
                'sort_order' => $i + 1,
            ]);

            $challenge->themes()->sync(collect($row['eligible'])->map(fn ($code) => $themes[$code])->all());
        }
    }
}
