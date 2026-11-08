<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register', [
            'enabled' => (bool) setting('registration_enabled', true),
            'participantTypes' => Options::participantTypes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(setting('registration_enabled', true), 403, 'Pendaftaran sedang ditutup.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:190', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s().]{6,32}$/'],
            'institution' => ['required', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'discipline' => ['required', 'string', 'max:190'],
            'participant_type' => ['required', Rule::in(array_keys(Options::participantTypes()))],
            'password' => ['required', 'confirmed', Password::defaults()],
            'consent' => ['accepted'],
        ], [
            'consent.accepted' => 'Anda harus menyetujui ketentuan program dan pemrosesan data.',
        ]);

        // Peran selalu "participant"; reviewer dan admin hanya dibuat oleh admin.
        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'institution' => $data['institution'],
            'city' => $data['city'],
            'province' => $data['province'],
            'discipline' => $data['discipline'],
            'participant_type' => $data['participant_type'],
            'password' => $data['password'],
            'consented_at' => now(),
        ]);

        Audit::log('user.registered', $user, [], ['role' => 'participant'], 'Pendaftaran peserta', $user);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('portal.team')->with('success', 'Akun berhasil dibuat. Langkah berikutnya: buat tim atau bergabung dengan tim.');
    }
}
