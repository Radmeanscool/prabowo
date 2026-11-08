<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();
        $team = $user->activeTeam();
        $team?->load(['members.user', 'leader', 'proposals']);

        return view('portal.team', [
            'user' => $user,
            'team' => $team,
            'pending' => $team ? null : $user->pendingMembership(),
            'isLeader' => $team?->isLeader($user) ?? false,
            'maxSize' => (int) setting('max_team_size', 6),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($this->alreadyInTeam($user)) {
            return back()->with('error', 'Anda sudah tergabung atau sedang menunggu persetujuan pada tim lain.');
        }

        $data = $request->validate($this->teamRules());

        $team = DB::transaction(function () use ($data, $user) {
            $team = new Team($data);
            $team->slug = Team::uniqueSlug($data['name']);
            $team->leader_id = $user->id;
            $team->join_code = Team::generateJoinCode();
            $team->save();

            $team->members()->create([
                'user_id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'institution' => $user->institution,
                'discipline' => $user->discipline,
                'role' => 'leader',
                'status' => 'active',
                'joined_at' => now(),
                'added_by' => $user->id,
            ]);

            return $team;
        });

        Audit::log('team.created', $team, [], ['name' => $team->name]);

        return redirect()->route('portal.team')->with('success', 'Tim berhasil dibuat. Bagikan kode tim kepada anggota agar mereka dapat bergabung.');
    }

    public function update(Request $request): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        $data = $request->validate($this->teamRules());
        $team->fill($data);
        [$old, $new] = Audit::diff($team);
        $team->save();
        if ($new) {
            Audit::log('team.updated', $team, $old, $new);
        }

        return back()->with('success', 'Profil tim diperbarui.');
    }

    public function join(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['join_code' => ['required', 'string', 'max:16']]);

        if ($this->alreadyInTeam($user)) {
            return back()->with('error', 'Anda sudah tergabung atau sedang menunggu persetujuan pada tim lain.');
        }

        $team = Team::query()->where('join_code', strtoupper(trim($data['join_code'])))->first();
        if (! $team) {
            return back()->withErrors(['join_code' => 'Kode tim tidak ditemukan.'])->withInput();
        }

        if ($team->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Anda sudah tercatat pada tim ini.');
        }

        if ($team->seatCount() >= (int) setting('max_team_size', 6)) {
            return back()->with('error', 'Tim ini sudah mencapai jumlah anggota maksimal.');
        }

        $needsApproval = (bool) setting('team_leader_approval_required', true);
        $team->members()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'institution' => $user->institution,
            'discipline' => $user->discipline,
            'role' => 'member',
            'status' => $needsApproval ? 'pending' : 'active',
            'joined_at' => $needsApproval ? null : now(),
            'added_by' => $user->id,
        ]);

        Audit::log('team.join_requested', $team, [], ['user_id' => $user->id, 'status' => $needsApproval ? 'pending' : 'active']);

        return redirect()->route('portal.team')->with('success', $needsApproval
            ? 'Permintaan bergabung terkirim. Menunggu persetujuan ketua tim.'
            : 'Anda berhasil bergabung dengan tim '.$team->name.'.');
    }

    public function cancelJoin(Request $request): RedirectResponse
    {
        $membership = $request->user()->pendingMembership();
        if ($membership) {
            Audit::log('team.join_cancelled', $membership->team, [], ['user_id' => $request->user()->id]);
            $membership->delete();
        }

        return redirect()->route('portal.team')->with('success', 'Permintaan bergabung dibatalkan.');
    }

    public function leave(Request $request): RedirectResponse
    {
        $user = $request->user();
        $team = $user->activeTeam();
        abort_unless($team, 404);

        if ($team->isLeader($user)) {
            return back()->with('error', 'Ketua tim tidak dapat keluar. Hubungi panitia untuk memindahkan peran ketua.');
        }

        $team->members()->where('user_id', $user->id)->delete();
        Audit::log('team.member_left', $team, ['user_id' => $user->id], []);

        return redirect()->route('portal.team')->with('success', 'Anda telah keluar dari tim.');
    }

    public function regenerateCode(Request $request): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        $team->forceFill(['join_code' => Team::generateJoinCode()])->save();
        Audit::log('team.code_regenerated', $team);

        return back()->with('success', 'Kode tim baru dibuat. Kode lama tidak berlaku lagi.');
    }

    public function addMember(Request $request): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        $data = $request->validate($this->memberRules());

        if ($team->seatCount() >= (int) setting('max_team_size', 6)) {
            return back()->with('error', 'Tim sudah mencapai jumlah anggota maksimal ('.setting('max_team_size', 6).' orang).');
        }

        if (! empty($data['email'])) {
            $email = strtolower($data['email']);
            if ($team->members()->where('email', $email)->exists()) {
                return back()->withErrors(['email' => 'Surel ini sudah tercatat di tim.'])->withInput();
            }
            $registered = User::query()->where('email', $email)->first();
            if ($registered && setting('one_team_per_participant', true) && $this->alreadyInTeam($registered)) {
                return back()->withErrors(['email' => 'Pemilik surel ini sudah tergabung di tim lain.'])->withInput();
            }
            $data['email'] = $email;
        }

        $member = $team->members()->create($data + [
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
            'added_by' => $request->user()->id,
        ]);

        Audit::log('team.member_added', $team, [], ['member_id' => $member->id, 'name' => $member->name]);

        return back()->with('success', 'Anggota ditambahkan. Anggota yang memiliki akun dapat bergabung sendiri memakai kode tim.');
    }

    public function updateMember(Request $request, TeamMember $member): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        $rules = $this->memberRules();
        if ($member->user_id) {
            // Nama dan surel anggota berakun mengikuti profil akunnya.
            unset($rules['name'], $rules['email'], $rules['phone']);
        }
        $data = $request->validate($rules);
        $member->fill($data);
        [$old, $new] = Audit::diff($member);
        $member->save();
        if ($new) {
            Audit::log('team.member_updated', $team, $old, $new + ['member_id' => $member->id]);
        }

        return back()->with('success', 'Data anggota diperbarui.');
    }

    public function approveMember(Request $request, TeamMember $member): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        abort_unless((int) $member->team_id === (int) $team->id && $member->status === 'pending', 404);

        $user = $member->user;
        if ($user && setting('one_team_per_participant', true) && $user->activeTeam()) {
            $member->delete();

            return back()->with('error', 'Peserta tersebut sudah bergabung di tim lain.');
        }

        $member->forceFill(['status' => 'active', 'joined_at' => now()])->save();
        Audit::log('team.member_approved', $team, ['status' => 'pending'], ['status' => 'active', 'member_id' => $member->id]);

        return back()->with('success', $member->name.' kini menjadi anggota tim.');
    }

    public function removeMember(Request $request, TeamMember $member): RedirectResponse
    {
        $team = $this->leaderTeam($request);
        abort_unless((int) $member->team_id === (int) $team->id, 404);

        if ($member->role === 'leader') {
            return back()->with('error', 'Ketua tim tidak dapat dihapus.');
        }

        Audit::log('team.member_removed', $team, ['member_id' => $member->id, 'name' => $member->name, 'status' => $member->status], []);
        $member->delete();

        return back()->with('success', 'Anggota dihapus dari tim.');
    }

    private function leaderTeam(Request $request): Team
    {
        $team = $request->user()->activeTeam();
        abort_unless($team, 404);
        $this->authorize('manageMembers', $team);

        return $team;
    }

    private function alreadyInTeam(User $user): bool
    {
        if (! setting('one_team_per_participant', true)) {
            return $user->teamMemberships()->where('status', 'pending')->exists();
        }

        return $user->teamMemberships()->whereIn('status', ['active', 'pending'])->whereHas('team')->exists();
    }

    private function teamRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['required', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'profile' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['required', 'email', 'max:190'],
            'contact_phone' => ['required', 'string', 'max:32', 'regex:/^[0-9+\-\s().]{6,32}$/'],
        ];
    }

    private function memberRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s().]{6,32}$/'],
            'institution' => ['nullable', 'string', 'max:190'],
            'discipline' => ['required', 'string', 'max:190'],
        ];
    }
}
