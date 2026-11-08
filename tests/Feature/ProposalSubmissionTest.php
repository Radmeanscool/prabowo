<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Models\Theme;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class ProposalSubmissionTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    public function test_theme_then_challenge_flow_creates_draft(): void
    {
        $leader = $this->participant();
        $this->teamFor($leader);
        $challenge = $this->publishedChallenge(Theme::FOOD_SECURITY);

        $this->actingAs($leader)->get(route('portal.proposals.create'))->assertOk()->assertSee($challenge->title);

        $this->actingAs($leader)->post(route('portal.proposals.store'), [
            'product_name' => 'Sensor Mutu Pakan',
            'theme_id' => $this->theme(Theme::FOOD_SECURITY)->id,
            'challenge_id' => $challenge->id,
        ])->assertRedirect();

        $proposal = Proposal::query()->firstOrFail();
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertSame($challenge->id, $proposal->challenge_id);
        $this->assertFalse($proposal->is_public);
    }

    public function test_challenge_must_be_eligible_for_selected_theme(): void
    {
        $leader = $this->participant();
        $this->teamFor($leader);
        $foodOnly = $this->publishedChallenge(Theme::FOOD_SECURITY, [Theme::FOOD_SECURITY]);

        $this->actingAs($leader)->post(route('portal.proposals.store'), [
            'product_name' => 'X',
            'theme_id' => $this->theme(Theme::HEALTH)->id,
            'challenge_id' => $foodOnly->id,
        ])->assertSessionHasErrors('challenge_id');
        $this->assertSame(0, Proposal::query()->count());
    }

    public function test_draft_challenge_cannot_be_selected(): void
    {
        $leader = $this->participant();
        $this->teamFor($leader);
        $challenge = $this->publishedChallenge();
        $challenge->update(['status' => 'draft']);

        $this->actingAs($leader)->post(route('portal.proposals.store'), [
            'product_name' => 'X', 'theme_id' => $challenge->theme_id, 'challenge_id' => $challenge->id,
        ])->assertSessionHasErrors('challenge_id');
    }

    public function test_participant_saves_structured_draft(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader), null, ProposalStatus::Draft, false);

        $this->actingAs($leader)->put(route('portal.proposals.update', $proposal), [
            'product_name' => 'Nama Baru',
            'technical_description' => 'Proses produksi dan komponen.',
            'market_validation' => 'Survei 100 responden.',
            'problem_statement' => 'Masalah mitra.',
            'ip_status' => 'filed',
            'data_risk_status' => 'health',
            'tkt_level' => 5,
            'demo_video_url' => 'https://contoh.test/video',
        ])->assertRedirect();

        $proposal->refresh();
        $this->assertSame('Nama Baru', $proposal->product_name);
        $this->assertSame('filed', $proposal->ip_status);
        $this->assertSame(5, $proposal->tkt_level);
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
    }

    public function test_status_and_publication_fields_cannot_be_injected_by_participant(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));

        $this->actingAs($leader)->put(route('portal.proposals.update', $proposal), [
            'product_name' => 'Aman', 'status' => 'approved', 'is_public' => '1', 'public_slug' => 'retas',
            'public_pdf_enabled' => '1', 'team_id' => 999, 'approved_at' => now()->toDateTimeString(),
        ])->assertRedirect();

        $proposal->refresh();
        $this->assertSame('Aman', $proposal->product_name);
        $this->assertSame(ProposalStatus::Draft, $proposal->status);
        $this->assertFalse($proposal->is_public);
        $this->assertFalse($proposal->public_pdf_enabled);
        $this->assertNull($proposal->public_slug);
        $this->assertNull($proposal->approved_at);
        $this->assertNotSame(999, $proposal->team_id);
    }

    public function test_invalid_controlled_values_are_rejected(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));

        $this->actingAs($leader)->put(route('portal.proposals.update', $proposal), [
            'product_name' => 'X', 'ip_status' => 'legal_compliant', 'tkt_level' => 12, 'demo_video_url' => 'javascript:alert(1)',
        ])->assertSessionHasErrors(['ip_status', 'tkt_level', 'demo_video_url']);
    }

    public function test_incomplete_proposal_cannot_be_submitted(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader), null, ProposalStatus::Draft, false);

        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertSessionHas('error');
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
    }

    public function test_complete_proposal_submission_records_version_and_audit(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));

        $this->actingAs($leader)->get(route('portal.proposals.confirm', $proposal))->assertOk()->assertSee('Kirim Proposal');
        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertRedirect(route('portal.proposals.status', $proposal));

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Submitted, $proposal->status);
        $this->assertSame(1, $proposal->version);
        $this->assertNotNull($proposal->submitted_at);
        $this->assertDatabaseHas('proposal_versions', ['proposal_id' => $proposal->id, 'version' => 1]);
        $this->assertTrue(AuditLog::query()->where('action', 'proposal.submitted')->where('auditable_id', $proposal->id)->exists());
        $this->assertFalse($proposal->is_public);
    }

    public function test_submission_requires_open_call(): void
    {
        Settings::set(['open_call_mode' => 'closed']);
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));

        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertSessionHas('error');
        $this->assertSame(ProposalStatus::Draft, $proposal->fresh()->status);
    }

    public function test_only_team_leader_can_submit(): void
    {
        $leader = $this->participant();
        $member = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader, [$member]));

        $this->actingAs($member)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertForbidden();
    }

    public function test_submitted_proposal_is_locked_and_duplicate_submission_blocked(): void
    {
        $proposal = $this->submittedProposal();
        $leader = $proposal->team->leader;

        $this->actingAs($leader)->put(route('portal.proposals.update', $proposal), ['product_name' => 'Ubah'])->assertForbidden();
        $this->actingAs($leader)->put(route('portal.proposals.challenge.update', $proposal), ['theme_id' => $this->theme(Theme::HEALTH)->id])->assertForbidden();
        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertForbidden();
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'proposal_pdf', 'file' => $this->pdfUpload()])->assertForbidden();

        $this->assertSame(1, $proposal->fresh()->version);
        $this->assertNotSame('Ubah', $proposal->fresh()->product_name);
    }

    public function test_reopened_proposal_allows_audited_theme_change_and_resubmission(): void
    {
        $proposal = $this->submittedProposal();
        $leader = $proposal->team->leader;
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'revision_requested', 'note' => 'Perjelas indikator.'])->assertRedirect();
        $this->assertSame(ProposalStatus::RevisionRequested, $proposal->fresh()->status);

        $health = $this->publishedChallenge(Theme::HEALTH);
        $this->actingAs($leader)->put(route('portal.proposals.challenge.update', $proposal), [
            'theme_id' => $health->theme_id, 'challenge_id' => $health->id,
        ])->assertRedirect();
        $this->assertTrue(AuditLog::query()->where('action', 'proposal.theme_changed_after_reopen')->where('auditable_id', $proposal->id)->exists());

        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertRedirect();
        $proposal->refresh();
        $this->assertSame(ProposalStatus::Submitted, $proposal->status);
        $this->assertSame(2, $proposal->version);
        $this->assertTrue(AuditLog::query()->where('action', 'proposal.resubmitted')->exists());
    }

    public function test_other_participants_cannot_view_or_edit_a_proposal(): void
    {
        $proposal = $this->proposalFor($this->teamFor($this->participant()));
        $outsider = $this->participant();
        $this->teamFor($outsider);

        $this->actingAs($outsider)->get(route('portal.proposals.show', $proposal))->assertForbidden();
        $this->actingAs($outsider)->get(route('portal.proposals.edit', $proposal))->assertForbidden();
        $this->actingAs($outsider)->put(route('portal.proposals.update', $proposal), ['product_name' => 'Retas'])->assertForbidden();
    }

    public function test_word_limit_blocks_submission(): void
    {
        Settings::set(['proposal_word_limit' => 50]);
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));
        $proposal->forceFill(['technical_description' => str_repeat('kata ', 60)])->save();

        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertSessionHas('error');
    }

    public function test_team_proposal_limit_is_enforced(): void
    {
        $leader = $this->participant();
        $team = $this->teamFor($leader);
        $this->proposalFor($team);

        $this->actingAs($leader)->get(route('portal.proposals.create'))->assertRedirect(route('portal.proposals.index'));
    }
}
