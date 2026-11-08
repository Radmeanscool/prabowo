<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MentorAssignment;
use App\Models\Person;
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $teams = Team::query()
            ->when($request->query('q'), fn ($q, $term) => $q->where(fn ($qq) => $qq->where('name', 'like', "%{$term}%")->orWhere('institution', 'like', "%{$term}%")))
            ->with(['leader', 'proposals.theme'])
            ->withCount(['members as active_members' => fn ($q) => $q->where('status', 'active'), 'members as pending_members' => fn ($q) => $q->where('status', 'pending')])
            ->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.teams.index', compact('teams'));
    }

    public function show(Team $team): View
    {
        $team->load(['members.user', 'leader', 'proposals.theme', 'proposals.challenge', 'mentorAssignments.person']);

        return view('admin.teams.show', [
            'team' => $team,
            'mentors' => Person::query()->where('type', 'mentor')->orderBy('name')->get(),
            'maxSize' => (int) setting('max_team_size', 6),
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'institution' => ['required', 'string', 'max:190'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'profile' => ['nullable', 'string', 'max:2000'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'leader_member_id' => ['nullable', Rule::exists('team_members', 'id')->where('team_id', $team->id)->where('status', 'active')->whereNotNull('user_id')],
        ]);

        $team->fill(collect($data)->except('leader_member_id')->all());
        [$old, $new] = Audit::diff($team);
        $team->save();
        if ($new) {
            Audit::log('team.updated_by_admin', $team, $old, $new);
        }

        if (! empty($data['leader_member_id'])) {
            $member = TeamMember::query()->findOrFail($data['leader_member_id']);
            if ((int) $member->user_id !== (int) $team->leader_id) {
                $oldLeader = $team->leader_id;
                $team->members()->where('role', 'leader')->update(['role' => 'member']);
                $member->forceFill(['role' => 'leader'])->save();
                $team->forceFill(['leader_id' => $member->user_id])->save();
                Audit::log('team.leader_changed', $team, ['leader_id' => $oldLeader], ['leader_id' => $member->user_id]);
            }
        }

        return back()->with('success', 'Data tim diperbarui.');
    }

    public function storeMember(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['nullable', 'email', 'max:190'],
            'name' => ['required_without:email', 'nullable', 'string', 'max:150'],
            'discipline' => ['nullable', 'string', 'max:190'],
            'institution' => ['nullable', 'string', 'max:190'],
        ]);

        if ($team->seatCount() >= (int) setting('max_team_size', 6)) {
            return back()->with('error', 'Tim sudah mencapai jumlah anggota maksimal.');
        }

        $user = ! empty($data['email']) ? User::query()->where('email', strtolower($data['email']))->first() : null;
        if ($user && $team->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'Pengguna ini sudah menjadi anggota tim.');
        }
        if ($user && setting('one_team_per_participant', true) && $user->teamMemberships()->whereIn('status', ['active', 'pending'])->whereHas('team')->exists()) {
            return back()->with('error', 'Pengguna ini sudah tergabung di tim lain.');
        }

        $member = $team->members()->create([
            'user_id' => $user?->id,
            'name' => $user?->name ?? $data['name'],
            'email' => $user?->email ?? (! empty($data['email']) ? strtolower($data['email']) : null),
            'phone' => $user?->phone,
            'institution' => $data['institution'] ?? $user?->institution,
            'discipline' => $data['discipline'] ?? $user?->discipline,
            'role' => 'member',
            'status' => 'active',
            'joined_at' => now(),
            'added_by' => $request->user()->id,
        ]);

        Audit::log('team.member_added_by_admin', $team, [], ['member_id' => $member->id, 'user_id' => $user?->id]);

        return back()->with('success', 'Anggota ditambahkan.');
    }

    public function updateMember(Request $request, Team $team, TeamMember $member): RedirectResponse
    {
        abort_unless((int) $member->team_id === (int) $team->id, 404);
        $data = $request->validate([
            'status' => ['required', Rule::in(['active', 'pending', 'rejected'])],
            'discipline' => ['nullable', 'string', 'max:190'],
            'institution' => ['nullable', 'string', 'max:190'],
        ]);
        if ($member->isLeader() && $data['status'] !== 'active') {
            return back()->with('error', 'Ketua tim harus berstatus aktif.');
        }
        $member->fill($data);
        [$old, $new] = Audit::diff($member);
        $member->forceFill(['joined_at' => $member->joined_at ?? ($data['status'] === 'active' ? now() : null)])->save();
        if ($new) {
            Audit::log('team.member_updated_by_admin', $team, $old, $new + ['member_id' => $member->id]);
        }

        return back()->with('success', 'Data anggota diperbarui.');
    }

    public function destroyMember(Team $team, TeamMember $member): RedirectResponse
    {
        abort_unless((int) $member->team_id === (int) $team->id, 404);
        if ($member->isLeader()) {
            return back()->with('error', 'Pindahkan peran ketua sebelum menghapus anggota ini.');
        }
        Audit::log('team.member_removed_by_admin', $team, ['member_id' => $member->id, 'name' => $member->name], []);
        $member->delete();

        return back()->with('success', 'Anggota dihapus dari tim.');
    }

    public function storeMentor(Request $request, Team $team): RedirectResponse
    {
        $data = $request->validate([
            'person_id' => ['required', Rule::exists('people', 'id')->where('type', 'mentor')],
            'role' => ['nullable', Rule::in(['internal', 'partner'])],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $assignment = MentorAssignment::query()->firstOrCreate(
            ['person_id' => $data['person_id'], 'team_id' => $team->id],
            ['role' => $data['role'] ?? null, 'notes' => $data['notes'] ?? null, 'assigned_by' => $request->user()->id, 'proposal_id' => $team->proposals()->latest('id')->value('id')]
        );
        Audit::log('mentor.assigned', $assignment, [], ['person_id' => $assignment->person_id, 'team_id' => $team->id]);

        return back()->with('success', 'Mentor ditugaskan ke tim.');
    }

    public function destroyMentor(Team $team, MentorAssignment $mentorAssignment): RedirectResponse
    {
        abort_unless((int) $mentorAssignment->team_id === (int) $team->id, 404);
        Audit::log('mentor.unassigned', $mentorAssignment, ['person_id' => $mentorAssignment->person_id, 'team_id' => $team->id], []);
        $mentorAssignment->delete();

        return back()->with('success', 'Penugasan mentor dicabut.');
    }
}
