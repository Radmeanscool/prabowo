<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProposalStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Challenge;
use App\Models\ChallengeOwner;
use App\Models\Proposal;
use App\Models\ReviewPhase;
use App\Models\Theme;
use App\Models\User;
use App\Rules\FileContentType;
use App\Services\Audit;
use App\Services\ProposalChecklist;
use App\Services\ProposalWorkflow;
use App\Services\SecureFileStore;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'theme_id', 'challenge_id', 'owner_id', 'status', 'completeness', 'assignment']);

        $proposals = Proposal::query()
            ->with(['theme', 'challenge.owner', 'team', 'files'])
            ->withCount(['activeAssignments', 'reviews as submitted_reviews_count' => fn ($q) => $q->where('status', 'submitted')])
            ->withAvg(['reviews as avg_score' => fn ($q) => $q->where('status', 'submitted')], 'weighted_score')
            ->when($filters['q'] ?? null, fn ($q, $term) => $q->where(fn ($qq) => $qq->where('product_name', 'like', "%{$term}%")->orWhereHas('team', fn ($t) => $t->where('name', 'like', "%{$term}%")->orWhere('institution', 'like', "%{$term}%"))))
            ->when($filters['theme_id'] ?? null, fn ($q, $v) => $q->where('theme_id', $v))
            ->when($filters['challenge_id'] ?? null, fn ($q, $v) => $q->where('challenge_id', $v))
            ->when($filters['owner_id'] ?? null, fn ($q, $v) => $q->whereHas('challenge', fn ($c) => $c->where('challenge_owner_id', $v)))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when(($filters['completeness'] ?? null) === 'missing_files', fn ($q) => $q->where(fn ($qq) => $qq->whereDoesntHave('files', fn ($f) => $f->where('type', 'proposal_pdf'))->orWhereDoesntHave('files', fn ($f) => $f->where('type', 'cover_image'))))
            ->when(($filters['completeness'] ?? null) === 'files_ok', fn ($q) => $q->whereHas('files', fn ($f) => $f->where('type', 'proposal_pdf'))->whereHas('files', fn ($f) => $f->where('type', 'cover_image')))
            ->when(($filters['assignment'] ?? null) === 'none', fn ($q) => $q->whereNotIn('status', ['draft', 'rejected', 'archived'])->whereDoesntHave('activeAssignments'))
            ->when(($filters['assignment'] ?? null) === 'assigned', fn ($q) => $q->whereHas('activeAssignments'))
            ->orderByRaw("case when status = 'draft' then 1 else 0 end")
            ->latest('submitted_at')->latest('id')
            ->paginate(25)->withQueryString();

        return view('admin.proposals.index', [
            'proposals' => $proposals,
            'filters' => $filters,
            'themes' => Theme::query()->active()->pluck('name', 'id')->all(),
            'challenges' => Challenge::query()->orderBy('title')->pluck('title', 'id')->all(),
            'owners' => ChallengeOwner::query()->orderBy('name')->pluck('name', 'id')->all(),
            'statuses' => ProposalStatus::options(),
        ]);
    }

    public function show(Proposal $proposal): View
    {
        $proposal->load([
            'theme', 'challenge.owner', 'team.members', 'team.leader', 'researchHistories', 'files.uploader', 'answers.question',
            'statusHistories.changer', 'versions.submitter', 'programStage', 'reviewPhase', 'approver',
            'assignments' => fn ($q) => $q->with(['reviewer', 'phase', 'review', 'assigner'])->latest('id'),
            'mentorAssignments.person',
        ]);

        $phases = ReviewPhase::query()->ordered()->get();
        $reviewSummary = $phases->map(fn ($phase) => [
            'phase' => $phase,
            'reviews' => $proposal->assignments->where('review_phase_id', $phase->id)->where('status', 'submitted')->pluck('review')->filter(),
        ])->filter(fn ($row) => $row['reviews']->isNotEmpty());

        return view('admin.proposals.show', [
            'proposal' => $proposal,
            'transitions' => $proposal->status->adminTransitions(),
            'checklist' => ProposalChecklist::items($proposal),
            'phases' => $phases,
            'reviewers' => User::query()->where('role', UserRole::Reviewer->value)->where('is_active', true)
                ->withCount(['reviewerAssignments as open_count' => fn ($q) => $q->open()])->orderBy('name')->get(),
            'reviewSummary' => $reviewSummary,
            'auditTrail' => AuditLog::query()->where('auditable_type', $proposal->getMorphClass())->where('auditable_id', $proposal->id)->with('user')->latest('id')->limit(50)->get(),
        ]);
    }

    public function transition(Request $request, Proposal $proposal): RedirectResponse
    {
        $allowed = collect($proposal->status->adminTransitions())->map->value->all();
        $data = $request->validate([
            'to' => ['required', Rule::in($allowed)],
            'note' => [Rule::requiredIf(in_array($request->input('to'), ['revision_requested', 'rejected'], true)), 'nullable', 'string', 'max:3000'],
            'public_title' => ['nullable', 'string', 'max:190'],
            'public_short_description' => ['nullable', 'string', 'max:500'],
            'public_show_team' => ['nullable', 'boolean'],
        ], [
            'note.required' => 'Catatan wajib diisi untuk permintaan revisi atau penolakan.',
        ]);

        $to = ProposalStatus::from($data['to']);
        $publicData = [];
        if ($to === ProposalStatus::Approved) {
            $publicData = [
                'public_title' => $data['public_title'] ?? null,
                'public_short_description' => $data['public_short_description'] ?? null,
                'public_show_team' => $request->boolean('public_show_team'),
            ];
        }

        try {
            (new ProposalWorkflow)->transition($proposal, $to, $request->user(), $data['note'] ?? null, $publicData);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $message = 'Status diubah menjadi '.$to->label().'.';
        if ($to === ProposalStatus::Approved) {
            $message .= $proposal->fresh()->is_public
                ? ' Ringkasan produk otomatis tampil di katalog publik (PDF tetap privat).'
                : ' Produk tetap tersembunyi karena sebelumnya dicabut publikasinya; gunakan "Terbitkan Produk" bila ingin menayangkan.';
        }

        return redirect()->route('admin.proposals.show', $proposal)->with('success', $message);
    }

    public function setPhase(Request $request, Proposal $proposal): RedirectResponse
    {
        $data = $request->validate(['review_phase_id' => ['nullable', Rule::exists('review_phases', 'id')]]);
        $old = $proposal->review_phase_id;
        $proposal->forceFill(['review_phase_id' => $data['review_phase_id'] ?? null])->save();
        Audit::log('proposal.phase_changed', $proposal, ['review_phase_id' => $old], ['review_phase_id' => $proposal->review_phase_id]);

        return back()->with('success', 'Fase penilaian proposal diperbarui.');
    }

    /** Unggah telaah internal (mis. hasil visitasi legal) yang hanya dapat dibuka admin. */
    public function storeInternalFile(Request $request, Proposal $proposal): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'mimetypes:application/pdf', new FileContentType(['application/pdf']), 'max:'.(max(1, (int) setting('max_pdf_size_mb', 10)) * 1024)],
        ]);

        $file = SecureFileStore::storeProposalFile($proposal, $request->file('file'), 'internal_legal', $request->user(), true);
        Audit::log('file.internal_uploaded', $proposal, [], ['file' => $file->uuid]);

        return back()->with('success', 'Berkas internal diunggah. Hanya admin yang dapat membukanya.');
    }
}
