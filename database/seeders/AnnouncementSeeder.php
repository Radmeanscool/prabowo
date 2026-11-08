<?php

namespace Database\Seeders;

use App\Models\Announcement;
use Illuminate\Database\Seeder;

/**
 * Draf pengumuman (belum terbit). Admin meninjau lalu menerbitkan.
 */
class AnnouncementSeeder extends Seeder
{
    public function run(): void
    {
        Announcement::query()->firstOrCreate(['slug' => 'fgd-challenge-discovery-deepnest'], [
            'title' => 'FGD Challenge Discovery DEEPNEST telah dilaksanakan',
            'excerpt' => 'Mitra pemerintah daerah, industri, koperasi, asosiasi, dan fasilitas kesehatan memaparkan permasalahan prioritas sebagai dasar tantangan DEEPNEST.',
            'body' => "FGD Challenge Discovery Hackathon Deeptech DEEPNEST dilaksanakan pada 1–2 November 2026 di Unhas Hotel & Convention, Makassar.\n\nPada forum ini, mitra memaparkan permasalahan prioritas di bidang ketahanan pangan dan kesehatan, lalu bersama tim pelaksana merumuskan challenge statement, fungsi minimum, dan indikator keberhasilan.\n\nTahap berikutnya adalah validasi lapangan dan FGD 2 untuk menetapkan tantangan final yang akan diumumkan bersama pembukaan Open Call.",
            'category' => 'berita',
            'audience' => 'public',
            'is_published' => false,
        ]);
    }
}
