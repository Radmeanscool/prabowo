<?php

namespace App\Models;

use App\Support\Options;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ProposalFile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid', 'proposal_id', 'type', 'disk', 'path', 'original_name', 'mime_type', 'size', 'checksum', 'is_internal', 'uploaded_by',
    ];

    /** Path penyimpanan tidak pernah dikirim ke tampilan/JSON. */
    protected $hidden = ['path', 'disk'];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'size' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ProposalFile $file) {
            $file->uuid ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type, 'image/');
    }

    public function isPdf(): bool
    {
        return $this->mime_type === 'application/pdf';
    }

    public function typeLabel(): string
    {
        return Options::label(Options::fileTypes(), $this->type);
    }

    /** Berkas sensitif yang aksesnya dicatat di log audit. */
    public function isSensitive(): bool
    {
        return in_array($this->type, ['proposal_pdf', 'technical_appendix', 'legal_evidence', 'internal_legal', 'supporting_evidence'], true);
    }
}
