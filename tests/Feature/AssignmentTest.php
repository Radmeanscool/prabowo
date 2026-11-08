<?php

namespace Tests\Feature;

use App\Models\ConflictDeclaration;
use App\Models\MentorAssignment;
use App\Models\Person;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class AssignmentTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    private ReviewPhase $phase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
        $this->phase = ReviewPhase::query()->where('code', 'open_call')->firstOrFail();
    }

    private function post_assign($admin, $proposal, $reviewer, bool $confirm = false)
    {
        return $this->actingAs($admin)->post(route('admin.assignments.store'), array_filter([
            'proposal_id' => $proposal->id,
            'reviewer_id' => $reviewer->id,
            'review_phase_id' => $this->phase->id,
            'confirm_warnings' => $confirm ? '1' : null,
        ]));
    }

    public function test_draft_proposals_cannot_be_assigned(): void
    {
        $proposal = $this->proposalFor($this->teamFor($this->participant()));
        $this->post_assign($this->admin(), $proposal, $this->reviewer())->assertSessionHas('error');
        $this->assertSame(0, ReviewerAssignment::query()->count());
    }

    public function test_team_member_reviewer_is_blocked(): void
    {
        $reviewerUser = $this->reviewer();
        $leader = $this->participant();
        $team = $this->teamFor($leader);
        $team->members()->create(['user_id' => $reviewerUser->id, 'name' => $reviewerUser->name, 'email' => $reviewerUser->email, 'role' => 'member', 'status' => 'active']);
        $proposal = $this->proposalFor($team);
        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1']);

        $this->post_assign($this->admin(), $proposal->fresh(), $reviewerUser, true)->assertSessionHas('error');
        $this->assertSame(0, ReviewerAssignment::query()->count());
    }

    public function test_juror_who_mentors_the_team_is_blocked(): void
    {
        $proposal = $this->submittedProposal();
        $juror = $this->reviewer();
        $person = Person::query()->create(['name' => 'Juri Mentor', 'slug' => 'juri-mentor', 'type' => 'mentor', 'user_id' => $juror->id]);
        MentorAssignment::query()->create(['person_id' => $person->id, 'team_id' => $proposal->team_id]);

        $this->post_assign($this->admin(), $proposal, $juror, true)->assertSessionHas('error');
        $this->assertSame(0, ReviewerAssignment::query()->count());
    }

    public function test_declared_institution_conflict_is_blocked(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        ConflictDeclaration::query()->create(['reviewer_id' => $reviewer->id, 'institution' => 'institut contoh', 'reason' => 'Mantan pegawai.']);

        $this->post_assign($this->admin(), $proposal, $reviewer, true)->assertSessionHas('error');
    }

    public function test_same_institution_requires_confirmation(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer(['institution' => 'Institut Contoh']);
        $admin = $this->admin();

        $this->post_assign($admin, $proposal, $reviewer)->assertSessionHas('error');
        $this->assertSame(0, ReviewerAssignment::query()->count());

        $this->post_assign($admin, $proposal, $reviewer, true)->assertSessionHas('success');
        $assignment = ReviewerAssignment::query()->firstOrFail();
        $this->assertStringContainsString('Peringatan ditinjau', (string) $assignment->override_note);
    }

    public function test_duplicate_assignment_is_rejected(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer(['institution' => 'Lain']);
        $admin = $this->admin();

        $this->post_assign($admin, $proposal, $reviewer)->assertSessionHas('success');
        $this->post_assign($admin, $proposal, $reviewer)->assertSessionHas('error');
        $this->assertSame(1, ReviewerAssignment::query()->count());
    }

    public function test_bulk_assignment_skips_conflicts(): void
    {
        $admin = $this->admin();
        $p1 = $this->submittedProposal();
        $p2 = $this->submittedProposal();
        $r1 = $this->reviewer(['institution' => 'BRIN']);
        $r2 = $this->reviewer(['institution' => 'Kemenkes']);
        ConflictDeclaration::query()->create(['reviewer_id' => $r2->id, 'proposal_id' => $p2->id, 'team_id' => $p2->team_id, 'reason' => 'Keluarga.']);

        $this->actingAs($admin)->post(route('admin.assignments.bulk'), [
            'proposal_ids' => [$p1->id, $p2->id],
            'reviewer_ids' => [$r1->id, $r2->id],
            'review_phase_id' => $this->phase->id,
        ])->assertSessionHas('success')->assertSessionHas('warning');

        $this->assertSame(3, ReviewerAssignment::query()->count());
        $this->assertFalse(ReviewerAssignment::query()->where('proposal_id', $p2->id)->where('reviewer_id', $r2->id)->exists());
    }

    public function test_remove_and_replace_keep_history(): void
    {
        $admin = $this->admin();
        $proposal = $this->submittedProposal();
        $r1 = $this->reviewer(['institution' => 'A']);
        $r2 = $this->reviewer(['institution' => 'B']);
        $this->post_assign($admin, $proposal, $r1)->assertSessionHas('success');
        $assignment = ReviewerAssignment::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.assignments.replace', $assignment), ['reviewer_id' => $r2->id])->assertSessionHas('success');
        $this->assertSame('removed', $assignment->fresh()->status);
        $this->assertTrue(ReviewerAssignment::query()->where('reviewer_id', $r2->id)->where('status', 'assigned')->exists());

        $this->actingAs($r1)->get(route('reviewer.assignments.show', $assignment))->assertForbidden();

        $new = ReviewerAssignment::query()->where('reviewer_id', $r2->id)->first();
        $this->actingAs($admin)->post(route('admin.assignments.remove', $new), ['reason' => 'Beban kerja.'])->assertSessionHas('success');
        $this->assertSame(2, ReviewerAssignment::query()->count());
    }

    public function test_reviewer_self_selection_disabled_by_default(): void
    {
        $reviewer = $this->reviewer();
        $proposal = $this->submittedProposal();

        $this->actingAs($reviewer)->get(route('reviewer.self-select.index'))->assertNotFound();
        $this->actingAs($reviewer)->post(route('reviewer.self-select.store', $proposal))->assertNotFound();

        Settings::set(['reviewer_self_selection_enabled' => true]);
        $this->actingAs($reviewer)->post(route('reviewer.self-select.store', $proposal))->assertRedirect();
        $this->assertTrue(ReviewerAssignment::query()->where('reviewer_id', $reviewer->id)->exists());
    }

    public function test_assignment_page_shows_workload(): void
    {
        $admin = $this->admin();
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer(['name' => 'Juri Beban', 'institution' => 'X']);
        $this->post_assign($admin, $proposal, $reviewer);

        $this->actingAs($admin)->get(route('admin.assignments.index'))->assertOk()->assertSee('Juri Beban')->assertSee('1 terbuka');
    }
}
