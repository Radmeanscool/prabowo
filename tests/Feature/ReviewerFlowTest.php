<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Review;
use App\Models\ReviewCriterion;
use App\Models\ReviewPhase;
use App\Services\ScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class ReviewerFlowTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    private function scorePayload(int $value, array $overrides = []): array
    {
        $criteria = ReviewPhase::query()->where('code', 'open_call')->firstOrFail()->criteria;

        return array_merge([
            'scores' => $criteria->mapWithKeys(fn ($c) => [$c->id => $value])->all(),
            'recommendation' => 'proceed',
            'overall_comment' => 'Baik.',
            'private_notes' => 'Catatan pribadi.',
        ], $overrides);
    }

    public function test_reviewer_sees_only_assigned_proposals(): void
    {
        $assigned = $this->submittedProposal();
        $other = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($assigned, $reviewer);

        $this->actingAs($reviewer)->get(route('reviewer.assignments.index'))
            ->assertOk()->assertSee($assigned->product_name)->assertDontSee($other->product_name);
        $this->actingAs($reviewer)->get(route('reviewer.assignments.show', $assignment))->assertOk()->assertSee('RAHASIA-TEKNIS');

        $otherAssignment = $this->assign($other, $this->reviewer());
        $this->actingAs($reviewer)->get(route('reviewer.assignments.show', $otherAssignment))->assertForbidden();
        $this->actingAs($reviewer)->get(route('files.show', $other->currentFile('proposal_pdf')->uuid))->assertForbidden();

        $this->assertSame(ProposalStatus::UnderReview, $assigned->fresh()->status);
    }

    public function test_reviewer_saves_draft_then_submits_and_review_is_locked(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($proposal, $reviewer);

        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(3) + ['action' => 'draft'])->assertRedirect();
        $review = Review::query()->firstOrFail();
        $this->assertSame('draft', $review->status);
        $this->assertSame('in_progress', $assignment->fresh()->status);

        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(4) + ['action' => 'submit', 'weighted_score' => 100])->assertRedirect();
        $review->refresh();
        $this->assertSame('submitted', $review->status);
        $this->assertEquals(80.0, (float) $review->weighted_score, 'Skor harus dihitung server (4/5 = 80), bukan dari input klien.');
        $this->assertSame('submitted', $assignment->fresh()->status);
        $this->assertTrue(AuditLog::query()->where('action', 'review.submitted')->exists());

        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(1) + ['action' => 'submit'])->assertSessionHas('error');
        $this->assertEquals(80.0, (float) $review->fresh()->weighted_score);
    }

    public function test_final_submission_requires_all_scores_and_range(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($proposal, $reviewer);
        $first = ReviewPhase::query()->where('code', 'open_call')->first()->criteria->first();

        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), ['action' => 'submit', 'recommendation' => 'proceed', 'scores' => [$first->id => 4]])
            ->assertSessionHasErrors();
        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(9) + ['action' => 'submit'])
            ->assertSessionHasErrors();
        $this->assertSame(0, Review::query()->where('status', 'submitted')->count());
    }

    public function test_weighted_score_uses_criterion_weights(): void
    {
        $phase = ReviewPhase::query()->where('code', 'open_call')->first();
        $criteria = $phase->criteria;
        $scores = $criteria->mapWithKeys(fn ($c) => [$c->id => 5])->all();
        $this->assertEquals(100.0, ScoreCalculator::weighted($criteria, $scores));

        $heaviest = $criteria->sortByDesc('weight')->first();
        $scores[$heaviest->id] = 1;
        $expected = round((100 - (float) $heaviest->weight * 0.8), 2);
        $this->assertEquals($expected, ScoreCalculator::weighted($criteria, $scores));
    }

    public function test_conflict_declaration_revokes_access_and_is_recorded(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($proposal, $reviewer);

        $this->actingAs($reviewer)->post(route('reviewer.assignments.conflict', $assignment), ['reason' => 'Rekan kerja ketua tim.'])->assertRedirect(route('reviewer.assignments.index'));

        $this->assertSame('conflict_declared', $assignment->fresh()->status);
        $this->assertDatabaseHas('conflict_declarations', ['reviewer_id' => $reviewer->id, 'proposal_id' => $proposal->id]);
        $this->actingAs($reviewer)->get(route('reviewer.assignments.show', $assignment))->assertForbidden();

        // Admin tidak dapat menugaskan ulang reviewer yang sama.
        $phase = ReviewPhase::query()->where('code', 'open_call')->first();
        $this->actingAs($this->admin())->post(route('admin.assignments.store'), [
            'proposal_id' => $proposal->id, 'reviewer_id' => $reviewer->id, 'review_phase_id' => $phase->id, 'confirm_warnings' => '1',
        ])->assertSessionHas('error');
    }

    public function test_reviewer_cannot_change_status_or_publication(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $this->assign($proposal, $reviewer);

        $this->actingAs($reviewer)->post(route('admin.proposals.status', $proposal), ['to' => 'approved'])->assertForbidden();
        $this->actingAs($reviewer)->post(route('admin.products.publish', $proposal))->assertForbidden();
        $this->assertFalse($proposal->fresh()->is_public);
        $this->assertSame(ProposalStatus::UnderReview, $proposal->fresh()->status);
    }

    public function test_admin_reopen_preserves_old_values_in_audit(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $admin = $this->admin();
        $assignment = $this->assign($proposal, $reviewer, $admin);
        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(5) + ['action' => 'submit']);
        $review = Review::query()->firstOrFail();

        $this->actingAs($admin)->post(route('admin.reviews.reopen', $review), ['reason' => 'Koreksi kriteria.'])->assertSessionHas('success');
        $this->assertSame('draft', $review->fresh()->status);

        $log = AuditLog::query()->where('action', 'review.reopened')->firstOrFail();
        $this->assertEquals(100, $log->old_values['weighted_score']);
        $this->assertNotEmpty($log->old_values['scores']);

        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(3) + ['action' => 'submit'])->assertRedirect();
        $this->assertEquals(60.0, (float) $review->fresh()->weighted_score);
    }

    public function test_reviewer_notes_and_scores_hidden_from_participants_by_default(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer(['name' => 'Juri Rahasia']);
        $assignment = $this->assign($proposal, $reviewer);
        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), $this->scorePayload(4, ['overall_comment' => 'KOMENTAR-JURI', 'private_notes' => 'CATATAN-PRIBADI']) + ['action' => 'submit']);

        $this->flushSession();
        $this->actingAs($proposal->team->leader)->get(route('portal.proposals.status', $proposal))
            ->assertOk()
            ->assertDontSee('KOMENTAR-JURI')->assertDontSee('CATATAN-PRIBADI')->assertDontSee('Juri Rahasia')->assertDontSee('80,00');
    }

    public function test_criteria_are_configurable_per_phase(): void
    {
        $admin = $this->admin();
        $phase = ReviewPhase::query()->where('code', 'demo_day')->first();

        $this->actingAs($admin)->post(route('admin.criteria.store'), [
            'review_phase_id' => $phase->id, 'name' => 'Kriteria baru', 'max_score' => 10, 'weight' => 12.5, 'requires_comment' => '1', 'is_active' => '1',
        ])->assertRedirect(route('admin.review-phases.index'));

        $this->assertDatabaseHas('review_criteria', ['name' => 'Kriteria baru', 'max_score' => 10, 'review_phase_id' => $phase->id]);
        $this->assertTrue(ReviewCriterion::query()->where('name', 'Kriteria baru')->first()->requires_comment);
    }
}
