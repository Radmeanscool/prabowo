<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Data rujukan produksi. Aman dijalankan berulang (idempotent).
 * Data contoh untuk uji lokal ada di DemoSeeder dan TIDAK boleh dijalankan di produksi.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ThemeSeeder::class,
            ProgramStageSeeder::class,
            ReviewPhaseSeeder::class,
            ChallengeOwnerSeeder::class,
            ChallengeSeeder::class,
            AgendaSeeder::class,
            PageSeeder::class,
            FaqSeeder::class,
            AnnouncementSeeder::class,
            AdminUserSeeder::class,
        ]);
    }
}
