<?php

namespace App\Support;

/**
 * Daftar pilihan terkendali (controlled vocabulary) untuk formulir dan laporan.
 */
class Options
{
    public static function participantTypes(): array
    {
        return [
            'student' => 'Mahasiswa',
            'researcher' => 'Dosen/Peneliti',
            'startup' => 'Startup',
            'professional' => 'Profesional/Praktisi',
            'community' => 'Inovator Masyarakat',
            'other' => 'Lainnya',
        ];
    }

    public static function prototypeStatuses(): array
    {
        return [
            'idea' => 'Gagasan/konsep',
            'validated_concept' => 'Konsep tervalidasi',
            'lab_prototype' => 'Purwarupa skala laboratorium',
            'functional_prototype' => 'Purwarupa fungsional',
            'field_tested' => 'Sudah diuji di lingkungan nyata',
            'market_ready' => 'Siap dipasarkan',
        ];
    }

    public static function ipStatuses(): array
    {
        return [
            'none' => 'Belum ada perlindungan KI',
            'identified' => 'Potensi KI teridentifikasi, belum diajukan',
            'filed' => 'Sudah diajukan (paten/paten sederhana/hak cipta/merek)',
            'granted' => 'Sudah terdaftar/diberikan',
            'trade_secret' => 'Dijaga sebagai rahasia dagang',
            'not_applicable' => 'Tidak relevan',
        ];
    }

    public static function regulatoryStatuses(): array
    {
        return [
            'not_identified' => 'Belum diidentifikasi',
            'identified' => 'Sudah diidentifikasi, belum diproses',
            'in_process' => 'Sedang diproses',
            'obtained' => 'Sudah diperoleh',
            'not_required' => 'Tidak diperlukan',
        ];
    }

    public static function ethicsStatuses(): array
    {
        return [
            'not_required' => 'Tidak memerlukan persetujuan etik',
            'required_not_submitted' => 'Diperlukan, belum diajukan',
            'submitted' => 'Sedang diajukan ke komisi etik',
            'approved' => 'Sudah memperoleh persetujuan etik',
        ];
    }

    public static function dataRiskStatuses(): array
    {
        return [
            'none' => 'Tidak melibatkan data pribadi',
            'personal' => 'Melibatkan data pribadi',
            'health' => 'Melibatkan data kesehatan',
            'sensitive_other' => 'Melibatkan data sensitif lain (lokasi, keuangan, dll.)',
        ];
    }

    public static function recommendations(): array
    {
        return [
            'proceed' => 'Lanjutkan',
            'proceed_with_conditions' => 'Lanjutkan dengan syarat',
            'request_revision' => 'Minta revisi',
            'do_not_proceed' => 'Tidak dilanjutkan',
            'conflict_of_interest' => 'Konflik kepentingan',
        ];
    }

    public static function assignmentStatuses(): array
    {
        return [
            'assigned' => 'Ditugaskan',
            'in_progress' => 'Sedang dinilai',
            'submitted' => 'Penilaian terkirim',
            'conflict_declared' => 'Konflik kepentingan',
            'removed' => 'Dicabut',
        ];
    }

    public static function fileTypes(): array
    {
        return [
            'cover_image' => 'Gambar produk',
            'proposal_pdf' => 'Proposal resmi (PDF)',
            'technical_appendix' => 'Lampiran teknis',
            'supporting_evidence' => 'Bukti pendukung',
            'legal_evidence' => 'Dokumen legal/KI',
            'internal_legal' => 'Telaah internal (khusus admin)',
        ];
    }

    public static function challengeOwnerTypes(): array
    {
        return [
            'pemerintah_daerah' => 'Pemerintah daerah',
            'instansi_pemerintah' => 'Instansi pemerintah',
            'industri' => 'Industri',
            'koperasi' => 'Koperasi',
            'asosiasi' => 'Asosiasi',
            'fasilitas_kesehatan' => 'Fasilitas pelayanan kesehatan',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function participationStatuses(): array
    {
        return [
            'invited' => 'Diundang',
            'pending_confirmation' => 'Menunggu konfirmasi',
            'confirmed' => 'Terkonfirmasi',
        ];
    }

    public static function personTypes(): array
    {
        return [
            'mentor' => 'Mentor',
            'juror' => 'Juri',
            'speaker' => 'Narasumber',
        ];
    }

    public static function personCategories(): array
    {
        return [
            'internal' => 'Internal Unhas',
            'partner' => 'Mitra/praktisi',
            'external' => 'Eksternal',
        ];
    }

    public static function announcementCategories(): array
    {
        return [
            'pengumuman' => 'Pengumuman',
            'berita' => 'Berita',
            'cerita' => 'Cerita',
        ];
    }

    public static function audiences(): array
    {
        return [
            'public' => 'Publik',
            'all_users' => 'Semua pengguna terdaftar',
            'participants' => 'Peserta',
            'reviewers' => 'Reviewer/juri',
        ];
    }

    public static function documentCategories(): array
    {
        return [
            'panduan' => 'Panduan',
            'template' => 'Templat',
            'kebijakan' => 'Kebijakan',
            'materi' => 'Materi',
            'lainnya' => 'Lainnya',
        ];
    }

    public static function documentVisibilities(): array
    {
        return [
            'public' => 'Publik',
            'participants' => 'Peserta (login)',
            'reviewers' => 'Reviewer/juri',
            'admins' => 'Admin saja',
        ];
    }

    public static function agendaStatuses(): array
    {
        return [
            'planned' => 'Rencana',
            'confirmed' => 'Terkonfirmasi',
            'completed' => 'Selesai',
            'postponed' => 'Ditunda',
        ];
    }

    public static function stageStatuses(): array
    {
        return [
            'upcoming' => 'Akan datang',
            'ongoing' => 'Berlangsung',
            'completed' => 'Selesai',
        ];
    }

    public static function challengeStatuses(): array
    {
        return [
            'draft' => 'Draf',
            'published' => 'Terbit',
            'archived' => 'Diarsipkan',
        ];
    }

    public static function publicBadges(): array
    {
        return [
            'approved' => 'Disetujui',
            'selected_prototype' => 'Purwarupa Terpilih',
            'pilot' => 'Pilot',
            'incubation' => 'Inkubasi',
            'adopted' => 'Diadopsi',
        ];
    }

    public static function memberStatuses(): array
    {
        return [
            'active' => 'Aktif',
            'pending' => 'Menunggu persetujuan',
            'rejected' => 'Ditolak',
        ];
    }

    public static function label(array $options, ?string $key, string $fallback = '—'): string
    {
        if ($key === null || $key === '') {
            return $fallback;
        }

        return $options[$key] ?? $key;
    }
}
