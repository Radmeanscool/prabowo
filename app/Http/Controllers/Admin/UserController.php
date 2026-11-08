<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Support\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $users = User::query()
            ->when($request->query('role'), fn ($q, $role) => $q->where('role', $role))
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$term}%")->orWhere('email', 'like', "%{$term}%")->orWhere('institution', 'like', "%{$term}%")))
            ->withCount(['reviewerAssignments as open_assignments' => fn ($q) => $q->open()])
            ->orderBy('name')
            ->paginate(25)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => UserRole::options()]);
    }

    public function create(): View
    {
        return view('admin.users.form', ['user' => new User(['role' => UserRole::Reviewer]), 'roles' => UserRole::options(), 'participantTypes' => Options::participantTypes()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate($this->rules(null));
        $user = new User(collect($data)->except(['role', 'password'])->all());
        $user->password = $data['password'];
        $user->forceFill(['role' => UserRole::from($data['role']), 'is_active' => true, 'email_verified_at' => now()])->save();

        Audit::log('user.created', $user, [], ['email' => $user->email, 'role' => $user->role->value]);

        return redirect()->route('admin.users.index')->with('success', 'Akun '.$user->email.' dibuat sebagai '.$user->role->label().'. Sampaikan kata sandi sementara secara aman dan minta pengguna menggantinya.');
    }

    public function edit(User $user): View
    {
        return view('admin.users.form', ['user' => $user, 'roles' => UserRole::options(), 'participantTypes' => Options::participantTypes()]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate($this->rules($user));

        if ($user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            return back()->with('error', 'Anda tidak dapat menurunkan peran akun Anda sendiri.');
        }

        $oldRole = $user->role->value;
        $user->fill(collect($data)->except(['role', 'password'])->all());
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->forceFill(['role' => UserRole::from($data['role'])]);
        $user->save();

        if ($oldRole !== $user->role->value) {
            Audit::log('user.role_changed', $user, ['role' => $oldRole], ['role' => $user->role->value], 'Peran pengguna diubah');
        }
        if (! empty($data['password'])) {
            Audit::log('user.password_reset_by_admin', $user, [], [], 'Kata sandi diatur ulang oleh admin');
        }

        return redirect()->route('admin.users.index')->with('success', 'Akun diperbarui.');
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();
        Audit::log($user->is_active ? 'user.activated' : 'user.deactivated', $user, ['is_active' => ! $user->is_active], ['is_active' => $user->is_active]);

        return back()->with('success', $user->is_active ? 'Akun diaktifkan.' : 'Akun dinonaktifkan. Pengguna tidak dapat masuk.');
    }

    private function rules(?User $user): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:190', Rule::unique('users', 'email')->ignore($user?->id)],
            'role' => ['required', Rule::in(array_keys(UserRole::options()))],
            'phone' => ['nullable', 'string', 'max:32'],
            'institution' => ['nullable', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'discipline' => ['nullable', 'string', 'max:190'],
            'participant_type' => ['nullable', Rule::in(array_keys(Options::participantTypes()))],
            'expertise' => ['nullable', 'string', 'max:2000'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', Password::defaults()],
        ];
    }
}
