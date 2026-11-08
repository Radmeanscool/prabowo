<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    public function run(): void
    {
        $themes = [
            [
                'code' => Theme::FOOD_SECURITY,
                'name' => 'Ketahanan Pangan',
                'name_en' => 'Food Security',
                'color' => '#1F8A4C',
                'sort_order' => 1,
                'description' => 'Budidaya dan produksi cerdas, otomasi dan analitika produksi, mutu pakan dan bahan baku, genetik unggul, pengolahan hasil pangan, perikanan, dan pertanian, serta pemasaran, distribusi, dan ketertelusuran untuk produktivitas dan rantai pasok yang berkelanjutan.',
            ],
            [
                'code' => Theme::HEALTH,
                'name' => 'Kesehatan',
                'name_en' => 'Health',
                'color' => '#D0404B',
                'sort_order' => 2,
                'description' => 'Alat kesehatan berteknologi tinggi untuk deteksi dini, pemeriksaan, dan pemantauan pasien; pelayanan kesehatan yang lebih cepat, aman, akurat, dan terjangkau; efisiensi operasional rumah sakit; serta sistem berbasis AI/data untuk kebutuhan medis dan operasional.',
            ],
            [
                'code' => Theme::HYBRID,
                'name' => 'Hybrid',
                'name_en' => 'Hybrid',
                'color' => '#6A55D6',
                'sort_order' => 3,
                'description' => 'Solusi yang secara sengaja menjawab ketahanan pangan dan kesehatan sekaligus, atau memadukan kapabilitas kedua bidang dalam satu solusi terintegrasi. Hybrid bukan kategori cadangan bagi proposal yang jelas termasuk satu tema.',
            ],
        ];

        foreach ($themes as $theme) {
            Theme::query()->updateOrCreate(['code' => $theme['code']], $theme);
        }
    }
}
