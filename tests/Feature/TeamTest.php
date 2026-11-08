<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class TeamTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    private function teamData(): array
    {
        return ['name' => 'Tim Hebat', 'institution' => 'Unhas', 'contact_email' => 'tim@contoh.test', 'contact_phone' => '081234567'];
    }

    public function test_participant_creates_team_and_becomes_leader(): void
    {
        $user = $this->participant();
        $this->actingAs($user)->post(route('portal.team.store'), $this->teamData())->assertRedirect(route('portal.team'));

        $team = Team::query()->firstOrFail();
        $this->assertTrue($team->isLeader($user));
        $this->assertTrue($team->hasActiveMember($user));
        $this->assertSame(8, strlen($team->join_code));
    }

    public function test_join_with_code_requires_leader_approval(): void
    {
        $leader = $this->participant();
        $team = $this->teamFor($leader);
        $member = $this->participant();

        $this->actingAs($member)->post(route('portal.team.join'), ['join_code' => strtolower($team->join_code)])->assertRedirect(route('portal.team'));
        $membership = $team->members()->where('user_id', $member->id)->first();
        $this->assertSame('pending', $membership->status);
        $this->assertFalse($team->hasActiveMember($member));

        $this->actingAs($leader)->post(route('portal.team.members.approve', $membership))->assertSessionHas('success');
        $this->assertTrue($team->fresh()->hasActiveMember($member));
    }

    public function test_one_team_per_participant_is_enforced(): void
    {
        $leaderA = $this->participant();
        $this->teamFor($leaderA);
        $teamB = $this->teamFor($this->participant());

        $this->actingAs($leaderA)->post(route('portal.team.join'), ['join_code' => $teamB->join_code])->assertSessionHas('error');
        $this->actingAs($leaderA)->post(route('portal.team.store'), $this->teamData())->assertSessionHas('error');
        $this->assertSame(2, Team::query()->count());
    }

    public function test_max_team_size_is_enforced(): void
    {
        Settings::set(['max_team_size' => 2]);
        $leader = $this->participant();
        $team = $this->teamFor($leader, [$this->participant()]);

        $this->actingAs($this->participant())->post(route('portal.team.join'), ['join_code' => $team->join_code])->assertSessionHas('error');
        $this->actingAs($leader)->post(route('portal.team.members.store'), ['name' => 'Tanpa Akun', 'discipline' => 'Gizi'])->assertSessionHas('error');
        $this->assertSame(2, $team->members()->count());
    }

    public function test_only_leader_can_manage_members(): void
    {
        $leader = $this->participant();
        $member = $this->participant();
        $team = $this->teamFor($leader, [$member]);
        $leaderRow = $team->members()->where('user_id', $leader->id)->first();

        $this->actingAs($member)->post(route('portal.team.members.store'), ['name' => 'X', 'discipline' => 'Y'])->assertForbidden();
        $this->actingAs($member)->delete(route('portal.team.members.destroy', $leaderRow))->assertForbidden();
        $this->actingAs($leader)->delete(route('portal.team.members.destroy', $leaderRow))->assertSessionHas('error');
    }

    public function test_leader_cannot_manage_other_teams_members(): void
    {
        $leaderA = $this->participant();
        $this->teamFor($leaderA);
        $teamB = $this->teamFor($this->participant(), [$this->participant()]);
        $foreign = $teamB->members()->where('role', 'member')->first();

        $this->actingAs($leaderA)->delete(route('portal.team.members.destroy', $foreign))->assertNotFound();
        $this->assertDatabaseHas('team_members', ['id' => $foreign->id]);
    }

    public function test_admin_can_add_and_remove_members(): void
    {
        $admin = $this->admin();
        $team = $this->teamFor($this->participant());
        $user = $this->participant(['email' => 'tambahan@contoh.test']);

        $this->actingAs($admin)->post(route('admin.teams.members.store', $team), ['email' => 'tambahan@contoh.test'])->assertSessionHas('success');
        $this->assertTrue($team->hasActiveMember($user));

        $row = $team->members()->where('user_id', $user->id)->first();
        $this->actingAs($admin)->delete(route('admin.teams.members.destroy', [$team, $row]))->assertSessionHas('success');
        $this->assertFalse($team->fresh()->hasActiveMember($user));
    }
}
