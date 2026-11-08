<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('portal.profile', [
            'user' => $request->user(),
            'participantTypes' => Options::participantTypes(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s().]{6,32}$/'],
            'institution' => ['required', 'string', 'max:190'],
            'city' => ['required', 'string', 'max:100'],
            'province' => ['required', 'string', 'max:100'],
            'discipline' => ['required', 'string', 'max:190'],
            'participant_type' => ['required', Rule::in(array_keys(Options::participantTypes()))],
            'expertise' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['email'] = strtolower($data['email']);
        $user->fill($data)->save();

        // Sinkronkan data keanggotaan tim yang terhubung ke akun ini.
        $user->teamMemberships()->update([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}
