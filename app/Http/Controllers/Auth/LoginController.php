<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            Audit::log('auth.failed', null, [], ['email' => $credentials['email']], 'Percobaan masuk gagal');

            throw ValidationException::withMessages([
                'email' => 'Surel atau kata sandi tidak sesuai.',
            ]);
        }

        $user = $request->user();
        if (! $user->is_active) {
            Auth::guard('web')->logout();
            throw ValidationException::withMessages(['email' => 'Akun Anda dinonaktifkan. Hubungi panitia.']);
        }

        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();
        Audit::log('auth.login', $user, [], [], 'Masuk ke portal', $user);

        return redirect()->intended(route($user->dashboardRoute()));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
