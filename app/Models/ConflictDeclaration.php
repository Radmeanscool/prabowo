<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConflictDeclaration extends Model
{
    protected $fillable = ['reviewer_id', 'team_id', 'proposal_id', 'institution', 'reason'];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }
}
