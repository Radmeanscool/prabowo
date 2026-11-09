<?php

namespace Database\Seeders;

use App\Models\AgendaItem;
use App\Models\ProgramStage;
use Illuminate\Database\Seeder;

/**
 * Agenda yang tercantum di dokumen resmi (surat undangan dan KAK Challenge Discovery).
 * Butir tanpa tanggal pasti diberi status "Rencana" dan label waktu umum.
 *
 * Hanya kegiatan yang sudah terlaksana yang tampil publik secara bawaan. Butir rencana
 * disimpan sebagai draf (tidak publik) agar situs tidak menampilkan jadwal yang belum
 * ditetapkan; admin menayangkannya melalui menu Agenda setelah tanggalnya pasti.
 */
class AgendaSeeder extends Seeder
{
    public function run(): void
    {
        if (AgendaItem::query()->exists()) {
            return;
        }

        $stage = fn (string $code) => ProgramStage::query()->where('code', $code)->value('id');
        $venue = 'Unhas Hotel & Convention, Makassar';

        $items = [
            ['challenge_discovery', 'FGD Challenge Discovery · Hari I: Pemetaan Masalah', 'diskusi', '2026-11-01', null, '08:00', null, $venue, 'completed',
                'Paparan program DEEPNEST dan peran mitra sebagai challenge owner, paparan permasalahan setiap mitra, serta diskusi kelompok tematik pangan dan kesehatan.'],
            ['challenge_discovery', 'FGD Challenge Discovery · Hari II: Perumusan dan Kesepakatan Tantangan', 'diskusi', '2026-11-02', null, '08:00', null, $venue, 'completed',
                'Perumusan challenge statement, fungsi minimum, dan indikator keberhasilan; prioritisasi tantangan; kesepakatan peran dan kontribusi mitra; persiapan Open Call.'],
            ['challenge_discovery', 'Validasi lapangan ke mitra prioritas', 'kunjungan', null, 'November 2026', null, null, 'Lokasi mitra', 'planned',
                'Observasi proses kerja, wawancara pengguna, pemeriksaan data, dan peninjauan calon lokasi uji coba.'],
            ['challenge_discovery', 'FGD 2: Finalisasi tantangan', 'diskusi', null, 'November 2026', null, null, 'Makassar', 'planned',
                'Penetapan tantangan final, kesepakatan kontribusi mitra, dan kriteria seleksi peserta.'],
            ['open_call', 'Penyerahan tantangan final ke Open Call', 'publikasi', null, 'Akhir November 2026', null, null, 'Universitas Hasanuddin', 'planned',
                'Tantangan final dipublikasikan melalui laman DEEPNEST sebagai dasar Open Call peserta.'],
            ['bootcamp', 'Bootcamp (Hari 1)', 'workshop', null, 'Bulan ke-5 program · tanggal menyusul', null, null, 'Makassar', 'planned',
                'Pemahaman masalah, validasi kebutuhan pengguna, serta perancangan solusi dan spesifikasi teknis.'],
            ['mentoring_sprint', 'Mentoring Sprint (Hari 2)', 'mentoring', null, 'Bulan ke-5 program · tanggal menyusul', null, null, 'Makassar', 'planned',
                'Pendampingan oleh mentor ahli, pengembangan purwarupa, pengujian awal, dan iterasi.'],
            ['demo_day', 'Demo Day & Technical Review (Hari 3)', 'demo', null, 'Bulan ke-5 program · tanggal menyusul', null, null, 'Makassar', 'planned',
                'Presentasi dan demonstrasi purwarupa, evaluasi teknis dan potensi hilirisasi, serta seleksi inovasi prioritas.'],
        ];

        foreach ($items as $i => [$code, $title, $type, $date, $label, $start, $end, $location, $status, $description]) {
            AgendaItem::query()->create([
                'program_stage_id' => $stage($code),
                'title' => $title,
                'session_type' => $type,
                'date' => $date,
                'date_label' => $label,
                'start_time' => $start,
                'end_time' => $end,
                'location' => $location,
                'description' => $description,
                'status' => $status,
                'is_public' => $status === 'completed',
                'sort_order' => $i + 1,
            ]);
        }
    }
}
