<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalVersion extends Model
{
    protected $fillable = ['proposal_id', 'version', 'data', 'submitted_by', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
