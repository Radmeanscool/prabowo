<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Wizard pembuatan admin pertama untuk hosting tanpa SSH.
 * Hanya tersedia bila belum ada admin DAN SETUP_TOKEN diisi di .env.
 */
class SetupController extends Controller
{
    private function available(): bool
    {
        return filled(config('deepnest.setup_token'))
            && ! User::query()->where('role', UserRole::Admin->value)->exists();
    }

    public function create(): View
    {
        abort_unless($this->available(), 404);

        return view('auth.setup');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($this->available(), 404);

        $data = $request->validate([
            'token' => ['required', 'string'],
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(10)->letters()->numbers()],
        ]);

        if (! hash_equals((string) config('deepnest.setup_token'), (string) $data['token'])) {
            return back()->withInput($request->except('password', 'password_confirmation', 'token'))
                ->withErrors(['token' => 'Token setup tidak valid.']);
        }

        $user = new User([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'],
            'institution' => 'Universitas Hasanuddin',
        ]);
        $user->forceFill(['role' => UserRole::Admin, 'is_active' => true, 'email_verified_at' => now()])->save();

        Audit::log('setup.admin_created', $user, [], ['email' => $user->email], 'Admin pertama dibuat melalui wizard setup', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.dashboard')->with('success', 'Admin pertama berhasil dibuat. Segera hapus SETUP_TOKEN dari berkas .env.');
    }
}
