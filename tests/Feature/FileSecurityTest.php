<?php

namespace Tests\Feature;

use App\Enums\ProposalStatus;
use App\Models\AuditLog;
use App\Models\ProposalFile;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\BuildsDeepnest;
use Tests\TestCase;

class FileSecurityTest extends TestCase
{
    use BuildsDeepnest, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bootDeepnest();
    }

    public function test_valid_image_and_pdf_are_stored_privately_with_random_names(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader), null, ProposalStatus::Draft, false);

        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'cover_image', 'file' => UploadedFile::fake()->image('../../etc/passwd.png', 800, 600)])->assertSessionHas('success');
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'proposal_pdf', 'file' => $this->pdfUpload('Proposal Tim.pdf')])->assertSessionHas('success');

        $files = ProposalFile::query()->where('proposal_id', $proposal->id)->get();
        $this->assertCount(2, $files);
        foreach ($files as $file) {
            $this->assertStringStartsWith('proposals/'.$proposal->id.'/', $file->path);
            $this->assertDoesNotMatchRegularExpression('/passwd|Proposal Tim/', $file->path);
            Storage::disk('local')->assertExists($file->path);
            $this->assertFalse(str_contains($file->path, 'public/'));
        }
        $this->assertSame('application/pdf', $files->firstWhere('type', 'proposal_pdf')->mime_type);
    }

    public function test_executable_and_disguised_uploads_are_rejected(): void
    {
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));

        $php = UploadedFile::fake()->createWithContent('shell.php', '<?php system($_GET["c"]); ?>');
        $disguised = UploadedFile::fake()->createWithContent('proposal.pdf', '<?php echo "bukan pdf"; ?>');
        $exe = UploadedFile::fake()->createWithContent('alat.exe', "MZ\x90\x00binary");
        $svg = UploadedFile::fake()->createWithContent('gambar.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'proposal_pdf', 'file' => $php])->assertSessionHasErrors('file');
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'proposal_pdf', 'file' => $disguised])->assertSessionHasErrors('file');
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'supporting_evidence', 'file' => $exe])->assertSessionHasErrors('file');
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'cover_image', 'file' => $svg])->assertSessionHasErrors('file');
        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'internal_legal', 'file' => $this->pdfUpload()])->assertSessionHasErrors('type');
    }

    public function test_oversized_upload_is_rejected(): void
    {
        Settings::set(['max_pdf_size_mb' => 1]);
        $leader = $this->participant();
        $proposal = $this->proposalFor($this->teamFor($leader));
        $big = UploadedFile::fake()->createWithContent('besar.pdf', "%PDF-1.4\n".str_repeat('0', 1100 * 1024));

        $this->actingAs($leader)->post(route('portal.proposals.files.store', $proposal), ['type' => 'proposal_pdf', 'file' => $big])->assertSessionHasErrors('file');
    }

    public function test_private_file_access_rules_per_role(): void
    {
        $proposal = $this->submittedProposal();
        $pdf = $proposal->currentFile('proposal_pdf');
        $url = route('files.show', $pdf->uuid);

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));

        $this->actingAs($proposal->team->leader)->get($url)->assertOk();

        $outsider = $this->participant();
        $this->actingAs($outsider)->get($url)->assertForbidden();

        $reviewer = $this->reviewer();
        $this->actingAs($reviewer)->get($url)->assertForbidden();

        $this->assign($proposal, $reviewer);
        $this->actingAs($reviewer)->get($url)->assertOk();

        $this->actingAs($this->admin())->get($url)->assertOk();

        $this->assertTrue(AuditLog::query()->where('action', 'file.accessed')->exists());
    }

    public function test_file_response_headers_do_not_expose_storage_path(): void
    {
        $proposal = $this->submittedProposal();
        $pdf = $proposal->currentFile('proposal_pdf');

        $response = $this->actingAs($proposal->team->leader)->get(route('files.show', $pdf->uuid));
        $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertStringContainsString('private', (string) $response->headers->get('Cache-Control'));

        $page = $this->actingAs($proposal->team->leader)->get(route('portal.proposals.files', $proposal))->getContent();
        $this->assertStringNotContainsString($pdf->path, $page);
    }

    public function test_internal_admin_files_are_hidden_from_participants_and_reviewers(): void
    {
        $proposal = $this->submittedProposal();
        $reviewer = $this->reviewer();
        $admin = $this->admin();
        $this->assign($proposal, $reviewer, $admin);

        $this->actingAs($admin)->post(route('admin.proposals.internal-files', $proposal), ['file' => $this->pdfUpload('visitasi.pdf')])->assertSessionHas('success');
        $internal = ProposalFile::query()->where('type', 'internal_legal')->firstOrFail();

        $this->actingAs($proposal->team->leader)->get(route('files.show', $internal->uuid))->assertForbidden();
        $this->actingAs($reviewer)->get(route('files.show', $internal->uuid))->assertForbidden();
        $this->actingAs($admin)->get(route('files.show', $internal->uuid))->assertOk();
    }

    public function test_unknown_file_identifier_returns_404(): void
    {
        $this->actingAs($this->admin())->get(route('files.show', '00000000-0000-0000-0000-000000000000'))->assertNotFound();
    }
}
