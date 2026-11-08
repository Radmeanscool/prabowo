<?php

namespace App\Services;

use App\Enums\ProposalStatus;
use App\Models\ProgramStage;
use App\Models\Proposal;
use App\Models\ProposalStatusHistory;
use App\Models\ProposalVersion;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya tempat perubahan status proposal dan publikasi produk.
 *
 * Aturan utama:
 * - Reviewer tidak pernah mengubah status atau publikasi.
 * - Keputusan `approved` oleh admin otomatis menayangkan ringkasan publik
 *   (is_public = true, published_at = now) tanpa langkah "Terbitkan" kedua.
 * - PDF proposal tetap privat kecuali admin mengaktifkan public_pdf_enabled.
 * - Menyembunyikan produk tidak menghapus proposal, penilaian, atau log audit.
 */
class ProposalWorkflow
{
    public function submit(Proposal $proposal, User $user): void
    {
        $from = $proposal->status;
        if (! $from->isSubmittableByParticipant()) {
            throw new DomainException('Proposal ini tidak dapat dikirim pada status saat ini.');
        }

        DB::transaction(function () use ($proposal, $user, $from) {
            $now = now();
            $proposal->version = (int) $proposal->version + 1;
            $proposal->status = ProposalStatus::Submitted;
            $proposal->submitted_at = $now;
            $proposal->first_submitted_at ??= $now;
            $proposal->save();

            ProposalVersion::create([
                'proposal_id' => $proposal->id,
                'version' => $proposal->version,
                'data' => $this->snapshot($proposal->fresh()),
                'submitted_by' => $user->id,
                'submitted_at' => $now,
            ]);

            $isRevision = $from === ProposalStatus::RevisionRequested;
            $this->recordHistory($proposal, $from, ProposalStatus::Submitted, $user, $isRevision ? 'Revisi proposal diajukan.' : 'Proposal dikirim.');
            Audit::log(
                $isRevision ? 'proposal.resubmitted' : 'proposal.submitted',
                $proposal,
                ['status' => $from->value],
                ['status' => ProposalStatus::Submitted->value, 'version' => $proposal->version],
                $isRevision ? 'Revisi proposal diajukan' : 'Proposal dikirim',
                $user,
            );
        });

        Notifier::proposalSubmitted($proposal);
    }

    /**
     * Ubah status oleh admin. $publicData dipakai saat menyetujui untuk mengisi ringkasan publik.
     */
    public function transition(Proposal $proposal, ProposalStatus $to, User $actor, ?string $note = null, array $publicData = []): void
    {
        if (! $actor->isAdmin()) {
            throw new DomainException('Hanya admin yang dapat menetapkan status proposal.');
        }

        $from = $proposal->status;
        if (! $from->canTransitionTo($to)) {
            throw new DomainException("Status tidak dapat diubah dari {$from->label()} ke {$to->label()}.");
        }

        DB::transaction(function () use ($proposal, $to, $actor, $note, $publicData, $from) {
            $proposal->status = $to;
            $now = now();

            switch ($to) {
                case ProposalStatus::RevisionRequested:
                    $proposal->reopened_at = $now;
                    break;
                case ProposalStatus::Rejected:
                    $proposal->rejected_at = $now;
                    $proposal->rejection_reason = $note;
                    break;
                case ProposalStatus::Approved:
                    $proposal->approved_at = $now;
                    $proposal->approved_by = $actor->id;
                    break;
                default:
                    break;
            }

            if ($note !== null && $note !== '') {
                $proposal->decision_note = $note;
            }

            if ($code = $to->programStageCode()) {
                $stageId = ProgramStage::query()->where('code', $code)->value('id');
                if ($stageId) {
                    $proposal->program_stage_id = $stageId;
                }
            }

            $proposal->save();

            if ($to === ProposalStatus::Approved) {
                $this->publishOnApproval($proposal, $actor, $publicData);
            }

            if ($to->isFinalNegative() && $proposal->is_public) {
                $proposal->forceFill([
                    'is_public' => false,
                    'unpublished_at' => $now,
                ])->save();
                Audit::log('product.unpublished', $proposal, ['is_public' => true], ['is_public' => false], 'Publikasi dicabut otomatis karena status '.$to->label(), $actor);
            }

            $this->recordHistory($proposal, $from, $to, $actor, $note);
            Audit::log('proposal.status_changed', $proposal, ['status' => $from->value], ['status' => $to->value, 'note' => $note], 'Status: '.$from->label().' → '.$to->label(), $actor);
        });

        Notifier::statusChanged($proposal, $to);
    }

    /**
     * Penayangan otomatis saat disetujui. Jika admin sebelumnya menyembunyikan produk
     * secara eksplisit (hidden_reason terisi), produk tetap tersembunyi sampai admin menayangkannya kembali.
     */
    protected function publishOnApproval(Proposal $proposal, User $actor, array $publicData): void
    {
        $this->fillPublicSummary($proposal, $publicData);

        if ($proposal->hidden_reason !== null && $proposal->published_at !== null) {
            $proposal->save();
            Audit::log('product.publication_kept_hidden', $proposal, [], [], 'Disetujui ulang; produk tetap tersembunyi karena sebelumnya dicabut admin', $actor);

            return;
        }

        $proposal->is_public = true;
        $proposal->published_at = now();
        $proposal->unpublished_at = null;
        $proposal->hidden_reason = null;
        $proposal->save();

        Audit::log('product.published', $proposal, ['is_public' => false], [
            'is_public' => true,
            'public_slug' => $proposal->public_slug,
            'automatic' => true,
        ], 'Produk otomatis tampil publik setelah disetujui', $actor);
    }

    protected function fillPublicSummary(Proposal $proposal, array $publicData): void
    {
        $proposal->loadMissing(['team', 'files']);

        $title = trim((string) ($publicData['public_title'] ?? '')) ?: ($proposal->public_title ?: $proposal->product_name);
        $proposal->public_title = $title;
        $proposal->public_slug ??= Proposal::uniqueSlug($title, 'public_slug');
        $proposal->public_short_description = trim((string) ($publicData['public_short_description'] ?? ''))
            ?: ($proposal->public_short_description ?: $proposal->short_summary);

        foreach (['public_full_description', 'public_problem', 'public_solution', 'public_target_users', 'public_technology', 'public_impact', 'public_team_label'] as $field) {
            if (array_key_exists($field, $publicData) && $publicData[$field] !== null) {
                $proposal->{$field} = $publicData[$field];
            }
        }

        foreach (['public_show_team', 'public_show_tkt', 'public_show_links'] as $flag) {
            if (array_key_exists($flag, $publicData)) {
                $proposal->{$flag} = (bool) $publicData[$flag];
            } elseif ($flag === 'public_show_team' && $proposal->published_at === null) {
                $proposal->public_show_team = (bool) setting('publication_show_team_default', false);
            }
        }

        $proposal->public_badge = $publicData['public_badge'] ?? ($proposal->public_badge ?: 'approved');

        if (! $proposal->public_image_path) {
            $cover = $proposal->currentFile('cover_image');
            if ($cover) {
                // Salin gambar sampul ke berkas publik terpisah agar perubahan oleh peserta tidak memengaruhi katalog.
                $proposal->public_image_path = $this->copyToPublicImage($proposal, $cover->path);
            }
        }
    }

    protected function copyToPublicImage(Proposal $proposal, string $path): ?string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('local');
        if (! $disk->exists($path)) {
            return null;
        }

        $target = 'public-products/'.$proposal->id.'/'.\Illuminate\Support\Str::random(32).'.'.pathinfo($path, PATHINFO_EXTENSION);
        $disk->copy($path, $target);

        return $target;
    }

    public function unpublish(Proposal $proposal, User $actor, string $reason): void
    {
        $was = $proposal->is_public;
        $proposal->forceFill([
            'is_public' => false,
            'unpublished_at' => now(),
            'hidden_reason' => $reason,
        ])->save();

        Audit::log('product.unpublished', $proposal, ['is_public' => $was], ['is_public' => false, 'reason' => $reason], 'Publikasi dicabut: '.$reason, $actor);
    }

    public function republish(Proposal $proposal, User $actor): void
    {
        if (! $proposal->isPublishable()) {
            throw new DomainException('Hanya proposal yang pernah disetujui dan tidak ditolak/diarsipkan yang dapat diterbitkan.');
        }

        $this->fillPublicSummary($proposal, []);
        $proposal->forceFill([
            'is_public' => true,
            'published_at' => $proposal->published_at ?? now(),
            'unpublished_at' => null,
            'hidden_reason' => null,
        ])->save();

        Audit::log('product.published', $proposal, ['is_public' => false], ['is_public' => true, 'automatic' => false], 'Produk diterbitkan kembali', $actor);
    }

    public function setPublicPdf(Proposal $proposal, bool $enabled, User $actor): void
    {
        $old = $proposal->public_pdf_enabled;
        $proposal->forceFill(['public_pdf_enabled' => $enabled])->save();
        Audit::log('product.public_pdf_changed', $proposal, ['public_pdf_enabled' => $old], ['public_pdf_enabled' => $enabled], $enabled ? 'PDF proposal dibuka untuk publik' : 'PDF proposal kembali privat', $actor);
    }

    public function updatePublicSummary(Proposal $proposal, array $data, User $actor): void
    {
        $proposal->fill(collect($data)->only(Proposal::PUBLIC_FIELDS)->all());
        [$old, $new] = Audit::diff($proposal);
        $proposal->save();
        if ($new) {
            Audit::log('product.summary_updated', $proposal, $old, $new, 'Ringkasan publik diperbarui', $actor);
        }
    }

    public function recordHistory(Proposal $proposal, ?ProposalStatus $from, ProposalStatus $to, ?User $user, ?string $note = null): void
    {
        ProposalStatusHistory::create([
            'proposal_id' => $proposal->id,
            'from_status' => $from?->value,
            'to_status' => $to->value,
            'note' => $note,
            'changed_by' => $user?->id,
        ]);
    }

    /** Salinan isian saat dikirim (untuk versi dan jejak audit). */
    public function snapshot(Proposal $proposal): array
    {
        $proposal->loadMissing(['theme', 'challenge', 'team.members', 'researchHistories', 'files', 'answers.question']);

        return [
            'fields' => collect(Proposal::PARTICIPANT_TEXT_FIELDS)->mapWithKeys(fn ($f) => [$f => $proposal->{$f}])->all()
                + ['tkt_level' => $proposal->tkt_level],
            'theme' => $proposal->theme?->only(['id', 'code', 'name']),
            'challenge' => $proposal->challenge?->only(['id', 'title']),
            'team' => [
                'id' => $proposal->team?->id,
                'name' => $proposal->team?->name,
                'institution' => $proposal->team?->institution,
                'members' => $proposal->team?->members->map->only(['name', 'email', 'institution', 'discipline', 'role', 'status'])->values()->all(),
            ],
            'research' => $proposal->researchHistories->map->only(['year', 'title', 'researchers', 'funding_amount', 'funding_source'])->values()->all(),
            'files' => $proposal->files->map->only(['uuid', 'type', 'original_name', 'mime_type', 'size', 'checksum'])->values()->all(),
            'answers' => $proposal->answers->map(fn ($a) => ['question' => $a->question?->question, 'answer' => $a->answer])->values()->all(),
        ];
    }
}
