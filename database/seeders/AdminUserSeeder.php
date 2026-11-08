<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Membuat admin pertama dari variabel lingkungan ADMIN_EMAIL dan ADMIN_PASSWORD (opsional).
 * Alternatif: perintah `php artisan deepnest:create-admin` atau wizard /setup.
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password || User::query()->where('email', $email)->exists()) {
            return;
        }

        $user = new User([
            'name' => env('ADMIN_NAME', 'Admin DEEPNEST'),
            'email' => $email,
            'password' => $password,
            'institution' => 'Universitas Hasanuddin',
        ]);
        $user->forceFill(['role' => UserRole::Admin, 'is_active' => true, 'email_verified_at' => now()])->save();

        $this->command?->info("Admin {$email} dibuat. Hapus ADMIN_PASSWORD dari .env setelah login pertama.");
    }
}
