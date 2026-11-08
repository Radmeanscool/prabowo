<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\ExportController;
use App\Models\AgendaItem;
use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Page;
use App\Models\Person;
use App\Models\ProgramStage;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\ReviewCriterion;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Models\Team;
use App\Models\Theme;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Memastikan setiap halaman setiap peran dapat dirender dengan data contoh (tanpa galat 500).
 */
class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->seed(DemoSeeder::class);
    }

    private function assertPagesRender(?User $user, array $urls): void
    {
        foreach ($urls as $url) {
            $response = $user ? $this->actingAs($user)->get($url) : $this->get($url);
            $status = $response->baseResponse->getStatusCode();
            $this->assertContains($status, [200, 302], "Halaman {$url} mengembalikan {$status}");
        }
    }

    public function test_public_pages_render(): void
    {
        $product = Proposal::query()->publicProducts()->firstOrFail();
        $challenge = $product->challenge;
        $owner = $challenge->owner;

        $this->assertPagesRender(null, [
            '/', '/tentang', '/ketentuan', '/privasi', '/syarat', '/faq', '/tantangan', '/tantangan?tema=health',
            '/tantangan/'.$challenge->slug, '/mitra', '/mitra/'.$owner->slug, '/tahapan', '/linimasa', '/agenda', '/mentor-juri',
            '/produk', '/produk?tema=food_security&urut=terlama', '/produk/'.$product->public_slug, '/pengumuman',
            '/pengumuman/fgd-challenge-discovery-deepnest', '/sumber-daya', '/daftar', '/masuk', '/lupa-sandi',
            route('media.product-image', $product->public_slug),
        ]);
    }

    public function test_participant_pages_render(): void
    {
        $leader = User::query()->where('email', 'peserta1@deepnest.test')->firstOrFail();
        $proposal = Proposal::query()->where('team_id', $leader->activeTeam()->id)->firstOrFail();
        $draftOwner = User::query()->where('email', 'peserta7@deepnest.test')->firstOrFail();
        $draft = Proposal::query()->where('team_id', $draftOwner->activeTeam()->id)->firstOrFail();

        $this->assertPagesRender($leader, [
            route('portal.dashboard'), route('portal.profile'), route('portal.team'), route('portal.proposals.index'),
            route('portal.proposals.show', $proposal), route('portal.proposals.status', $proposal), route('portal.proposals.files', $proposal),
            route('portal.announcements'), route('portal.help'), route('account.password'),
            route('portal.shortcut.challenge'), route('portal.shortcut.files'), route('portal.proposal.status'),
        ]);
        $this->assertPagesRender($draftOwner, [
            route('portal.proposals.edit', $draft), route('portal.proposals.challenge', $draft), route('portal.proposals.confirm', $draft),
            route('portal.proposals.files', $draft),
        ]);

        $newcomer = User::query()->create(['name' => 'Baru', 'email' => 'baru@deepnest.test', 'password' => 'Rahasia123']);
        $this->assertPagesRender($newcomer, [route('portal.dashboard'), route('portal.team'), route('portal.proposals.create'), route('portal.proposals.index')]);
    }

    public function test_reviewer_pages_render(): void
    {
        $assignment = ReviewerAssignment::query()->with('reviewer')->firstOrFail();
        $reviewer = $assignment->reviewer;

        $this->assertPagesRender($reviewer, [
            route('reviewer.dashboard'), route('reviewer.assignments.index'), route('reviewer.assignments.show', $assignment),
            route('reviewer.reviews.index', ['status' => 'draft']), route('reviewer.reviews.index', ['status' => 'submitted']),
            route('reviewer.rubric'), route('reviewer.announcements'), route('reviewer.conflicts.index'),
        ]);
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::query()->where('email', 'admin@deepnest.test')->firstOrFail();
        $proposal = Proposal::query()->publicProducts()->firstOrFail();
        $team = Team::query()->firstOrFail();
        $review = Review::query()->firstOrFail();

        $urls = [
            route('admin.dashboard'), route('admin.users.index'), route('admin.users.create'), route('admin.users.edit', $admin),
            route('admin.teams.index'), route('admin.teams.show', $team), route('admin.proposals.index'),
            route('admin.proposals.index', ['completeness' => 'missing_files', 'assignment' => 'none']),
            route('admin.proposals.show', $proposal), route('admin.assignments.index'), route('admin.assignments.index', ['state' => 'unassigned']),
            route('admin.reviews.index'), route('admin.reviews.show', $review), route('admin.products.index'), route('admin.products.edit', $proposal),
            route('admin.review-phases.index'), route('admin.review-phases.create'), route('admin.exports.index'), route('admin.audit.index'),
            route('admin.settings.edit'), route('admin.media', ['type' => 'product', 'id' => $proposal->id]),
        ];
        foreach (['themes', 'theme-questions', 'challenges', 'challenge-owners', 'people', 'stages', 'agenda', 'announcements', 'documents', 'pages', 'faqs'] as $resource) {
            $urls[] = route('admin.'.$resource.'.index');
            if (Route::has('admin.'.$resource.'.create')) {
                $urls[] = route('admin.'.$resource.'.create');
            }
        }
        $urls[] = route('admin.themes.edit', Theme::query()->first());
        $urls[] = route('admin.challenges.edit', Challenge::query()->first());
        $urls[] = route('admin.challenge-owners.edit', ChallengeOwner::query()->first());
        $urls[] = route('admin.stages.edit', ProgramStage::query()->first());
        $urls[] = route('admin.agenda.edit', AgendaItem::query()->first());
        $urls[] = route('admin.pages.edit', Page::query()->first());
        $urls[] = route('admin.people.edit', Person::query()->first());
        $urls[] = route('admin.criteria.create', ['phase' => ReviewPhase::query()->first()->id]);
        $urls[] = route('admin.criteria.edit', ReviewCriterion::query()->first());

        $this->assertPagesRender($admin, $urls);

        foreach (array_keys(ExportController::TYPES) as $type) {
            $response = $this->actingAs($admin)->get(route('admin.exports.download', ['type' => $type, 'delimiter' => 'semicolon']));
            $response->assertOk();
            $this->assertStringStartsWith("\xEF\xBB\xBF", $response->streamedContent());
        }
    }
}
