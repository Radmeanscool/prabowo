<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pemeriksaan cepat setelah deploy: versi PHP, ekstensi, izin folder, basis data, dan pengaturan keamanan.
 */
class CheckInstallation extends Command
{
    protected $signature = 'deepnest:check';

    protected $description = 'Memeriksa kesiapan instalasi DEEPNEST di server';

    public function handle(): int
    {
        $rows = [];
        $ok = true;
        $check = function (string $label, bool $pass, string $hint = '') use (&$rows, &$ok) {
            $rows[] = [$pass ? 'OK' : 'PERLU DIPERBAIKI', $label, $pass ? '' : $hint];
            $ok = $ok && $pass;
        };

        $check('PHP >= 8.3 (saat ini '.PHP_VERSION.')', version_compare(PHP_VERSION, '8.3.0', '>='), 'Pilih PHP 8.3+ di cPanel > Select PHP Version.');
        foreach (['pdo_mysql', 'mbstring', 'openssl', 'fileinfo', 'tokenizer', 'xml', 'ctype', 'json', 'gd'] as $ext) {
            $check("Ekstensi {$ext}", extension_loaded($ext), "Aktifkan ekstensi {$ext} di cPanel.");
        }
        $check('APP_KEY terisi', filled(config('app.key')), 'Jalankan php artisan key:generate.');
        $check('APP_DEBUG=false di produksi', ! (app()->isProduction() && config('app.debug')), 'Set APP_DEBUG=false.');
        $check('Folder storage/ dapat ditulis', is_writable(storage_path('app/private')) || is_writable(storage_path()), 'Set izin folder storage/ ke 755/775.');
        $check('Folder bootstrap/cache dapat ditulis', is_writable(base_path('bootstrap/cache')), 'Set izin bootstrap/cache ke 755/775.');
        $check('Aset terbangun (public/build/manifest.json)', file_exists(public_path('build/manifest.json')), 'Jalankan npm run build di komputer lokal lalu unggah public/build.');

        try {
            DB::connection()->getPdo();
            $check('Koneksi basis data', true);
            $check('Tabel sudah dimigrasi', Schema::hasTable('proposals') && Schema::hasTable('settings'), 'Jalankan php artisan migrate --seed atau impor database/deepnest-install.sql.');
            $hasAdmin = Schema::hasTable('users') && User::query()->where('role', UserRole::Admin->value)->exists();
            $check('Minimal satu admin', $hasAdmin, 'Gunakan /setup (isi SETUP_TOKEN) atau php artisan deepnest:create-admin.');
            if ($hasAdmin && filled(config('deepnest.setup_token'))) {
                $check('SETUP_TOKEN sudah dikosongkan', false, 'Admin sudah ada; kosongkan SETUP_TOKEN di .env.');
            }
        } catch (Throwable $e) {
            $check('Koneksi basis data', false, $e->getMessage());
        }

        $this->table(['Status', 'Pemeriksaan', 'Saran'], $rows);
        $ok ? $this->info('Instalasi siap.') : $this->warn('Ada butir yang perlu diperbaiki.');

        return $ok ? self::SUCCESS : self::FAILURE;
    }
}
