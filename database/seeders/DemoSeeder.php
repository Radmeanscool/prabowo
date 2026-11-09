<?php

namespace Database\Seeders;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Person;
use App\Models\Proposal;
use App\Models\ReviewerAssignment;
use App\Models\ReviewPhase;
use App\Models\ReviewScore;
use App\Models\Team;
use App\Models\Theme;
use App\Models\User;
use App\Services\ProposalWorkflow;
use App\Services\ScoreCalculator;
use App\Services\SecureFileStore;
use App\Support\Settings;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * DATA CONTOH UNTUK UJI LOKAL SAJA. Jangan dijalankan di server produksi.
 *
 *   php artisan db:seed --class=DemoSeeder
 *
 * Semua akun demo memakai kata sandi: Deepnest2026
 */
class DemoSeeder extends Seeder
{
    private const PASSWORD = 'Deepnest2026';

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('DemoSeeder tidak boleh dijalankan di lingkungan produksi.');
        }

        $this->call(DatabaseSeeder::class);

        Settings::set(['open_call_mode' => 'open', 'email_notifications_enabled' => false]);

        // Untuk demo: terbitkan draf tantangan dan tampilkan mitra.
        Challenge::query()->update(['status' => 'published', 'published_at' => now()]);
        ChallengeOwner::query()->where('participation_status', '!=', 'pending_confirmation')->update(['is_public' => true]);
        Announcement::query()->update(['is_published' => true, 'published_at' => now()->subDays(2)]);

        $admin = $this->user('admin@deepnest.test', 'Admin Demo', UserRole::Admin, 'Universitas Hasanuddin');
        $reviewers = [
            $this->user('reviewer1@deepnest.test', 'Reviewer Demo Satu', UserRole::Reviewer, 'Universitas Hasanuddin', 'Teknologi pangan'),
            $this->user('reviewer2@deepnest.test', 'Reviewer Demo Dua', UserRole::Reviewer, 'BRIN', 'Rekayasa alat kesehatan'),
            $this->user('reviewer3@deepnest.test', 'Reviewer Demo Tiga', UserRole::Reviewer, 'Kementerian Pertanian', 'Agribisnis'),
        ];

        foreach ([['Juri Demo Satu', 'juror', 'internal', 'Universitas Hasanuddin', $reviewers[0]], ['Juri Demo Dua', 'juror', 'external', 'BRIN', $reviewers[1]], ['Mentor Demo Pangan', 'mentor', 'internal', 'Universitas Hasanuddin', null], ['Mentor Demo Kesehatan', 'mentor', 'partner', 'Rumah Sakit Pendidikan Unhas', null]] as $i => [$name, $type, $category, $affiliation, $user]) {
            Person::query()->firstOrCreate(['slug' => Str::slug($name)], [
                'name' => $name, 'type' => $type, 'category' => $category, 'affiliation' => $affiliation,
                'expertise' => $type === 'juror' ? 'Penilaian kelayakan teknis dan hilirisasi' : 'Pendampingan purwarupa',
                'is_public' => true, 'sort_order' => $i, 'user_id' => $user?->id,
            ]);
        }

        $themes = Theme::query()->pluck('id', 'code');
        $phase = ReviewPhase::query()->where('code', 'open_call')->first();
        $workflow = new ProposalWorkflow;

        $scenarios = [
            ['team' => 'Tim Demo Rumput Laut', 'leader' => 'peserta1@deepnest.test', 'member' => 'peserta2@deepnest.test', 'institution' => 'Universitas Hasanuddin',
                'product' => 'SeaTrace: ketertelusuran mutu rumput laut', 'theme' => Theme::FOOD_SECURITY, 'challenge' => 'ketertelusuran-dan-mutu-rumput-laut-pascapanen',
                'summary' => 'Sensor kadar air portabel dan aplikasi ketertelusuran untuk menjaga mutu rumput laut pascapanen.', 'final' => 'approved', 'color' => [61, 110, 70]],
            ['team' => 'Tim Demo Bunda Sehat', 'leader' => 'peserta3@deepnest.test', 'member' => 'peserta4@deepnest.test', 'institution' => 'Politeknik Kesehatan Makassar',
                'product' => 'PantauBunda: pemantauan kehamilan berbasis kader', 'theme' => Theme::HEALTH, 'challenge' => 'pemantauan-kesehatan-ibu-hamil-dan-pencegahan-stunting',
                'summary' => 'Alat ukur dan aplikasi kader untuk memantau kehamilan berisiko dan mengingatkan jadwal pemeriksaan.', 'final' => 'approved', 'color' => [168, 64, 47]],
            ['team' => 'Tim Demo Sapi Presisi', 'leader' => 'peserta5@deepnest.test', 'member' => 'peserta6@deepnest.test', 'institution' => 'Universitas Negeri Makassar',
                'product' => 'BirahiCam: deteksi birahi sapi', 'theme' => Theme::FOOD_SECURITY, 'challenge' => 'intensifikasi-reproduksi-dan-peningkatan-populasi-sapi',
                'summary' => 'Kamera dan sensor gerak untuk mendeteksi masa birahi sapi agar inseminasi buatan tepat waktu.', 'final' => 'under_review', 'color' => [61, 110, 70]],
            ['team' => 'Tim Demo Pulau Sehat', 'leader' => 'peserta7@deepnest.test', 'member' => 'peserta8@deepnest.test', 'institution' => 'Startup Demo Makassar',
                'product' => 'Telerujuk Kepulauan', 'theme' => Theme::HYBRID, 'challenge' => 'drone-untuk-pertanian-pemantauan-laut-dan-distribusi-alat-kesehatan',
                'summary' => 'Layanan drone dan telekonsultasi untuk rujukan cepat dari pulau ke daratan.', 'final' => 'draft', 'color' => [168, 64, 47]],
        ];

        foreach ($scenarios as $index => $s) {
            if (Team::query()->where('name', $s['team'])->exists()) {
                continue;
            }

            $leader = $this->user($s['leader'], 'Ketua '.Str::after($s['team'], 'Tim Demo '), UserRole::Participant, $s['institution'], 'Teknik');
            $member = $this->user($s['member'], 'Anggota '.Str::after($s['team'], 'Tim Demo '), UserRole::Participant, $s['institution'], 'Gizi dan kesehatan masyarakat');

            $team = new Team(['name' => $s['team'], 'institution' => $s['institution'], 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'contact_email' => $leader->email, 'contact_phone' => '0811000000'.$index, 'profile' => 'Tim lintas disiplin (data demo).']);
            $team->slug = Team::uniqueSlug($s['team']);
            $team->leader_id = $leader->id;
            $team->join_code = Team::generateJoinCode();
            $team->save();
            foreach ([[$leader, 'leader'], [$member, 'member']] as [$user, $role]) {
                $team->members()->create(['user_id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'institution' => $user->institution, 'discipline' => $user->discipline, 'role' => $role, 'status' => 'active', 'joined_at' => now()]);
            }

            $challenge = Challenge::query()->where('slug', $s['challenge'])->first();
            $proposal = new Proposal([
                'product_name' => $s['product'], 'short_summary' => $s['summary'], 'tkt_level' => 4, 'prototype_status' => 'lab_prototype',
                'contact_name' => $leader->name, 'contact_email' => $leader->email, 'contact_phone' => '0811000000'.$index,
                'inventors' => 'Data demo: ketua tim sebagai peneliti utama, anggota sebagai peneliti pendamping.',
                'technical_description' => 'Data demo. Purwarupa terdiri atas sensor, mikrokontroler, dan aplikasi seluler yang menyimpan data secara luring lalu menyinkronkan ke server saat tersedia sinyal.',
                'technical_validation' => 'Uji laboratorium awal pada 30 sampel menunjukkan hasil konsisten.',
                'technical_limitations' => 'Kalibrasi lapangan belum dilakukan.',
                'target_users' => 'Pengguna di lingkungan mitra pemilik tantangan.',
                'market_description' => 'Data demo: pasar awal adalah mitra pemilik tantangan dan kelompok pengguna binaannya.',
                'competitors' => 'Pencatatan manual dan alat impor yang mahal.',
                'value_proposition' => 'Lebih murah, dapat bekerja tanpa internet, dan mudah dioperasikan.',
                'market_validation' => 'Survei 50 calon pengguna dan FGD bersama mitra.',
                'impact_description' => 'Target: waktu pemeriksaan turun 50% dan data terekam lengkap.',
                'adoption_plan' => 'Pilot 3 bulan di lokasi mitra, dilanjutkan pelatihan pengguna.',
                'problem_statement' => $challenge?->summary ?? 'Data demo.',
                'challenge_importance' => 'Masalah berdampak langsung pada pengguna mitra.',
                'solution_description' => $s['summary'],
                'minimum_functions' => 'Pengukuran, pencatatan, dan pelaporan otomatis.',
                'success_indicators' => 'Akurasi ≥ 90% dibanding metode rujukan.',
                'data_facility_needs' => 'Akses lokasi uji dan data pembanding dari mitra.',
                'pilot_location' => 'Lokasi mitra pemilik tantangan.',
                'ip_status' => 'identified', 'ip_notes' => 'Akan diajukan paten sederhana.',
                'regulatory_status' => 'identified', 'regulatory_notes' => 'Identifikasi standar dan izin yang relevan.',
                'third_party_dependencies' => 'Komponen elektronik komersial.',
                'safety_risks' => 'Risiko kelistrikan rendah.',
                'data_risk_status' => $s['theme'] === Theme::HEALTH ? 'health' : 'none', 'data_notes' => 'Data disimpan terenkripsi.',
                'ethics_status' => $s['theme'] === Theme::HEALTH ? 'required_not_submitted' : 'not_required', 'ethics_notes' => 'Data demo.',
                'environmental_risks' => 'Tidak signifikan.',
                'mitigation_plan' => 'Uji keselamatan dan protokol perlindungan data sebelum pilot.',
            ]);
            $proposal->forceFill([
                'team_id' => $team->id, 'theme_id' => $themes[$s['theme']], 'challenge_id' => $challenge?->id, 'created_by' => $leader->id,
                'slug' => Proposal::uniqueSlug($s['product']), 'status' => ProposalStatus::Draft,
            ])->save();
            $workflow->recordHistory($proposal, null, ProposalStatus::Draft, $leader, 'Draf proposal dibuat.');
            $proposal->researchHistories()->create(['year' => '2024–2025', 'title' => 'Penelitian pendahuluan (data demo)', 'researchers' => $leader->name, 'funding_amount' => 25000000, 'funding_source' => 'Hibah internal (demo)']);

            SecureFileStore::storeProposalFile($proposal, $this->demoImage($s['color']), 'cover_image', $leader);
            SecureFileStore::storeProposalFile($proposal, $this->demoPdf($s['product']), 'proposal_pdf', $leader);

            if ($s['final'] === 'draft') {
                continue;
            }

            $workflow->submit($proposal->fresh(), $leader);
            $proposal->refresh();

            $assignment = ReviewerAssignment::query()->create([
                'proposal_id' => $proposal->id, 'reviewer_id' => $reviewers[$index % 3]->id, 'review_phase_id' => $phase->id,
                'assigned_by' => $admin->id, 'status' => 'assigned', 'due_at' => now()->addDays(10),
            ]);
            $proposal->forceFill(['status' => ProposalStatus::UnderReview, 'review_phase_id' => $phase->id])->save();
            $workflow->recordHistory($proposal, ProposalStatus::Submitted, ProposalStatus::UnderReview, $admin, 'Reviewer ditugaskan.');

            if ($s['final'] === 'approved') {
                $criteria = $phase->criteriaForTheme($proposal->theme_id);
                $review = $assignment->review()->create(['proposal_id' => $proposal->id, 'reviewer_id' => $assignment->reviewer_id, 'review_phase_id' => $phase->id, 'status' => 'submitted', 'recommendation' => 'proceed', 'overall_comment' => 'Relevan dan layak dilanjutkan (data demo).', 'submitted_at' => now()]);
                $scores = [];
                foreach ($criteria as $c) {
                    $scores[$c->id] = 4 + ($c->id % 2);
                    ReviewScore::query()->create(['review_id' => $review->id, 'review_criterion_id' => $c->id, 'score' => $scores[$c->id]]);
                }
                $review->forceFill(['weighted_score' => ScoreCalculator::weighted($criteria, $scores)])->save();
                $assignment->forceFill(['status' => 'submitted'])->save();

                $workflow->transition($proposal->fresh(), ProposalStatus::Approved, $admin, 'Disetujui (data demo).', [
                    'public_title' => $s['product'],
                    'public_short_description' => $s['summary'],
                    'public_show_team' => true,
                ]);
                $proposal->refresh()->forceFill([
                    'public_problem' => $challenge?->summary,
                    'public_solution' => $s['summary'],
                    'public_impact' => 'Target pilot: waktu pemeriksaan turun 50% (data demo).',
                ])->save();
            }
        }

        $this->command?->info('Data demo siap. Masuk sebagai admin@deepnest.test / '.self::PASSWORD);
    }

    private function user(string $email, string $name, UserRole $role, string $institution, ?string $discipline = null): User
    {
        $user = User::query()->where('email', $email)->first();
        if ($user) {
            return $user;
        }

        $user = new User([
            'name' => $name, 'email' => $email, 'password' => self::PASSWORD, 'institution' => $institution,
            'discipline' => $discipline, 'city' => 'Makassar', 'province' => 'Sulawesi Selatan', 'phone' => '081100000000',
            'participant_type' => $role === UserRole::Participant ? 'researcher' : null, 'consented_at' => now(),
        ]);
        $user->forceFill(['role' => $role, 'is_active' => true, 'email_verified_at' => now()])->save();

        return $user;
    }

    private function demoImage(array $rgb): UploadedFile
    {
        // Gambar contoh sederhana: bidang warna tema dengan tanda "sarang" DEEPNEST.
        $w = 1200;
        $h = 900;
        $img = imagecreatetruecolor($w, $h);
        imagefilledrectangle($img, 0, 0, $w, $h, imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]));
        $paper = imagecolorallocate($img, 246, 242, 234);
        imagesetthickness($img, 10);
        foreach ([520, 340, 160] as $diameter) {
            imagearc($img, 600, 470, $diameter, $diameter, 0, 180, $paper);
        }
        imagefilledellipse($img, 600, 330, 56, 56, imagecolorallocate($img, 210, 115, 76));

        $path = tempnam(sys_get_temp_dir(), 'dn').'.png';
        imagepng($img, $path);
        imagedestroy($img);

        return new UploadedFile($path, 'gambar-produk.png', 'image/png', null, true);
    }

    private function demoPdf(string $title): UploadedFile
    {
        $text = 'DEEPNEST demo: '.Str::ascii($title);
        $stream = 'BT /F1 18 Tf 72 720 Td ('.addcslashes($text, '()\\').') Tj ET';
        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            '<< /Length '.strlen($stream)." >>\nstream\n".$stream."\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];
        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $i => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($i + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$xref."\n%%EOF\n";

        $path = tempnam(sys_get_temp_dir(), 'dn').'.pdf';
        file_put_contents($path, $pdf);

        return new UploadedFile($path, 'proposal-resmi.pdf', 'application/pdf', null, true);
    }
}
