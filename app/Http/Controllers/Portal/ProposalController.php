<?php

namespace App\Http\Controllers\Portal;

use App\Enums\ProposalStatus;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Proposal;
use App\Models\ProposalAnswer;
use App\Models\ProposalResearchHistory;
use App\Models\Team;
use App\Models\Theme;
use App\Models\ThemeQuestion;
use App\Services\Audit;
use App\Services\ProposalChecklist;
use App\Services\ProposalWorkflow;
use App\Support\Options;
use App\Support\Settings;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class ProposalController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $team = $this->team($request);
        if (! $team) {
            return redirect()->route('portal.team')->with('warning', 'Buat atau bergabung dengan tim terlebih dahulu sebelum menyusun proposal.');
        }

        $proposals = $team->proposals()->with(['theme', 'challenge'])->latest('id')->get();

        return view('portal.proposals.index', [
            'team' => $team,
            'proposals' => $proposals,
            'canCreate' => $this->canCreate($team),
        ]);
    }

    /** Langkah 1: pilih tema → lihat tantangan terkait → pilih tantangan. */
    public function create(Request $request): View|RedirectResponse
    {
        $team = $this->team($request);
        if (! $team) {
            return redirect()->route('portal.team')->with('warning', 'Buat atau bergabung dengan tim terlebih dahulu.');
        }
        if (! $this->canCreate($team)) {
            return redirect()->route('portal.proposals.index')->with('warning', 'Tim Anda sudah mencapai batas jumlah proposal.');
        }

        return view('portal.proposals.choose', $this->chooseData() + [
            'proposal' => null,
            'action' => route('portal.proposals.store'),
            'method' => 'POST',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $this->team($request);
        abort_unless($team && $this->canCreate($team), 403);

        $data = $this->validateChoice($request, true);

        $proposal = new Proposal([
            'product_name' => $data['product_name'],
            'contact_name' => $request->user()->name,
            'contact_email' => $request->user()->email,
            'contact_phone' => $request->user()->phone,
        ]);
        $proposal->forceFill([
            'team_id' => $team->id,
            'theme_id' => $data['theme_id'],
            'challenge_id' => $data['challenge_id'] ?? null,
            'created_by' => $request->user()->id,
            'slug' => Proposal::uniqueSlug($data['product_name']),
            'status' => ProposalStatus::Draft,
        ])->save();

        (new ProposalWorkflow)->recordHistory($proposal, null, ProposalStatus::Draft, $request->user(), 'Draf proposal dibuat.');
        Audit::log('proposal.created', $proposal, [], ['theme_id' => $proposal->theme_id, 'challenge_id' => $proposal->challenge_id]);

        return redirect()->route('portal.proposals.edit', $proposal)->with('success', 'Draf proposal dibuat. Lengkapi setiap bagian lalu simpan sebagai draf.');
    }

    public function show(Request $request, Proposal $proposal): View
    {
        $this->authorize('view', $proposal);
        $proposal->load(['theme', 'challenge.owner', 'team.members', 'researchHistories', 'files', 'answers.question']);

        return view('portal.proposals.show', [
            'proposal' => $proposal,
            'checklist' => ProposalChecklist::items($proposal),
        ]);
    }

    public function edit(Request $request, Proposal $proposal): View|RedirectResponse
    {
        $this->authorize('view', $proposal);
        if (! $request->user()->can('update', $proposal)) {
            return redirect()->route('portal.proposals.show', $proposal)->with('warning', 'Proposal sudah dikirim dan terkunci. Panitia dapat membukanya kembali untuk revisi.');
        }

        $proposal->load(['theme', 'challenge', 'team.members', 'researchHistories', 'files', 'answers']);

        return view('portal.proposals.edit', [
            'proposal' => $proposal,
            'questions' => ThemeQuestion::query()->forTheme($proposal->theme_id)->get(),
            'answers' => $proposal->answers->pluck('answer', 'theme_question_id'),
            'checklist' => ProposalChecklist::items($proposal),
            'percent' => ProposalChecklist::percent($proposal),
            'limit' => (int) setting('proposal_word_limit', 300),
        ]);
    }

    public function update(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);

        $long = ['nullable', 'string', 'max:6000'];
        $rules = array_fill_keys(Proposal::LONG_TEXT_FIELDS, $long) + [
            'product_name' => ['required', 'string', 'max:190'],
            'short_summary' => ['nullable', 'string', 'max:300'],
            'tkt_level' => ['nullable', 'integer', 'between:1,9'],
            'prototype_status' => ['nullable', Rule::in(array_keys(Options::prototypeStatuses()))],
            'contact_name' => ['nullable', 'string', 'max:150'],
            'contact_email' => ['nullable', 'email', 'max:190'],
            'contact_phone' => ['nullable', 'string', 'max:32', 'regex:/^[0-9+\-\s().]{6,32}$/'],
            'ip_status' => ['nullable', Rule::in(array_keys(Options::ipStatuses()))],
            'regulatory_status' => ['nullable', Rule::in(array_keys(Options::regulatoryStatuses()))],
            'ethics_status' => ['nullable', Rule::in(array_keys(Options::ethicsStatuses()))],
            'data_risk_status' => ['nullable', Rule::in(array_keys(Options::dataRiskStatuses()))],
            'demo_video_url' => ['nullable', 'url:http,https', 'max:500'],
            'repository_url' => ['nullable', 'url:http,https', 'max:500'],
            'answers' => ['nullable', 'array'],
            'answers.*' => ['nullable', 'string', 'max:6000'],
        ];

        $data = $request->validate($rules);
        $fields = collect($data)->only(array_merge(Proposal::PARTICIPANT_TEXT_FIELDS, ['tkt_level']))->all();
        $proposal->fill($fields)->save();

        $questionIds = ThemeQuestion::query()->forTheme($proposal->theme_id)->pluck('id');
        foreach ($data['answers'] ?? [] as $questionId => $answer) {
            if ($questionIds->contains((int) $questionId)) {
                ProposalAnswer::query()->updateOrCreate(
                    ['proposal_id' => $proposal->id, 'theme_question_id' => (int) $questionId],
                    ['answer' => $answer]
                );
            }
        }

        if ($request->input('next') === 'files') {
            return redirect()->route('portal.proposals.files', $proposal)->with('success', 'Draf tersimpan. Lanjutkan dengan mengunggah berkas.');
        }

        return redirect()->to(route('portal.proposals.edit', $proposal).($request->input('section') ? '#'.$request->input('section') : ''))
            ->with('success', 'Draf proposal tersimpan.');
    }

    public function editChallenge(Request $request, Proposal $proposal): View|RedirectResponse
    {
        $this->authorize('view', $proposal);
        if (! $request->user()->can('update', $proposal)) {
            return redirect()->route('portal.proposals.show', $proposal)->with('warning', 'Tema dan tantangan tidak dapat diubah setelah proposal dikirim.');
        }

        return view('portal.proposals.choose', $this->chooseData() + [
            'proposal' => $proposal,
            'action' => route('portal.proposals.challenge.update', $proposal),
            'method' => 'PUT',
        ]);
    }

    public function updateChallenge(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);
        $data = $this->validateChoice($request, false);

        $old = ['theme_id' => $proposal->theme_id, 'challenge_id' => $proposal->challenge_id];
        $proposal->forceFill([
            'theme_id' => $data['theme_id'],
            'challenge_id' => $data['challenge_id'] ?? null,
        ])->save();
        $new = ['theme_id' => $proposal->theme_id, 'challenge_id' => $proposal->challenge_id];

        if ($old !== $new) {
            // Perubahan tema setelah proposal pernah dikirim (dibuka kembali admin) wajib tercatat.
            $action = $proposal->version > 0 ? 'proposal.theme_changed_after_reopen' : 'proposal.theme_changed';
            Audit::log($action, $proposal, $old, $new, 'Tema/tantangan proposal diubah');
        }

        return redirect()->route('portal.proposals.edit', $proposal)->with('success', 'Tema dan tantangan diperbarui.');
    }

    public function storeResearch(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('update', $proposal);
        $data = $request->validate([
            'year' => ['required', 'string', 'max:20'],
            'title' => ['required', 'string', 'max:1000'],
            'researchers' => ['nullable', 'string', 'max:1000'],
            'funding_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'funding_source' => ['nullable', 'string', 'max:190'],
        ]);

        if ($proposal->researchHistories()->count() >= 15) {
            return back()->with('error', 'Maksimal 15 riwayat penelitian.');
        }

        $proposal->researchHistories()->create($data + ['sort_order' => $proposal->researchHistories()->count() + 1]);

        return redirect()->to(route('portal.proposals.edit', $proposal).'#riset')->with('success', 'Riwayat penelitian ditambahkan.');
    }

    public function destroyResearch(Request $request, Proposal $proposal, ProposalResearchHistory $research): RedirectResponse
    {
        $this->authorize('update', $proposal);
        abort_unless((int) $research->proposal_id === (int) $proposal->id, 404);
        $research->delete();

        return redirect()->to(route('portal.proposals.edit', $proposal).'#riset')->with('success', 'Riwayat penelitian dihapus.');
    }

    /** Layar konfirmasi akhir sebelum pengiriman. */
    public function confirm(Request $request, Proposal $proposal): View|RedirectResponse
    {
        $this->authorize('view', $proposal);
        if (! $proposal->status->isSubmittableByParticipant()) {
            return redirect()->route('portal.proposals.status', $proposal)->with('warning', 'Proposal ini sudah dikirim.');
        }

        $proposal->load(['theme', 'challenge', 'team.members', 'files']);

        return view('portal.proposals.confirm', [
            'proposal' => $proposal,
            'checklist' => ProposalChecklist::items($proposal),
            'complete' => ProposalChecklist::isComplete($proposal),
            'isLeader' => $proposal->team->isLeader($request->user()),
            'openCall' => Settings::openCallState(),
            'needsOpenCall' => $proposal->status === ProposalStatus::Draft,
        ]);
    }

    public function submit(Request $request, Proposal $proposal): RedirectResponse
    {
        $this->authorize('submit', $proposal);
        $request->validate(['confirm' => ['accepted']], ['confirm.accepted' => 'Centang pernyataan sebelum mengirim proposal.']);

        if ($proposal->status === ProposalStatus::Draft && ! Settings::openCallIsOpen()) {
            return back()->with('error', 'Open Call sedang tidak dibuka. Proposal belum dapat dikirim.');
        }

        if (! ProposalChecklist::isComplete($proposal)) {
            return back()->with('error', 'Lengkapi semua butir daftar periksa sebelum mengirim.');
        }

        try {
            (new ProposalWorkflow)->submit($proposal, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('portal.proposals.status', $proposal)->with('success', 'Proposal berhasil dikirim pada '.fmt_date($proposal->submitted_at, true).' (versi '.$proposal->version.').');
    }

    public function status(Request $request, Proposal $proposal): View
    {
        $this->authorize('view', $proposal);
        $proposal->load(['theme', 'challenge', 'statusHistories', 'versions', 'programStage']);

        $showScores = (bool) setting('show_scores_to_participants', false);
        $showComments = (bool) setting('show_reviewer_comments_to_participants', false);
        $reviews = ($showScores || $showComments)
            ? $proposal->reviews()->where('status', 'submitted')->with('phase')->get()
            : collect();

        return view('portal.proposals.status', compact('proposal', 'showScores', 'showComments', 'reviews'));
    }

    private function team(Request $request): ?Team
    {
        return $request->user()->activeTeam();
    }

    private function canCreate(Team $team): bool
    {
        return $team->proposals()->count() < max(1, (int) setting('max_proposals_per_team', 1));
    }

    private function chooseData(): array
    {
        $themes = Theme::query()->active()->get();
        $challenges = Challenge::query()->published()
            ->with(['themes:id', 'owner', 'theme'])
            ->orderBy('sort_order')->orderBy('title')
            ->get();

        return [
            'themes' => $themes,
            'challenges' => $challenges,
            'requireChallenge' => (bool) setting('require_challenge_selection', true),
        ];
    }

    private function validateChoice(Request $request, bool $withName): array
    {
        $rules = [
            'theme_id' => ['required', Rule::exists('themes', 'id')->where('is_active', true)],
            'challenge_id' => [setting('require_challenge_selection', true) ? 'required' : 'nullable', 'integer'],
        ];
        if ($withName) {
            $rules['product_name'] = ['required', 'string', 'max:190'];
        }

        $validator = validator($request->all(), $rules, [
            'challenge_id.required' => 'Pilih satu tantangan yang sesuai dengan tema.',
            'theme_id.required' => 'Pilih tema proposal.',
        ]);

        $validator->after(function (Validator $validator) use ($request) {
            $challengeId = $request->input('challenge_id');
            if (! $challengeId || $validator->errors()->has('theme_id')) {
                return;
            }

            $eligible = Challenge::query()->published()
                ->whereKey($challengeId)
                ->whereHas('themes', fn ($q) => $q->where('themes.id', $request->input('theme_id')))
                ->exists();

            if (! $eligible) {
                $validator->errors()->add('challenge_id', 'Tantangan tidak tersedia untuk tema yang dipilih.');
            }
        });

        return $validator->validate();
    }
}
