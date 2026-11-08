<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\ReviewPhase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class DecisionAndPublicationTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    private function approve(Proposal $proposal, array $extra = []): Proposal
    {
        $this->actingAs($this->admin())->post(route('admin.proposals.status', $proposal), array_merge([
            'to' => 'approved',
            'public_title' => 'Judul Publik Aman',
            'public_short_description' => 'Deskripsi publik yang ditinjau.',
        ], $extra))->assertRedirect(route('admin.proposals.show', $proposal));

        return $proposal->fresh();
    }

    public function test_submitted_proposal_is_not_public(): void
    {
        $proposal = $this->submittedProposal();

        $this->get(route('products.index'))->assertOk()->assertDontSee($proposal->product_name);
        $this->assertFalse($proposal->is_public);
        $this->assertNull($proposal->public_slug);
    }

    public function test_admin_decisions_follow_allowed_transitions(): void
    {
        $proposal = $this->submittedProposal();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'revision_requested'])->assertSessionHasErrors('note');
        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'shortlisted'])->assertRedirect();
        $this->assertSame(ProposalStatus::Shortlisted, $proposal->fresh()->status);

        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'accepted'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'demo_day'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'selected_prototype'])->assertRedirect();
        $this->assertSame(ProposalStatus::SelectedPrototype, $proposal->fresh()->status);
        $this->assertSame('prototype_funding', $proposal->fresh()->programStage->code);

        // Transisi yang tidak diizinkan (mis. kembali ke draf) ditolak.
        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'draft'])->assertSessionHasErrors('to');
        $this->assertSame(5, $proposal->statusHistories()->count());
    }

    public function test_rejection_records_reason_and_is_audited(): void
    {
        $proposal = $this->submittedProposal();
        $this->actingAs($this->admin())->post(route('admin.proposals.status', $proposal), ['to' => 'rejected', 'note' => 'Tidak sesuai tantangan.'])->assertRedirect();

        $proposal->refresh();
        $this->assertSame(ProposalStatus::Rejected, $proposal->status);
        $this->assertSame('Tidak sesuai tantangan.', $proposal->rejection_reason);
        $this->assertTrue(AuditLog::query()->where('action', 'proposal.status_changed')->where('auditable_id', $proposal->id)->exists());
    }

    public function test_approval_automatically_publishes_safe_summary_without_pdf(): void
    {
        $proposal = $this->approve($this->submittedProposal());

        $this->assertSame(ProposalStatus::Approved, $proposal->status);
        $this->assertTrue($proposal->is_public);
        $this->assertNotNull($proposal->published_at);
        $this->assertNotNull($proposal->public_slug);
        $this->assertFalse($proposal->public_pdf_enabled);
        $this->assertNotNull($proposal->public_image_path);
        $this->assertNotSame($proposal->currentFile('cover_image')->path, $proposal->public_image_path, 'Gambar publik harus salinan terpisah.');

        $this->get(route('products.index'))->assertOk()->assertSee('Judul Publik Aman');
        $page = $this->get(route('products.show', $proposal->public_slug))->assertOk()->assertSee('Deskripsi publik yang ditinjau.');
        $page->assertDontSee('RAHASIA-TEKNIS')->assertDontSee('Unduh PDF')->assertDontSee('narahubung@contoh.test');

        $this->get(route('media.product-pdf', $proposal->public_slug))->assertNotFound();
        $this->get(route('media.product-image', $proposal->public_slug))->assertOk();
        $this->assertTrue(AuditLog::query()->where('action', 'product.published')->where('auditable_id', $proposal->id)->exists());
    }

    public function test_admin_can_enable_public_pdf_separately(): void
    {
        $proposal = $this->approve($this->submittedProposal());
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.products.pdf', $proposal), ['enable' => '1'])->assertSessionHas('success');
        auth()->logout();
        $this->get(route('products.show', $proposal->public_slug))->assertSee('Unduh PDF');
        $this->get(route('media.product-pdf', $proposal->public_slug))->assertOk()->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($admin)->post(route('admin.products.pdf', $proposal), ['enable' => '0']);
        auth()->logout();
        $this->get(route('media.product-pdf', $proposal->public_slug))->assertNotFound();
        $this->assertSame(2, AuditLog::query()->where('action', 'product.public_pdf_changed')->count());
    }

    public function test_unpublish_hides_product_without_deleting_history(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($proposal, $reviewer);
        $proposal = $this->approve($proposal);
        $admin = $this->admin();
        $slug = $proposal->public_slug;

        $this->actingAs($admin)->post(route('admin.products.unpublish', $proposal), ['reason' => 'Permintaan kerahasiaan mitra.'])->assertSessionHas('success');
        auth()->logout();

        $this->get(route('products.show', $slug))->assertNotFound();
        $this->get(route('media.product-image', $slug))->assertNotFound();
        $this->get(route('products.index'))->assertDontSee('Judul Publik Aman');

        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('reviewer_assignments', ['id' => $assignment->id]);
        $this->assertTrue(AuditLog::query()->where('action', 'product.unpublished')->exists());
        $this->assertGreaterThan(0, $proposal->statusHistories()->count());

        $this->actingAs($admin)->post(route('admin.products.publish', $proposal))->assertSessionHas('success');
        auth()->logout();
        $this->get(route('products.show', $slug))->assertOk();
    }

    public function test_hidden_product_stays_hidden_after_reapproval(): void
    {
        $proposal = $this->approve($this->submittedProposal());
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.products.unpublish', $proposal), ['reason' => 'Koreksi data.']);
        $this->actingAs($admin)->post(route('admin.proposals.status', $proposal), ['to' => 'pilot']);
        $this->approve($proposal->fresh());

        $this->assertFalse($proposal->fresh()->is_public);
    }

    public function test_rejecting_approved_product_unpublishes_it(): void
    {
        $proposal = $this->approve($this->submittedProposal());
        $this->actingAs($this->admin())->post(route('admin.proposals.status', $proposal), ['to' => 'rejected', 'note' => 'Persetujuan keliru.']);

        auth()->logout();
        $this->get(route('products.show', $proposal->public_slug))->assertNotFound();
        $this->assertFalse($proposal->fresh()->is_public);
    }

    public function test_public_summary_is_edited_independently_from_private_fields(): void
    {
        $proposal = $this->approve($this->submittedProposal());
        $this->actingAs($this->admin())->put(route('admin.products.update', $proposal), [
            'public_title' => 'Judul Editorial',
            'public_short_description' => 'Ringkas.',
            'public_impact' => 'Dampak publik.',
            'public_show_team' => '0',
        ])->assertSessionHas('success');

        $proposal->refresh();
        $this->assertSame('Judul Editorial', $proposal->public_title);
        $this->assertNotSame('Judul Editorial', $proposal->product_name);
        $this->get(route('products.show', $proposal->public_slug))->assertSee('Dampak publik.')->assertDontSee($proposal->team->name);
    }

    public function test_review_records_survive_status_changes(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $assignment = $this->assign($proposal, $reviewer);
        $criteria = ReviewPhase::query()->where('code', 'open_call')->first()->criteria;
        $this->actingAs($reviewer)->put(route('reviewer.assignments.review', $assignment), [
            'action' => 'submit', 'recommendation' => 'proceed', 'scores' => $criteria->mapWithKeys(fn ($c) => [$c->id => 4])->all(),
        ]);

        $this->approve($proposal);
        $this->actingAs($this->admin())->post(route('admin.products.unpublish', $proposal), ['reason' => 'Uji.']);

        $this->assertSame(1, Review::query()->where('proposal_id', $proposal->id)->where('status', 'submitted')->count());
    }
}
