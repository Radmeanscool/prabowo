<?php

namespace App\Providers;

use App\Models\ChallengeOwner;
use App\Models\Document;
use App\Models\Proposal;
use Carbon\Carbon;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Carbon::setLocale('id');
        Paginator::defaultView('components.pagination');

        Password::defaults(fn () => Password::min(8)->letters()->numbers());

        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $notifiable->getEmailForPasswordReset()]);

            return (new MailMessage)
                ->subject('Atur ulang kata sandi DEEPNEST')
                ->greeting('Halo '.$notifiable->name.',')
                ->line('Kami menerima permintaan untuk mengatur ulang kata sandi akun DEEPNEST Anda.')
                ->action('Atur Ulang Kata Sandi', $url)
                ->line('Tautan ini berlaku selama '.config('auth.passwords.users.expire', 60).' menit. Abaikan surel ini jika Anda tidak memintanya.')
                ->salutation('Salam, Tim Pelaksana DEEPNEST');
        });

        if (config('deepnest.force_https')) {
            URL::forceScheme('https');
        }

        // Proposal aktif tim untuk navigasi portal peserta.
        View::composer('components.layouts.portal', function ($view) {
            $user = auth()->user();
            $portalProposal = null;
            if ($user?->isParticipant()) {
                $team = $user->activeTeam();
                $portalProposal = $team ? Proposal::query()->where('team_id', $team->id)->latest('id')->first() : null;
            }
            $view->with('portalProposal', $portalProposal);
        });

        // Navigasi situs publik hanya menautkan bagian yang sudah berisi data terbit,
        // agar pengunjung tidak diarahkan ke halaman kosong (mis. katalog produk sebelum ada yang disetujui).
        View::composer('components.layouts.public', function ($view) {
            $view->with('publicNav', [
                'products' => Proposal::query()->publicProducts()->exists(),
                'owners' => ChallengeOwner::query()->public()->exists(),
                'documents' => Document::query()->published()->where('visibility', 'public')->exists(),
            ]);
        });
    }
}
