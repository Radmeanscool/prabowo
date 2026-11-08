<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProposalAnswer extends Model
{
    protected $fillable = ['proposal_id', 'theme_question_id', 'answer'];

    public function proposal(): BelongsTo
    {
        return $this->belongsTo(Proposal::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(ThemeQuestion::class, 'theme_question_id');
    }
}
