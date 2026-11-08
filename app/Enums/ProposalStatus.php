<?php

namespace App\Enums;

/**
 * Status proposal. Seleksi (draft → submitted → under_review → …) dan tahapan program
 * (bootcamp → … → downstreaming) memakai satu kolom status agar riwayatnya utuh.
 *
 * Publikasi produk TIDAK diwakili status: keputusan `approved` otomatis mengisi
 * `is_public = true` dan `published_at`. Nilai `published` disediakan untuk
 * kompatibilitas laporan, tetapi tidak dipakai oleh alur kerja.
 */
enum ProposalStatus: string
{
    case Draft = 'draft';
    case Submitted = 'submitted';
    case UnderReview = 'under_review';
    case RevisionRequested = 'revision_requested';
    case Shortlisted = 'shortlisted';
    case Accepted = 'accepted';
    case Bootcamp = 'bootcamp';
    case Mentoring = 'mentoring';
    case PrototypeDevelopment = 'prototype_development';
    case DemoDay = 'demo_day';
    case SelectedPrototype = 'selected_prototype';
    case PilotPreparation = 'pilot_preparation';
    case Pilot = 'pilot';
    case Incubation = 'incubation';
    case Downstreaming = 'downstreaming';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Submitted => 'Terkirim',
            self::UnderReview => 'Dalam Penilaian',
            self::RevisionRequested => 'Perlu Revisi',
            self::Shortlisted => 'Daftar Pendek',
            self::Accepted => 'Diterima',
            self::Bootcamp => 'Bootcamp',
            self::Mentoring => 'Mentoring Sprint',
            self::PrototypeDevelopment => 'Pengembangan Purwarupa',
            self::DemoDay => 'Demo Day',
            self::SelectedPrototype => 'Purwarupa Terpilih',
            self::PilotPreparation => 'Persiapan Pilot',
            self::Pilot => 'Pilot Project',
            self::Incubation => 'Inkubasi',
            self::Downstreaming => 'Hilirisasi',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Published => 'Dipublikasikan',
            self::Archived => 'Diarsipkan',
        };
    }

    /** Nada warna lencana status (dipetakan ke kelas CSS .badge-*). */
    public function tone(): string
    {
        return match ($this) {
            self::Draft, self::Archived => 'neutral',
            self::Submitted => 'info',
            self::UnderReview, self::Shortlisted => 'indigo',
            self::RevisionRequested => 'warning',
            self::Accepted, self::Approved, self::Published => 'success',
            self::Rejected => 'danger',
            self::SelectedPrototype => 'violet',
            default => 'teal',
        };
    }

    /** Status yang masih bisa disunting oleh peserta. */
    public function isEditableByParticipant(): bool
    {
        return in_array($this, [self::Draft, self::RevisionRequested], true);
    }

    /** Status yang bisa dikirim oleh peserta (kirim pertama atau ajukan revisi). */
    public function isSubmittableByParticipant(): bool
    {
        return $this->isEditableByParticipant();
    }

    public function isFinalNegative(): bool
    {
        return in_array($this, [self::Rejected, self::Archived], true);
    }

    /** Status yang menandakan tahapan program setelah diterima. */
    public static function programStageStatuses(): array
    {
        return [
            self::Bootcamp, self::Mentoring, self::PrototypeDevelopment, self::DemoDay,
            self::SelectedPrototype, self::PilotPreparation, self::Pilot, self::Incubation, self::Downstreaming,
        ];
    }

    /** Kode tahapan program (tabel program_stages) yang sesuai dengan status ini. */
    public function programStageCode(): ?string
    {
        return match ($this) {
            self::Accepted => 'open_call',
            self::Bootcamp => 'bootcamp',
            self::Mentoring => 'mentoring_sprint',
            self::PrototypeDevelopment => 'prototype_development_sprint',
            self::DemoDay => 'demo_day',
            self::SelectedPrototype, self::PilotPreparation => 'prototype_funding',
            self::Pilot => 'pilot_project',
            self::Incubation, self::Downstreaming => 'incubation_downstreaming',
            default => null,
        };
    }

    /**
     * Transisi yang boleh dilakukan admin dari status ini.
     *
     * @return array<int, self>
     */
    public function adminTransitions(): array
    {
        return match ($this) {
            self::Draft => [self::Archived],
            self::Submitted => [self::UnderReview, self::RevisionRequested, self::Shortlisted, self::Accepted, self::Approved, self::Rejected, self::Archived],
            self::UnderReview => [self::RevisionRequested, self::Shortlisted, self::Accepted, self::Approved, self::Rejected, self::Archived],
            self::RevisionRequested => [self::UnderReview, self::Rejected, self::Archived],
            self::Shortlisted => [self::UnderReview, self::RevisionRequested, self::Accepted, self::Approved, self::Rejected, self::Archived],
            self::Accepted => [self::Bootcamp, self::Mentoring, self::PrototypeDevelopment, self::DemoDay, self::SelectedPrototype, self::Approved, self::Rejected, self::Archived],
            self::Bootcamp => [self::Mentoring, self::PrototypeDevelopment, self::DemoDay, self::Approved, self::Rejected, self::Archived],
            self::Mentoring => [self::PrototypeDevelopment, self::DemoDay, self::Approved, self::Rejected, self::Archived],
            self::PrototypeDevelopment => [self::DemoDay, self::Approved, self::Rejected, self::Archived],
            self::DemoDay => [self::SelectedPrototype, self::Approved, self::Rejected, self::Archived],
            self::SelectedPrototype => [self::PilotPreparation, self::Pilot, self::Approved, self::Archived],
            self::PilotPreparation => [self::Pilot, self::Approved, self::Archived],
            self::Pilot => [self::Incubation, self::Downstreaming, self::Approved, self::Archived],
            self::Incubation => [self::Downstreaming, self::Approved, self::Archived],
            self::Downstreaming => [self::Approved, self::Archived],
            self::Approved => [self::SelectedPrototype, self::PilotPreparation, self::Pilot, self::Incubation, self::Downstreaming, self::Rejected, self::Archived],
            self::Rejected => [self::UnderReview, self::Archived],
            self::Published => [self::Archived],
            self::Archived => [self::UnderReview],
        };
    }

    public function canTransitionTo(self $to): bool
    {
        return in_array($to, $this->adminTransitions(), true);
    }

    /** Label tombol aksi admin untuk menuju status ini. */
    public function actionLabel(): string
    {
        return match ($this) {
            self::UnderReview => 'Tandai Dalam Penilaian',
            self::RevisionRequested => 'Minta Revisi',
            self::Shortlisted => 'Masukkan Daftar Pendek',
            self::Accepted => 'Terima sebagai Peserta',
            self::Approved => 'Setujui',
            self::Rejected => 'Tolak',
            self::Archived => 'Arsipkan',
            self::SelectedPrototype => 'Pilih sebagai Finalis Purwarupa',
            default => 'Pindahkan ke '.$this->label(),
        };
    }

    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            if ($case === self::Published) {
                continue;
            }
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
