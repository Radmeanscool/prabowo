<?php

namespace App\Models;

use App\Enums\ProposalStatus;
use App\Support\Options;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Proposal extends Model
{
    use SoftDeletes;

    /** Kolom isian peserta (satu-satunya kolom yang boleh diisi lewat formulir peserta). */
    public const PARTICIPANT_TEXT_FIELDS = [
        'product_name', 'short_summary', 'prototype_status',
        'contact_name', 'contact_email', 'contact_phone', 'inventors', 'previous_work',
        'existing_results', 'ip_publications',
        'technical_description', 'technical_validation', 'technical_limitations',
        'target_users', 'market_description', 'competitors', 'value_proposition', 'market_validation',
        'impact_description', 'adoption_plan',
        'problem_statement', 'challenge_importance', 'solution_description', 'minimum_functions',
        'success_indicators', 'data_facility_needs', 'pilot_location',
        'ip_status', 'ip_notes', 'regulatory_status', 'regulatory_notes', 'third_party_dependencies',
        'safety_risks', 'data_risk_status', 'data_notes', 'ethics_status', 'ethics_notes',
        'environmental_risks', 'mitigation_plan',
        'demo_video_url', 'repository_url',
    ];

    /** Kolom teks panjang yang dibatasi jumlah kata. */
    public const LONG_TEXT_FIELDS = [
        'inventors', 'previous_work', 'existing_results', 'ip_publications',
        'technical_description', 'technical_validation', 'technical_limitations',
        'target_users', 'market_description', 'competitors', 'value_proposition', 'market_validation',
        'impact_description', 'adoption_plan',
        'problem_statement', 'challenge_importance', 'solution_description', 'minimum_functions',
        'success_indicators', 'data_facility_needs', 'pilot_location',
        'ip_notes', 'regulatory_notes', 'third_party_dependencies', 'safety_risks', 'data_notes',
        'ethics_notes', 'environmental_risks', 'mitigation_plan',
    ];

    /** Kolom ringkasan publik yang dapat disunting admin secara terpisah. */
    public const PUBLIC_FIELDS = [
        'public_title', 'public_short_description', 'public_full_description', 'public_problem',
        'public_solution', 'public_target_users', 'public_technology', 'public_impact',
        'public_team_label', 'public_show_team', 'public_show_tkt', 'public_show_links', 'public_badge',
    ];

    /**
     * Hanya isian peserta dan ringkasan publik yang boleh diisi massal. Status, publikasi
     * (is_public, published_at, public_slug, public_pdf_enabled), dan kolom sistem diubah
     * secara eksplisit oleh ProposalWorkflow.
     */
    protected $fillable = [...self::PARTICIPANT_TEXT_FIELDS, 'tkt_level', ...self::PUBLIC_FIELDS];

    protected function casts(): array
    {
        return [
            'status' => ProposalStatus::class,
            'tkt_level' => 'integer',
            'version' => 'integer',
            'first_submitted_at' => 'datetime',
            'submitted_at' => 'datetime',
            'reopened_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'published_at' => 'datetime',
            'unpublished_at' => 'datetime',
            'is_public' => 'boolean',
            'public_pdf_enabled' => 'boolean',
            'public_show_team' => 'boolean',
            'public_show_tkt' => 'boolean',
            'public_show_links' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function challenge(): BelongsTo
    {
        return $this->belongsTo(Challenge::class)->withTrashed();
    }

    public function programStage(): BelongsTo
    {
        return $this->belongsTo(ProgramStage::class);
    }

    public function reviewPhase(): BelongsTo
    {
        return $this->belongsTo(ReviewPhase::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ProposalFile::class)->latest('id');
    }

    public function researchHistories(): HasMany
    {
        return $this->hasMany(ProposalResearchHistory::class)->orderBy('sort_order')->orderBy('id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ProposalVersion::class)->orderByDesc('version');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(ProposalStatusHistory::class)->orderByDesc('id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ReviewerAssignment::class);
    }

    public function activeAssignments(): HasMany
    {
        return $this->assignments()->whereNotIn('status', ['removed', 'conflict_declared']);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ProposalAnswer::class);
    }

    public function mentorAssignments(): HasMany
    {
        return $this->hasMany(MentorAssignment::class);
    }

    /** Produk publik: disetujui, tidak disembunyikan, dan tidak ditolak/diarsipkan. */
    public function scopePublicProducts(Builder $query): Builder
    {
        return $query->where('is_public', true)
            ->whereNotNull('approved_at')
            ->whereNotNull('public_slug')
            ->whereNotIn('status', [ProposalStatus::Rejected->value, ProposalStatus::Archived->value, ProposalStatus::Draft->value]);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_public
            && $this->approved_at !== null
            && $this->public_slug !== null
            && ! in_array($this->status, [ProposalStatus::Rejected, ProposalStatus::Archived, ProposalStatus::Draft], true);
    }

    /** Dapat ditayangkan ulang setelah disembunyikan (hanya jika pernah disetujui). */
    public function isPublishable(): bool
    {
        return $this->approved_at !== null
            && ! in_array($this->status, [ProposalStatus::Rejected, ProposalStatus::Archived, ProposalStatus::Draft], true);
    }

    public function isEditableByParticipant(): bool
    {
        return $this->status->isEditableByParticipant();
    }

    public function currentFile(string $type): ?ProposalFile
    {
        if ($this->relationLoaded('files')) {
            return $this->files->firstWhere('type', $type);
        }

        return $this->files()->where('type', $type)->first();
    }

    public function filesOfType(string $type)
    {
        if ($this->relationLoaded('files')) {
            return $this->files->where('type', $type)->values();
        }

        return $this->files()->where('type', $type)->get();
    }

    public function displayTitle(): string
    {
        return $this->public_title ?: $this->product_name;
    }

    public function publicBadgeLabel(): ?string
    {
        return $this->public_badge ? Options::label(Options::publicBadges(), $this->public_badge) : null;
    }

    public function publicTeamLabel(): ?string
    {
        if (! $this->public_show_team) {
            return null;
        }

        if ($this->public_team_label) {
            return $this->public_team_label;
        }

        $team = $this->team;

        return $team ? trim($team->name.($team->institution ? ' · '.$team->institution : '')) : null;
    }

    public static function uniqueSlug(string $name, string $column = 'slug'): string
    {
        $base = Str::slug(Str::limit($name, 80, '')) ?: 'proposal';
        $slug = $base;
        $i = 2;
        while (self::withTrashed()->where($column, $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
