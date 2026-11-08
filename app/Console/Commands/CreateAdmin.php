<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdmin extends Command
{
    protected $signature = 'deepnest:create-admin {email : Surel admin} {--name=Admin DEEPNEST : Nama admin}';

    protected $description = 'Membuat akun admin DEEPNEST (atau menaikkan akun yang ada menjadi admin)';

    public function handle(): int
    {
        $email = strtolower((string) $this->argument('email'));
        $user = User::query()->where('email', $email)->first();

        if ($user) {
            if (! $this->confirm("Akun {$email} sudah ada sebagai {$user->role->label()}. Jadikan admin?")) {
                return self::FAILURE;
            }
            $old = $user->role->value;
            $user->forceFill(['role' => UserRole::Admin, 'is_active' => true])->save();
            Audit::log('user.role_changed', $user, ['role' => $old], ['role' => 'admin'], 'Dinaikkan menjadi admin melalui CLI');
            $this->info("{$email} kini admin.");

            return self::SUCCESS;
        }

        $password = (string) $this->secret('Kata sandi (min. 10 karakter, huruf dan angka)');
        $validator = Validator::make(['email' => $email, 'password' => $password], [
            'email' => ['required', 'email'],
            'password' => ['required', Password::min(10)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = new User(['name' => (string) $this->option('name'), 'email' => $email, 'password' => $password, 'institution' => 'Universitas Hasanuddin']);
        $user->forceFill(['role' => UserRole::Admin, 'is_active' => true, 'email_verified_at' => now()])->save();
        Audit::log('setup.admin_created', $user, [], ['email' => $email], 'Admin dibuat melalui CLI');

        $this->info("Admin {$email} berhasil dibuat.");

        return self::SUCCESS;
    }
}
