<?php

namespace Database\Seeders;

use App\Models\ChallengeOwner;
use App\Models\Theme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Mitra pemilik masalah sesuai daftar undangan FGD Challenge Discovery.
 * Seluruhnya TIDAK publik secara default (is_public = false) sampai mitra
 * memberi izin penayangan nama/logo. Admin cukup mengaktifkan "Tampilkan publik".
 */
class ChallengeOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $food = Theme::query()->where('code', Theme::FOOD_SECURITY)->value('id');
        $health = Theme::query()->where('code', Theme::HEALTH)->value('id');

        $owners = [
            ['Pemerintah Kabupaten Maros', 'Pemkab Maros', 'pemerintah_daerah', $food, 'invited',
                'Produktivitas komoditas unggulan, efisiensi pengelolaan kawasan produksi, ketertelusuran hasil, serta konektivitas pelaku usaha, pemerintah daerah, dan pasar.'],
            ['Pemerintah Kabupaten Pangkajene dan Kepulauan', 'Pemkab Pangkep', 'pemerintah_daerah', $food, 'invited',
                'Produktivitas komoditas unggulan wilayah daratan dan kepulauan, efisiensi kawasan produksi, ketertelusuran, distribusi antarpulau, dan akses pasar.'],
            ['Pemerintah Kabupaten Sidenreng Rappang', 'Pemkab Sidrap', 'pemerintah_daerah', $food, 'invited',
                'Usulan awal: produktivitas komoditas pertanian dan peternakan unggulan, pengolahan hasil, rantai pasok, dan dukungan pasokan program Makan Bergizi Gratis.'],
            ['Dinas Peternakan dan Kesehatan Hewan Provinsi Sulawesi Selatan', 'Disnakkeswan Sulsel', 'instansi_pemerintah', $food, 'invited',
                'Pemantauan produksi dan kesehatan ternak, pengendalian mutu pakan, peningkatan layanan peternakan, dan pengembangan genetik ternak.'],
            ['PT JAPFA Comfeed Indonesia Tbk.', 'JAPFA', 'industri', $food, 'invited',
                'Mutu pakan dan bahan baku, pengujian cepat, genetik unggul, dan efisiensi produksi.'],
            ['PT Enersky Innovation Indonesia', 'Enersky', 'industri', $food, 'invited',
                'Teknologi pendukung: sensor dan IoT, otomasi, serta integrasi perangkat dan sistem pemantauan produksi.'],
            ['PT Hadin Metavisi Akademika', 'Hadin Metavisi', 'industri', $food, 'invited',
                'Teknologi pendukung: analitika data, kecerdasan buatan, pengembangan perangkat lunak, serta integrasi dan interoperabilitas sistem.'],
            ['Koperasi Serikat Pekerja Merdeka Indonesia (KOSPERMINDO)', 'KOSPERMINDO', 'koperasi', $food, 'invited',
                'Kebutuhan pengguna pada unit budidaya dan pengolahan, mutu produk, serta pemasaran dan distribusi bagi anggota koperasi.'],
            ['GAKESLAB Indonesia Wilayah Sulawesi Selatan', 'GAKESLAB Sulsel', 'asosiasi', $health, 'invited',
                'Kebutuhan pasar alat kesehatan dan laboratorium, standar teknis, perizinan edar, serta kesiapan manufaktur dan distribusi.'],
            ['Rumah Sakit Pendidikan Universitas Hasanuddin', 'RSP Unhas', 'fasilitas_kesehatan', $health, 'invited',
                'Alat kesehatan untuk deteksi dini, pemeriksaan, pemantauan pasien, serta efisiensi dan keselamatan layanan.'],
            ['HIPELKI', 'HIPELKI', 'asosiasi', null, 'pending_confirmation',
                'Bidang dan fokus tantangan menunggu konfirmasi.'],
        ];

        foreach ($owners as $i => [$name, $short, $type, $themeId, $status, $focus]) {
            $owner = ChallengeOwner::withTrashed()->firstOrNew(['slug' => Str::slug($short)]);
            if (! $owner->exists) {
                $owner->fill([
                    'name' => $name,
                    'short_name' => $short,
                    'type' => $type,
                    'theme_id' => $themeId,
                    'participation_status' => $status,
                    'initial_focus' => $focus,
                    'is_public' => false,
                    'sort_order' => $i + 1,
                    'internal_notes' => 'Diambil dari daftar undangan FGD Challenge Discovery (Oktober 2026). Aktifkan "Tampilkan publik" setelah ada izin mitra.',
                ])->save();
            }
        }
    }
}
