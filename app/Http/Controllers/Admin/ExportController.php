<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Proposal;
use App\Models\Review;
use App\Models\ReviewerAssignment;
use App\Models\ReviewScore;
use App\Models\Team;
use App\Models\User;
use App\Services\Audit;
use App\Support\Options;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor CSV (UTF-8 dengan BOM agar terbaca benar di Excel). Pilih pemisah titik koma
 * untuk Excel berbahasa Indonesia.
 */
class ExportController extends Controller
{
    public const TYPES = [
        'proposals' => 'Proposal (ringkasan, status, tema, tantangan, skor rata-rata)',
        'reviews' => 'Penilaian (skor tertimbang, rekomendasi, reviewer)',
        'scores' => 'Skor per kriteria',
        'assignments' => 'Penugasan reviewer (riwayat lengkap)',
        'teams' => 'Tim dan anggota',
        'participants' => 'Peserta terdaftar',
        'products' => 'Produk disetujui dan status publikasi',
    ];

    public function index(): View
    {
        return view('admin.exports.index', ['types' => self::TYPES]);
    }

    public function download(Request $request, string $type): StreamedResponse
    {
        abort_unless(array_key_exists($type, self::TYPES), 404);
        $delimiter = $request->query('delimiter') === 'semicolon' ? ';' : ',';

        Audit::log('export.downloaded', null, [], ['type' => $type], 'Ekspor data: '.self::TYPES[$type]);

        [$headers, $rows] = $this->{'export'.ucfirst($type)}();
        $filename = 'deepnest-'.$type.'-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($headers, $rows, $delimiter) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, $delimiter, '"', '');
            foreach ($rows() as $row) {
                fputcsv($out, array_map(fn ($v) => $this->safe($v), $row), $delimiter, '"', '');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    /** Cegah injeksi formula spreadsheet (CSV injection). */
    private function safe(mixed $value): string
    {
        $value = $value instanceof \BackedEnum ? $value->value : (string) ($value ?? '');
        if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true) && ! is_numeric($value)) {
            return "'".$value;
        }

        return $value;
    }

    private function exportProposals(): array
    {
        $headers = ['ID', 'Produk', 'Tema', 'Tantangan', 'Mitra', 'Tim', 'Institusi', 'Status', 'Versi', 'Dikirim', 'Disetujui', 'Publik', 'TKT', 'Jumlah reviewer aktif', 'Penilaian terkirim', 'Skor rata-rata'];
        $rows = function () {
            foreach (Proposal::query()->with(['theme', 'challenge.owner', 'team'])
                ->withCount(['activeAssignments', 'reviews as submitted_reviews' => fn ($q) => $q->where('status', 'submitted')])
                ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', 'submitted')], 'weighted_score')
                ->orderBy('id')->cursor() as $p) {
                yield [
                    $p->id, $p->product_name, $p->theme?->name, $p->challenge?->title, $p->challenge?->owner?->name,
                    $p->team?->name, $p->team?->institution, $p->status->label(), $p->version,
                    $p->submitted_at?->format('Y-m-d H:i'), $p->approved_at?->format('Y-m-d H:i'), $p->is_public ? 'Ya' : 'Tidak',
                    $p->tkt_level, $p->active_assignments_count, $p->submitted_reviews,
                    $p->avg_score !== null ? number_format((float) $p->avg_score, 2, '.', '') : '',
                ];
            }
        };

        return [$headers, $rows];
    }

    private function exportReviews(): array
    {
        $headers = ['ID', 'Proposal', 'Tema', 'Fase', 'Reviewer', 'Status', 'Rekomendasi', 'Skor tertimbang', 'Dikirim', 'Dibuka kembali', 'Status penugasan'];
        $rows = function () {
            foreach (Review::query()->with(['proposal.theme', 'phase', 'reviewer', 'assignment'])->orderBy('id')->cursor() as $r) {
                yield [
                    $r->id, $r->proposal?->product_name, $r->proposal?->theme?->name, $r->phase?->name, $r->reviewer?->name,
                    $r->status, $r->recommendationLabel(), $r->weighted_score, $r->submitted_at?->format('Y-m-d H:i'),
                    $r->reopened_at?->format('Y-m-d H:i'), $r->assignment?->statusLabel(),
                ];
            }
        };

        return [$headers, $rows];
    }

    private function exportScores(): array
    {
        $headers = ['Review ID', 'Proposal', 'Fase', 'Reviewer', 'Kriteria', 'Bobot (%)', 'Skor', 'Skor maks', 'Komentar'];
        $rows = function () {
            foreach (ReviewScore::query()->with(['review.proposal', 'review.phase', 'review.reviewer', 'criterion'])->orderBy('review_id')->cursor() as $s) {
                yield [
                    $s->review_id, $s->review?->proposal?->product_name, $s->review?->phase?->name, $s->review?->reviewer?->name,
                    $s->criterion?->name, $s->criterion?->weight, $s->score, $s->criterion?->max_score, $s->comment,
                ];
            }
        };

        return [$headers, $rows];
    }

    private function exportAssignments(): array
    {
        $headers = ['ID', 'Proposal', 'Reviewer', 'Fase', 'Status', 'Ditugaskan oleh', 'Ditugaskan', 'Batas', 'Alasan konflik', 'Alasan pencabutan', 'Catatan'];
        $rows = function () {
            foreach (ReviewerAssignment::query()->with(['proposal', 'reviewer', 'phase', 'assigner'])->orderBy('id')->cursor() as $a) {
                yield [
                    $a->id, $a->proposal?->product_name, $a->reviewer?->name, $a->phase?->name, $a->statusLabel(), $a->assigner?->name,
                    $a->created_at?->format('Y-m-d H:i'), $a->due_at?->format('Y-m-d H:i'), $a->conflict_reason, $a->removal_reason, $a->override_note,
                ];
            }
        };

        return [$headers, $rows];
    }

    private function exportTeams(): array
    {
        $headers = ['Tim ID', 'Tim', 'Institusi', 'Kota', 'Provinsi', 'Ketua', 'Anggota', 'Surel anggota', 'Keahlian', 'Institusi anggota', 'Peran', 'Status anggota'];
        $rows = function () {
            foreach (Team::query()->with(['leader', 'members'])->orderBy('id')->cursor() as $team) {
                foreach ($team->members as $member) {
                    yield [
                        $team->id, $team->name, $team->institution, $team->city, $team->province, $team->leader?->name,
                        $member->name, $member->email, $member->discipline, $member->institution, $member->isLeader() ? 'Ketua' : 'Anggota', $member->statusLabel(),
                    ];
                }
            }
        };

        return [$headers, $rows];
    }

    private function exportParticipants(): array
    {
        $headers = ['ID', 'Nama', 'Surel', 'Telepon', 'Institusi', 'Kota', 'Provinsi', 'Bidang keahlian', 'Jenis peserta', 'Persetujuan data', 'Terdaftar', 'Aktif'];
        $rows = function () {
            foreach (User::query()->where('role', UserRole::Participant->value)->orderBy('id')->cursor() as $u) {
                yield [
                    $u->id, $u->name, $u->email, $u->phone, $u->institution, $u->city, $u->province, $u->discipline,
                    Options::label(Options::participantTypes(), $u->participant_type, ''), $u->consented_at?->format('Y-m-d H:i'),
                    $u->created_at?->format('Y-m-d H:i'), $u->is_active ? 'Ya' : 'Tidak',
                ];
            }
        };

        return [$headers, $rows];
    }

    private function exportProducts(): array
    {
        $headers = ['ID', 'Judul publik', 'Tema', 'Tantangan', 'Status', 'Publik', 'Diterbitkan', 'PDF publik', 'Lencana', 'Alasan disembunyikan', 'URL'];
        $rows = function () {
            foreach (Proposal::query()->whereNotNull('approved_at')->with(['theme', 'challenge'])->orderBy('id')->cursor() as $p) {
                yield [
                    $p->id, $p->displayTitle(), $p->theme?->name, $p->challenge?->title, $p->status->label(), $p->is_public ? 'Ya' : 'Tidak',
                    $p->published_at?->format('Y-m-d H:i'), $p->public_pdf_enabled ? 'Ya' : 'Tidak', $p->publicBadgeLabel(), $p->hidden_reason,
                    $p->public_slug ? route('products.show', $p->public_slug) : '',
                ];
            }
        };

        return [$headers, $rows];
    }
}
