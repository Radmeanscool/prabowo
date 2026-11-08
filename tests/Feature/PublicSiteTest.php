<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\ChallengeOwner;
use App\Models\Document;
use App\Models\Person;
use Database\Seeders\AgendaSeeder;
use Database\Seeders\ChallengeOwnerSeeder;
use Database\Seeders\ChallengeSeeder;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class PublicSiteTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
        $this->seed([ChallengeOwnerSeeder::class, ChallengeSeeder::class, AgendaSeeder::class, PageSeeder::class, FaqSeeder::class]);
    }

    public function test_visitor_can_understand_deepnest(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('DEEPNEST')
            ->assertSee('Dari Tantangan Nyata Menuju Inovasi Berdampak')
            ->assertSee('Ketahanan Pangan')->assertSee('Kesehatan')->assertSee('Hybrid')
            ->assertSee('Challenge Discovery')
            ->assertSee('Jelajahi Tantangan')->assertSee('Daftar Sekarang');

        $this->get(route('about'))->assertOk()->assertSee('Target luaran');
        $this->get(route('stages'))->assertOk()->assertSee('Pilot Project')->assertSee('Adoption & Impact');
        $this->get(route('timeline'))->assertOk()->assertSee('FGD Challenge Discovery');
        $this->get(route('agenda'))->assertOk()->assertSee('Unhas Hotel &amp; Convention', false);
        $this->get(route('rules'))->assertOk();
        $this->get(route('faq'))->assertOk()->assertSee('Apa itu DEEPNEST?');
        $this->get(route('people.index'))->assertOk()->assertSee('Mentor (rencana)')->assertSee('Juri (rencana)');
    }

    public function test_draft_challenges_are_not_public_until_published(): void
    {
        $this->get(route('challenges.index'))->assertOk()->assertSee('Tantangan final segera diumumkan')->assertDontSee('Ketertelusuran dan mutu rumput laut');
        $this->get(route('challenges.show', 'ketertelusuran-dan-mutu-rumput-laut-pascapanen'))->assertNotFound();

        $published = $this->publishedChallenge();
        $this->get(route('challenges.index'))->assertSee($published->title);
        $this->get(route('challenges.show', $published->slug))->assertOk()->assertDontSee('DATA RAHASIA MITRA');

        $published->update(['data_public' => true]);
        $this->get(route('challenges.show', $published->slug))->assertSee('DATA RAHASIA MITRA');
    }

    public function test_challenge_owners_hidden_until_permission(): void
    {
        $owner = ChallengeOwner::query()->where('slug', 'kospermindo')->firstOrFail();

        $this->get(route('owners.index'))->assertOk()->assertDontSee('KOSPERMINDO');
        $this->get(route('owners.show', 'kospermindo'))->assertNotFound();

        $owner->update(['is_public' => true]);
        $this->get(route('owners.index'))->assertSee('KOSPERMINDO');
        $this->get(route('owners.show', 'kospermindo'))->assertOk()->assertDontSee((string) $owner->contact_email ?: 'x-tidak-ada');
    }

    public function test_people_and_documents_respect_visibility(): void
    {
        Person::query()->create(['name' => 'Mentor Tersembunyi', 'slug' => 'mentor-tersembunyi', 'type' => 'mentor', 'is_public' => false]);
        Person::query()->create(['name' => 'Mentor Publik', 'slug' => 'mentor-publik', 'type' => 'mentor', 'is_public' => true]);
        Document::query()->create(['title' => 'Panduan Publik', 'slug' => 'panduan-publik', 'visibility' => 'public', 'is_published' => true, 'external_url' => 'https://contoh.test/a.pdf']);
        Document::query()->create(['title' => 'Hasil Visitasi Legal', 'slug' => 'hasil-visitasi', 'visibility' => 'admins', 'is_published' => true, 'external_url' => 'https://contoh.test/b.pdf']);

        $this->get(route('people.index'))->assertSee('Mentor Publik')->assertDontSee('Mentor Tersembunyi');
        $this->get(route('resources.index'))->assertSee('Panduan Publik')->assertDontSee('Hasil Visitasi Legal');
        $this->get(route('resources.download', 'hasil-visitasi'))->assertNotFound();
    }

    public function test_announcements_respect_publication_and_audience(): void
    {
        Announcement::query()->create(['title' => 'Info Publik', 'slug' => 'info-publik', 'body' => 'Isi', 'audience' => 'public', 'is_published' => true, 'published_at' => now()]);
        Announcement::query()->create(['title' => 'Info Peserta', 'slug' => 'info-peserta', 'body' => 'Isi', 'audience' => 'participants', 'is_published' => true, 'published_at' => now()]);
        Announcement::query()->create(['title' => 'Draf Rahasia', 'slug' => 'draf', 'body' => 'Isi', 'audience' => 'public', 'is_published' => false]);

        $this->get(route('announcements.index'))->assertSee('Info Publik')->assertDontSee('Info Peserta')->assertDontSee('Draf Rahasia');
        $this->get(route('announcements.show', 'draf'))->assertNotFound();
        $this->get(route('announcements.show', 'info-peserta'))->assertNotFound();

        $this->actingAs($this->participant())->get(route('announcements.show', 'info-peserta'))->assertOk();
        $this->actingAs($this->participant())->get(route('portal.announcements'))->assertSee('Info Peserta');
    }

    public function test_markdown_content_is_sanitized(): void
    {
        Announcement::query()->create(['title' => 'Uji XSS', 'slug' => 'uji-xss', 'body' => "Halo <script>alert('x')</script> [klik](javascript:alert(1))", 'audience' => 'public', 'is_published' => true, 'published_at' => now()]);

        $html = $this->get(route('announcements.show', 'uji-xss'))->assertOk()->getContent();
        $this->assertStringNotContainsString("<script>alert('x')</script>", $html);
        $this->assertStringNotContainsString('href="javascript:', $html);
    }

    public function test_security_headers_are_present(): void
    {
        $this->get('/')->assertHeader('X-Frame-Options', 'SAMEORIGIN')->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Security-Policy');
    }

    public function test_only_published_products_are_listed(): void
    {
        $draftTeam = $this->teamFor($this->participant());
        $draft = $this->proposalFor($draftTeam);
        $submitted = $this->submittedProposal();

        $this->get(route('products.index'))->assertOk()->assertDontSee($draft->product_name)->assertDontSee($submitted->product_name);
        $this->get(route('products.show', $draft->slug))->assertNotFound();
    }
}
