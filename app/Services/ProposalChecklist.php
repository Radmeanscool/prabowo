<?php

namespace App\Services;

use App\Models\Proposal;
use App\Models\ThemeQuestion;

/**
 * Daftar periksa kelengkapan proposal yang ditampilkan ke peserta dan
 * dipakai sebagai syarat sebelum proposal dapat dikirim.
 */
class ProposalChecklist
{
    /**
     * @return array<int, array{key: string, label: string, done: bool, section: string}>
     */
    public static function items(Proposal $proposal): array
    {
        $proposal->loadMissing(['team.members', 'files', 'answers']);
        $limit = max(50, (int) setting('proposal_word_limit', 300));
        $items = [];

        $add = function (string $key, string $label, bool $done, string $section) use (&$items) {
            $items[] = compact('key', 'label', 'done', 'section');
        };

        $add('theme', 'Tema proposal dipilih', (bool) $proposal->theme_id, 'tema');
        if (setting('require_challenge_selection', true)) {
            $add('challenge', 'Tantangan dipilih', (bool) $proposal->challenge_id, 'tema');
        }

        $add('identity', 'Nama produk dan ringkasan satu kalimat', filled($proposal->product_name) && filled($proposal->short_summary), 'identitas');

        $minTeam = max(1, (int) setting('min_team_size', 1));
        $activeMembers = $proposal->team?->members->where('status', 'active')->count() ?? 0;
        $add('team', "Tim memiliki minimal {$minTeam} anggota aktif", $activeMembers >= $minTeam, 'tim');
        $add('contact', 'Narahubung (nama dan surel)', filled($proposal->contact_name) && filled($proposal->contact_email), 'tim');

        $add('technical', 'Aspek teknis diisi', filled($proposal->technical_description), 'teknis');
        $add('market', 'Pengguna sasaran, aspek pasar, dan dampak', filled($proposal->target_users) && filled($proposal->market_description) && filled($proposal->impact_description), 'pasar');
        $add('fit', 'Masalah, solusi, dan indikator keberhasilan', filled($proposal->problem_statement) && filled($proposal->solution_description) && filled($proposal->success_indicators), 'kesesuaian');
        $add('readiness', 'Status KI, regulasi, etik, data, dan rencana mitigasi', filled($proposal->ip_status) && filled($proposal->regulatory_status) && filled($proposal->ethics_status) && filled($proposal->data_risk_status) && filled($proposal->mitigation_plan), 'kesiapan');

        $required = ThemeQuestion::query()->forTheme($proposal->theme_id)->where('is_required', true)->pluck('id');
        if ($required->isNotEmpty()) {
            $answered = $proposal->answers->filter(fn ($a) => filled($a->answer))->pluck('theme_question_id');
            $add('theme_questions', 'Pertanyaan khusus tema dijawab', $required->diff($answered)->isEmpty(), 'tema-khusus');
        }

        $overLimit = collect(Proposal::LONG_TEXT_FIELDS)->contains(fn ($field) => word_count($proposal->{$field}) > $limit);
        $add('word_limit', "Setiap isian maksimal {$limit} kata", ! $overLimit, 'identitas');

        $add('cover_image', 'Gambar produk diunggah', $proposal->files->contains('type', 'cover_image'), 'berkas');
        $add('proposal_pdf', 'Proposal resmi (PDF) diunggah', $proposal->files->contains('type', 'proposal_pdf'), 'berkas');

        return $items;
    }

    public static function isComplete(Proposal $proposal): bool
    {
        return collect(self::items($proposal))->every(fn ($item) => $item['done']);
    }

    /** @return array<int, string> */
    public static function missing(Proposal $proposal): array
    {
        return collect(self::items($proposal))->reject(fn ($item) => $item['done'])->pluck('label')->all();
    }

    public static function percent(Proposal $proposal): int
    {
        $items = self::items($proposal);
        if (! $items) {
            return 0;
        }

        return (int) round(collect($items)->where('done', true)->count() / count($items) * 100);
    }
}
