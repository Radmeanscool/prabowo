<?php

namespace App\Support;

use App\Models\Setting;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pengaturan operasional yang dapat diubah admin tanpa menyentuh kode.
 * Nilai disimpan sebagai JSON di tabel `settings` dan di-cache.
 */
class Settings
{
    public const CACHE_KEY = 'deepnest.settings.v1';

    public static function defaults(): array
    {
        return [
            'site_name' => 'DEEPNEST',
            'site_full_name' => 'Deeptech Ecosystem for Emerging Prototype and Sustainable Technologies',
            'organizer' => 'Universitas Hasanuddin',
            'tagline' => 'Dari Tantangan Nyata Menuju Inovasi Berdampak',
            'tagline_en' => 'From Real Challenges to Impactful Innovation',
            'spirit' => 'Dari Indonesia Timur untuk Nusantara',
            'event_period' => 'Program 12 bulan',
            'event_location' => 'Universitas Hasanuddin, Makassar',
            'hero_intro' => 'Hackathon deep technology Universitas Hasanuddin yang mempertemukan pemilik masalah nyata, inovator lintas disiplin, mentor, dan mitra hilirisasi untuk menghasilkan purwarupa fungsional di bidang ketahanan pangan dan kesehatan.',
            'contact_email' => 'office@unhas.ac.id',
            'contact_phone' => '+62 811 5003636',
            'contact_address' => 'Direktorat Inovasi dan Kekayaan Intelektual, Universitas Hasanuddin, Jl. Perintis Kemerdekaan Km. 10, Tamalanrea, Makassar 90245',

            // Open call
            'open_call_mode' => 'auto', // auto | open | closed
            'open_call_start_at' => null,
            'open_call_end_at' => null,
            'registration_enabled' => true,

            // Aturan tim dan proposal
            'max_team_size' => 6,
            'min_team_size' => 1,
            'one_team_per_participant' => true,
            'team_leader_approval_required' => true,
            'max_proposals_per_team' => 1,
            'require_challenge_selection' => true,
            'proposal_word_limit' => 300,
            'max_pdf_size_mb' => 10,
            'max_image_size_mb' => 5,

            // Penilaian
            'reviewer_self_selection_enabled' => false,
            'blind_review' => false,
            'show_scores_to_participants' => false,
            'show_reviewer_comments_to_participants' => false,

            // Publikasi
            'publication_show_team_default' => false,

            // Notifikasi
            'email_notifications_enabled' => false,

            'homepage_stats' => [
                ['value' => '15', 'label' => 'Tim lintas disiplin', 'note' => 'Target peserta program'],
                ['value' => '≥ 3', 'label' => 'Purwarupa fungsional', 'note' => 'Target tervalidasi awal'],
                ['value' => '30', 'label' => 'Mentor', 'note' => 'Rencana: 15 dosen Unhas dan 15 praktisi mitra'],
                ['value' => '7', 'label' => 'Juri', 'note' => 'Rencana: Unhas, BRIN, Kemenkes, Kementan'],
                ['value' => '38 JP', 'label' => 'Bimbingan teknis', 'note' => '1 JP setara 45 menit'],
                ['value' => '192', 'label' => 'Laboratorium Unhas', 'note' => 'Pendukung pengembangan dan pengujian'],
            ],
        ];
    }

    /** Kunci yang bertipe boolean (untuk normalisasi input formulir). */
    public static function booleanKeys(): array
    {
        return [
            'registration_enabled', 'one_team_per_participant', 'team_leader_approval_required',
            'require_challenge_selection', 'reviewer_self_selection_enabled', 'blind_review',
            'show_scores_to_participants', 'show_reviewer_comments_to_participants',
            'publication_show_team_default', 'email_notifications_enabled',
        ];
    }

    public static function integerKeys(): array
    {
        return [
            'max_team_size', 'min_team_size', 'max_proposals_per_team', 'proposal_word_limit',
            'max_pdf_size_mb', 'max_image_size_mb',
        ];
    }

    public static function all(): array
    {
        $stored = Cache::rememberForever(self::CACHE_KEY, function () {
            try {
                if (! Schema::hasTable('settings')) {
                    return [];
                }

                return Setting::query()->pluck('value', 'key')
                    ->map(fn ($value) => json_decode((string) $value, true))
                    ->all();
            } catch (Throwable) {
                return [];
            }
        });

        return array_replace(self::defaults(), $stored);
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = self::all();

        return array_key_exists($key, $all) ? $all[$key] : $default;
    }

    public static function set(array $values): void
    {
        foreach ($values as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => json_encode($value, JSON_UNESCAPED_UNICODE)]
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function date(string $key): ?CarbonImmutable
    {
        $value = self::get($key);
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse($value, config('app.timezone'));
        } catch (Throwable) {
            return null;
        }
    }

    public static function openCallIsOpen(): bool
    {
        return self::openCallState()['open'];
    }

    /**
     * @return array{open: bool, label: string, start: ?CarbonImmutable, end: ?CarbonImmutable, phase: string}
     */
    public static function openCallState(): array
    {
        $mode = self::get('open_call_mode', 'auto');
        $start = self::date('open_call_start_at');
        $end = self::date('open_call_end_at');
        $now = CarbonImmutable::now();

        if ($mode === 'open') {
            return ['open' => true, 'label' => 'Open Call dibuka', 'start' => $start, 'end' => $end, 'phase' => 'open'];
        }

        if ($mode === 'closed') {
            return ['open' => false, 'label' => 'Open Call belum/tidak dibuka', 'start' => $start, 'end' => $end, 'phase' => 'closed'];
        }

        if ($start && $end) {
            if ($now->lessThan($start)) {
                return ['open' => false, 'label' => 'Open Call segera dibuka', 'start' => $start, 'end' => $end, 'phase' => 'scheduled'];
            }
            if ($now->greaterThan($end)) {
                return ['open' => false, 'label' => 'Open Call telah ditutup', 'start' => $start, 'end' => $end, 'phase' => 'ended'];
            }

            return ['open' => true, 'label' => 'Open Call dibuka', 'start' => $start, 'end' => $end, 'phase' => 'open'];
        }

        return ['open' => false, 'label' => 'Jadwal Open Call akan diumumkan', 'start' => $start, 'end' => $end, 'phase' => 'tba'];
    }
}
