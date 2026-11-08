<?php

namespace Tests\Concerns;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Models\Challenge;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Models\Team;
use App\Models\Theme;
use App\Models\User;
use App\Services\SecureFileStore;
use App\Support\Settings;
use Database\Seeders\ProgramStageSeeder;
use Database\Seeders\ReviewPhaseSeeder;
use Database\Seeders\ThemeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait BuildsDeepnest
{
    protected function bootDeepnest(): void
    {
        Storage::fake('local');
        $this->seed([ThemeSeeder::class, ProgramStageSeeder::class, ReviewPhaseSeeder::class]);
        Settings::set(['open_call_mode' => 'open']);
    }

    protected function makeUser(UserRole $role = UserRole::Participant, array $attributes = []): User
    {
        $user = new User(array_merge([
            'name' => 'Pengguna '.Str::random(5),
            'email' => Str::lower(Str::random(10)).'@contoh.test',
            'password' => 'Rahasia123',
            'institution' => 'Institut Contoh',
            'discipline' => 'Teknik',
            'phone' => '081234567890',
            'city' => 'Makassar',
            'province' => 'Sulawesi Selatan',
        ], $attributes));
        $user->forceFill(['role' => $role, 'is_active' => true])->save();

        return $user;
    }

    protected function participant(array $attributes = []): User
    {
        return $this->makeUser(UserRole::Participant, $attributes);
    }

    protected function reviewer(array $attributes = []): User
    {
        return $this->makeUser(UserRole::Reviewer, $attributes);
    }

    protected function admin(): User
    {
        return $this->makeUser(UserRole::Admin);
    }

    protected function theme(string $code = Theme::FOOD_SECURITY): Theme
    {
        return Theme::query()->where('code', $code)->firstOrFail();
    }

    protected function publishedChallenge(string $themeCode = Theme::FOOD_SECURITY, ?array $eligible = null): Challenge
    {
        $theme = $this->theme($themeCode);
        $challenge = Challenge::query()->create([
            'theme_id' => $theme->id,
            'title' => 'Tantangan '.Str::random(6),
            'slug' => 'tantangan-'.Str::lower(Str::random(8)),
            'summary' => 'Ringkasan tantangan.',
            'problem_statement' => 'Masalah nyata.',
            'available_data' => 'DATA RAHASIA MITRA',
            'data_public' => false,
            'status' => 'published',
            'published_at' => now(),
        ]);
        $codes = $eligible ?? [$themeCode, Theme::HYBRID];
        $challenge->themes()->sync(Theme::query()->whereIn('code', $codes)->pluck('id'));

        return $challenge;
    }

    protected function teamFor(User $leader, array $members = []): Team
    {
        $team = new Team(['name' => 'Tim '.Str::random(5), 'institution' => 'Institut Contoh', 'contact_email' => $leader->email, 'contact_phone' => '0812345678']);
        $team->slug = Team::uniqueSlug($team->name);
        $team->leader_id = $leader->id;
        $team->join_code = Team::generateJoinCode();
        $team->save();
        $team->members()->create(['user_id' => $leader->id, 'name' => $leader->name, 'email' => $leader->email, 'institution' => $leader->institution, 'discipline' => $leader->discipline, 'role' => 'leader', 'status' => 'active', 'joined_at' => now()]);
        foreach ($members as $member) {
            $team->members()->create(['user_id' => $member->id, 'name' => $member->name, 'email' => $member->email, 'institution' => $member->institution, 'discipline' => $member->discipline, 'role' => 'member', 'status' => 'active', 'joined_at' => now()]);
        }

        return $team;
    }

    protected function proposalFor(Team $team, ?Challenge $challenge = null, ProposalStatus $status = ProposalStatus::Draft, bool $complete = true): Proposal
    {
        $challenge ??= $this->publishedChallenge();
        $proposal = new Proposal(['product_name' => 'Produk '.Str::random(5)]);
        $proposal->forceFill([
            'team_id' => $team->id,
            'theme_id' => $challenge->theme_id,
            'challenge_id' => $challenge->id,
            'created_by' => $team->leader_id,
            'slug' => Proposal::uniqueSlug('produk-'.Str::random(6)),
            'status' => $status,
        ])->save();

        if ($complete) {
            $this->completeProposal($proposal);
        }

        return $proposal->fresh();
    }

    protected function completeProposal(Proposal $proposal): void
    {
        $proposal->forceFill([
            'short_summary' => 'Ringkasan satu kalimat.',
            'contact_name' => 'Narahubung',
            'contact_email' => 'narahubung@contoh.test',
            'technical_description' => 'Deskripsi teknis RAHASIA-TEKNIS.',
            'target_users' => 'Petani.',
            'market_description' => 'Pasar.',
            'impact_description' => 'Dampak.',
            'problem_statement' => 'Masalah.',
            'solution_description' => 'Solusi.',
            'success_indicators' => 'Indikator.',
            'ip_status' => 'identified',
            'regulatory_status' => 'identified',
            'ethics_status' => 'not_required',
            'data_risk_status' => 'none',
            'mitigation_plan' => 'Mitigasi.',
        ])->save();

        $user = $proposal->team->leader;
        SecureFileStore::storeProposalFile($proposal, UploadedFile::fake()->image('sampul.jpg', 800, 600), 'cover_image', $user);
        SecureFileStore::storeProposalFile($proposal, $this->pdfUpload(), 'proposal_pdf', $user);
    }

    protected function pdfUpload(string $name = 'proposal.pdf'): UploadedFile
    {
        $pdf = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n2 0 obj\n<< /Type /Pages /Kids [] /Count 0 >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";

        return UploadedFile::fake()->createWithContent($name, $pdf);
    }

    protected function submittedProposal(?User $leader = null, ?Challenge $challenge = null): Proposal
    {
        $leader ??= $this->participant();
        $team = $this->teamFor($leader);
        $proposal = $this->proposalFor($team, $challenge);
        $this->actingAs($leader)->post(route('portal.proposals.submit', $proposal), ['confirm' => '1'])->assertRedirect();

        return $proposal->fresh();
    }

    protected function assign(Proposal $proposal, User $reviewer, ?User $admin = null): ReviewerAssignment
    {
        $admin ??= $this->admin();
        $phase = ReviewPhase::query()->where('code', 'open_call')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.assignments.store'), [
            'proposal_id' => $proposal->id,
            'reviewer_id' => $reviewer->id,
            'review_phase_id' => $phase->id,
            'confirm_warnings' => '1',
        ])->assertSessionHas('success');

        return ReviewerAssignment::query()->where('proposal_id', $proposal->id)->where('reviewer_id', $reviewer->id)->latest('id')->firstOrFail();
    }
}
