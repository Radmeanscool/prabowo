<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MentorAssignment extends Model
{
    protected $fillable = ['person_id', 'team_id', 'proposal_id', 'role', 'notes', 'assigned_by'];

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class)->withTrashed();
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class)->withTrashed();
    }

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class)->withTrashed();
    }

    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
